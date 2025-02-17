<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Enum\UserRole;
#[Route('/utilisateur')]
final class UtilisateurController extends AbstractController
{

    #[Route(name: 'app_utilisateur_index', methods: ['GET', 'POST'])]
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        $user = $this->getUser();

        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurRepository->findAll(),
                'user' => $user,
            
        ]);
    }

    #[Route('/new', name: 'app_utilisateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $utilisateur,
                $form->get('motDePasse')->getData()
            );
            $utilisateur->setMotDePasse($hashedPassword);
            $utilisateur->setPassword($hashedPassword);
            if ($utilisateur->getRole() === UserRole::MEDECIN) {
                $diplomaFile = $form->get('diploma')->getData();
        
                if ($diplomaFile) {
                    $newFilename = uniqid().'.'.$diplomaFile->guessExtension();
        
                    try {
                        $diplomaFile->move(
                            $this->getParameter('diplomas_directory'),
                            $newFilename
                        );
                        $utilisateur->setDiploma($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement du diplôme.');
                        return $this->render('utilisateur/new.html.twig', [
                            'form' => $form->createView(),
                        ]);
                    }
                } else {
                    $this->addFlash('error', 'Le diplôme est requis pour les médecins.');
                    return $this->render('utilisateur/new.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);        
        }

        return $this->render('utilisateur/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_utilisateur_show', methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        $user = $this->getUser();

        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
            'user' => $user,

        ]);
    }

// Controller:
#[Route('/{id}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
{
    if ($request->isMethod('POST')) {
        // Get form data
        $utilisateur->setNom($request->request->get('nom'));
        $utilisateur->setPrenom($request->request->get('prenom'));
        $utilisateur->setEmail($request->request->get('email'));
        $utilisateur->setTelephone($request->request->get('telephone'));
        $utilisateur->setDateNaissance(new \DateTime($request->request->get('dateNaissance')));
        
        if ($utilisateur->getRole() === 'Médecin') {
            $utilisateur->setSpecialite($request->request->get('specialite'));
        }

        // Handle profile image upload
        $imageFile = $request->files->get('image');
        if ($imageFile instanceof UploadedFile) {
            $allowedMimeTypes = ['image/jpeg', 'image/png'];
            if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                $this->addFlash('error', 'Type de fichier non autorisé pour l\'image. Utilisez JPG ou PNG.');
                return $this->redirectToRoute('app_utilisateur_edit', ['id' => $utilisateur->getId()]);
            }

            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $uploadDirectory = $this->getParameter('profile_images_directory');

            try {
                $imageFile->move($uploadDirectory, $newFilename);
                $utilisateur->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Échec du téléchargement de l\'image.');
                return $this->redirectToRoute('app_utilisateur_edit', ['id' => $utilisateur->getId()]);
            }
        }

        // Handle diploma upload
        $diplomaFile = $request->files->get('diploma');
        if ($diplomaFile instanceof UploadedFile) {
            $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!in_array($diplomaFile->getMimeType(), $allowedMimeTypes)) {
                $this->addFlash('error', 'Type de fichier non autorisé pour le diplôme. Utilisez PDF, JPG ou PNG.');
                return $this->redirectToRoute('app_utilisateur_edit', ['id' => $utilisateur->getId()]);
            }

            $newFilename = uniqid() . '.' . $diplomaFile->guessExtension();
            $uploadDirectory = $this->getParameter('diplomas_directory');

            try {
                $diplomaFile->move($uploadDirectory, $newFilename);
                $utilisateur->setDiploma($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Échec du téléchargement du diplôme.');
                return $this->redirectToRoute('app_utilisateur_edit', ['id' => $utilisateur->getId()]);
            }
        }

        try {
            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_utilisateur_index');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du profil.');
            return $this->redirectToRoute('app_utilisateur_edit', ['id' => $utilisateur->getId()]);
        }
    }

    return $this->render('utilisateur/edit.html.twig', [
        'utilisateur' => $utilisateur,
        'user' => $this->getUser(),
    ]);
}

    #[Route('/{id}', name: 'app_utilisateur_delete', methods: ['POST'])]
    public function delete(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$utilisateur->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($utilisateur);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
    }
}