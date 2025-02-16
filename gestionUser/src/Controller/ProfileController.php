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
    public function profile(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response 
    {
        $user = $this->getUser();
        
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
                    // Check if the action is to change the password
        if ($request->request->get('action') === 'change_password') {
            $currentPassword = $request->request->get('currentPassword');
            $newPassword = $request->request->get('newPassword');
            $confirmPassword = $request->request->get('confirmPassword');

            // Validate current password
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('app_profile');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_profile');
            }

            // Validate new password format (letters, numbers, and minimum length of 8)
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $newPassword)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères, dont des lettres et des chiffres.');
                return $this->redirectToRoute('app_profile');
            }

            // Hash and set the new password
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $entityManager->flush();
            $this->addFlash('success', 'Mot de passe mis à jour avec succès!');
            return $this->redirectToRoute('app_profile');
        } 
        {
                // Handle profile update
                $nom = $request->request->get('nom');
                $prenom = $request->request->get('prenom');
                $email = $request->request->get('email');
                $telephone = $request->request->get('telephone');
                $dateNaissance = $request->request->get('dateNaissance');

                // Validate nom and prenom (only letters)
                if (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $nom)) {
                    $this->addFlash('error', 'Le nom ne doit contenir que des lettres.');
                    return $this->redirectToRoute('app_profile');
                }

                if (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $prenom)) {
                    $this->addFlash('error', 'Le prénom ne doit contenir que des lettres.');
                    return $this->redirectToRoute('app_profile');
                }

                // Validate telephone (exactly 8 digits)
                if (!preg_match('/^\d{8}$/', $telephone)) {
                    $this->addFlash('error', 'Le téléphone doit contenir exactement 8 chiffres.');
                    return $this->redirectToRoute('app_profile');
                }

                // Validate dateNaissance (not in the future)
                if (new \DateTime($dateNaissance) > new \DateTime()) {
                    $this->addFlash('error', 'La date de naissance ne peut pas être dans le futur.');
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
    #[Route('/admin/profile', name: 'app_admin_profile', methods: ['GET', 'POST'])]
    public function adminProfile(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
        ): Response {
        // Ensure the user is authenticated and is an ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }
    if ($request->isMethod('POST')) {
        // Check if the action is to change the password
        if ($request->request->get('action') === 'change_password') {
            $currentPassword = $request->request->get('currentPassword');
            $newPassword = $request->request->get('newPassword');
            $confirmPassword = $request->request->get('confirmPassword');

            // Validate current password
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('app_admin_profile');
            }
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_profile');
            }

            // Validate new password format (letters, numbers, and minimum length of 8)
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $newPassword)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères, dont des lettres et des chiffres.');
                return $this->redirectToRoute('app_profile');
            }

            // Hash and set the new password
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $entityManager->flush();
            $this->addFlash('success', 'Mot de passe mis à jour avec succès!');
            return $this->redirectToRoute('app_admin_profile');
        }            // Handle profile update logic (similar to the existing profile method)
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $email = $request->request->get('email');
            $telephone = $request->request->get('telephone');
            $dateNaissance = $request->request->get('dateNaissance');
            // Validate nom and prenom (only letters)
            if (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $nom)) {
                $this->addFlash('error', 'Le nom ne doit contenir que des lettres.');
                return $this->redirectToRoute('app_admin_profile');
            }

            if (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $prenom)) {
                $this->addFlash('error', 'Le prénom ne doit contenir que des lettres.');
                return $this->redirectToRoute('app_admin_profile');
            }

            // Validate telephone (exactly 8 digits)
            if (!preg_match('/^\d{8}$/', $telephone)) {
                $this->addFlash('error', 'Le téléphone doit contenir exactement 8 chiffres.');
                return $this->redirectToRoute('app_admin_profile');
            }

            // Validate dateNaissance (not in the future)
            if (new \DateTime($dateNaissance) > new \DateTime()) {
                $this->addFlash('error', 'La date de naissance ne peut pas être dans le futur.');
                return $this->redirectToRoute('app_admin_profile');
            }
            try {
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setEmail($email);
                $user->setDateNaissance(new \DateTime($dateNaissance));
                $user->setTelephone((int)$telephone);
                // Handle image upload (optional)
                $imageFile = $request->files->get('image');
                if ($imageFile instanceof UploadedFile) {
                    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                        $this->addFlash('error', 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
                        return $this->redirectToRoute('app_admin_profile');
                    }
                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    $uploadDirectory = $this->getParameter('profile_images_directory');

                    try {
                        $imageFile->move($uploadDirectory, $newFilename);
                        $user->setImage($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Échec du téléchargement de l\'image.');
                        return $this->redirectToRoute('app_admin_profile');
                    }
                }
                $entityManager->flush();
                $this->addFlash('success', 'Profil admin mis à jour avec succès!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du profil admin.');
            }

            return $this->redirectToRoute('app_admin_profile');
        }

        // Render the admin profile page
        return $this->render('profile/admin.html.twig', [
            'user' => $user,
        ]);
    }
}