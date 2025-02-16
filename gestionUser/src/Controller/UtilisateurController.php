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

    #[Route(name: 'app_utilisateur_index', methods: ['GET'])]
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

    #[Route('/{id}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);
        $user = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
        $diplomaFile = $form->get('diploma')->getData();

        if ($diplomaFile) {
            // Handle file upload (e.g., move the file to a directory)
            $newFilename = uniqid().'.'.$diplomaFile->guessExtension();
            $diplomaFile->move(
                $this->getParameter('diplomas'),
                $newFilename
            );
            $utilisateur->setDiploma($newFilename);
        }

        // Save the user
        $entityManager->persist($utilisateur);        
            $entityManager->flush();
            $this->addFlash('success', 'Historique de traitement créé avec succès.');

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
            'user' => $user,

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