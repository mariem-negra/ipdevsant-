<?php

namespace App\Controller;

use App\Entity\Planning;
use App\Entity\RendezVous;
use App\Form\RendezVousType;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rendez/vous')]
final class RendezVousController extends AbstractController
{
    #[Route(name: 'app_rendez_vous_index', methods: ['GET'])]
    public function index(RendezVousRepository $rendezVousRepository): Response
    {
        return $this->render('rendez_vous/index.html.twig', [
            'rendez_vouses' => $rendezVousRepository->findAll(),
        ]);
    }
    #[Route('/admin/rendez-vous', name: 'app_rendez_vous_admin_index', methods: ['GET'])]
public function adminIndex(RendezVousRepository $rendezVousRepository): Response
{
    return $this->render('rendez_vous/indexB.html.twig', [
        'rendez_vouses' => $rendezVousRepository->findAll(),
    ]);
}


    
    #[Route('/new', name: 'app_rendez_vous_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rendezVous = new RendezVous();
        $form = $this->createForm(RendezVousType::class, $rendezVous);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Vérification des erreurs avant l'enregistrement
            $errors = [];

            // Vérifier que la date du rendez-vous est dans le futur
            if ($rendezVous->getDateheure() < new \DateTime()) {
                $errors[] = "La date du rendez-vous doit être dans le futur.";
            }

            // Vérifier que le statut est valide
            $statutsValides = ['Confirmé', 'Annulé', 'En attente'];
            if (!in_array($rendezVous->getStatut(), $statutsValides, true)) {
                $errors[] = "Statut invalide. Veuillez sélectionner 'Confirmé', 'Annulé' ou 'En attente'.";
            }

            // Vérifier que la description contient au moins 10 caractères
            if (strlen($rendezVous->getDescription()) < 5) {
                $errors[] = "La description doit contenir au moins 5 caractères.";
            }

            // Vérifier que le planning est bien sélectionné
            if ($rendezVous->getPlanning() === null) {
                $errors[] = "Veuillez sélectionner un planning.";
            }

            // Si des erreurs sont détectées, afficher un message flash et ne pas enregistrer
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_rendez_vous_new');
            }

            // Persist et flush les données dans la base
            $entityManager->persist($rendezVous);
            $entityManager->flush();

            // Ajoutez un message flash pour informer l'utilisateur du succès
            $this->addFlash('success', 'Le rendez-vous a été créé avec succès.');

            // Redirige vers l'index des rendez-vous
            return $this->redirectToRoute('app_rendez_vous_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('rendez_vous/new.html.twig', [
            'form' => $form->createView(),  // Vérifiez bien cette ligne
        ]);
        
    }

    
    
    
    

    #[Route('/{id}', name: 'app_rendez_vous_show', methods: ['GET'])]
    public function show(RendezVous $rendezVou): Response
    {
        return $this->render('rendez_vous/show.html.twig', [
            'rendez_vou' => $rendezVou,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_rendez_vous_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RendezVous $rendezVou, EntityManagerInterface $entityManager): Response
    {
        // Créer le formulaire pour l'entité RendezVous
        $form = $this->createForm(RendezVousType::class, $rendezVou);
        $form->handleRequest($request);
    
        // Si le formulaire est soumis et valide, on enregistre les données
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
    
            // Redirection vers la liste des rendez-vous après modification
            return $this->redirectToRoute('app_rendez_vous_index', [], Response::HTTP_SEE_OTHER);
        }
    
        // Retourner la vue avec le formulaire et l'objet 'rendez_vous'
        return $this->render('rendez_vous/edit.html.twig', [
            'rendez_vous' => $rendezVou,  // Assurez-vous que la variable est nommée 'rendez_vous'
            'form' => $form->createView(),
        ]);
    }
    

    #[Route('/{id}', name: 'app_rendez_vous_delete', methods: ['POST'])]
    public function delete(Request $request, RendezVous $rendezVou, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rendezVou->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($rendezVou);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rendez_vous_index', [], Response::HTTP_SEE_OTHER);
    }
}
