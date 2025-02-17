<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Enum\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            if ($request->request->get('action') === 'change_password') {
                // Handle password change
                $currentPassword = $request->request->get('currentPassword');
                $newPassword = $request->request->get('newPassword');
                $confirmPassword = $request->request->get('confirmPassword');

                // Password validations
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $this->addFlash('error', 'Tous les champs de mot de passe sont requis.');
                    return $this->redirectToRoute('app_profile');
                }

                if (strlen($newPassword) < 8) {
                    $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
                    return $this->redirectToRoute('app_profile');
                }

                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                    return $this->redirectToRoute('app_profile');
                }

                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                    return $this->redirectToRoute('app_profile');
                }

                try {
                    $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);
                    $entityManager->flush();
                    $this->addFlash('success', 'Mot de passe mis à jour avec succès!');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du mot de passe.');
                }
            } else {
                // Handle profile update
                $nom = $request->request->get('nom');
                $prenom = $request->request->get('prenom');
                $email = $request->request->get('email');
                $telephone = $request->request->get('telephone');

                // Validate required fields
                if (empty($nom) || empty($prenom) || empty($email)) {
                    $this->addFlash('error', 'Les champs nom, prénom et email sont obligatoires.');
                    return $this->redirectToRoute('app_profile');
                }

                try {
                    $user->setNom($nom);
                    $user->setPrenom($prenom);
                    $user->setEmail($email);
                    
                    // Phone validation
                    if (!empty($telephone)) {
                        if (!preg_match('/^\d{8}$/', $telephone)) {
                            $this->addFlash('error', 'Le numéro de téléphone doit contenir exactement 8 chiffres.');
                            return $this->redirectToRoute('app_profile');
                        }
                        $user->setTelephone((int)$telephone);
                    }
                    
                    // Date validation
                    $dateNaissance = $request->request->get('dateNaissance');
                    if (!empty($dateNaissance)) {
                        try {
                            $user->setDateNaissance(new \DateTime($dateNaissance));
                        } catch (\Exception $e) {
                            $this->addFlash('error', 'Format de date invalide.');
                            return $this->redirectToRoute('app_profile');
                        }
                    }
                    
                    // Handle specialité for MEDECIN role
                    if ($user->getRole() === UserRole::MEDECIN) {
                        $specialite = $request->request->get('specialite');
                        if (!empty($specialite)) {
                            $user->setSpecialite($specialite);
                        }
                    }

                    // Handle image upload
                    $imageFile = $request->files->get('image');
                    if ($imageFile instanceof UploadedFile) 
                    {
                        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                            $this->addFlash('error', 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
                            return $this->redirectToRoute('app_profile');
                        }

                        $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                        $uploadDirectory = $this->getParameter('profile_images_directory');

                        try {
                            $imageFile->move($uploadDirectory, $newFilename);
                            $user->setImage($newFilename);
                        } catch (FileException $e) {
                            $this->addFlash('error', 'Échec du téléchargement de l\'image.');
                            return $this->redirectToRoute('app_profile');
                        }
                    }
                    // Handle diploma upload
                    $diplomaFile = $request->files->get('diploma');
                    if ($diplomaFile instanceof UploadedFile) {
                        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                        if (!in_array($diplomaFile->getMimeType(), $allowedMimeTypes)) {
                            $this->addFlash('error', 'Type de fichier non autorisé. Utilisez PDF, JPG ou PNG.');
                            return $this->redirectToRoute('app_profile');
                        }
                    
                        $newFilename = uniqid() . '.' . $diplomaFile->guessExtension();
                        $uploadDirectory = $this->getParameter('diplomas_directory');
                    
                        try {
                            $diplomaFile->move($uploadDirectory, $newFilename);
                            $user->setDiploma($newFilename);
                        } catch (FileException $e) {
                            $this->addFlash('error', 'Échec du téléchargement du diplôme.');
                            return $this->redirectToRoute('app_profile');
                        }
                    }

                    $entityManager->flush();
                    $this->addFlash('success', 'Profil mis à jour avec succès!');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du profil.');
                }
            }
            
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }
}