<?php

// src/Controller/PlanningController.php

namespace App\Controller;

use App\Entity\Planning;
use App\Entity\RendezVous;
use App\Form\PlanningType;
use App\Repository\PlanningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface; // Importer le validateur

class PlanningController extends AbstractController
{
    #[Route('/planning', name: 'app_planning_index', methods: ['GET'])]
    public function index(PlanningRepository $planningRepository): Response
    {
        return $this->render('planning/index.html.twig', [
            'plannings' => $planningRepository->findAll(),
        ]);
    }
    #[Route('/admin/planning', name: 'app_planning_admin_index', methods: ['GET'])]
public function adminIndex(PlanningRepository $planningRepository): Response
{
    return $this->render('planning/indexB.html.twig', [
        'plannings' => $planningRepository->findAll(),
    ]);
}
    
    #[Route('/api/get-planning-hours/{id}', name: 'api_get_planning_hours', methods: ['GET'])]
    public function getPlanningHours(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $planning = $entityManager->getRepository(Planning::class)->find($id);
    
        if (!$planning) {
            return new JsonResponse(['error' => 'Planning not found'], Response::HTTP_NOT_FOUND);
        }
    
        return new JsonResponse([
            'heuredebut' => $planning->getHeuredebut() ? $planning->getHeuredebut()->format('H:i') : null,
            'heurefin' => $planning->getHeurefin() ? $planning->getHeurefin()->format('H:i') : null
        ]);
    }
    

    #[Route('/planning/new', name: 'app_planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer une nouvelle instance de l'entité Planning
        $planning = new Planning();
        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Vérification que l'heure de début est avant l'heure de fin
                if ($planning->getHeuredebut() >= $planning->getHeurefin()) {
                    // Si l'heure de début est après ou égale à l'heure de fin, on ajoute un message d'erreur
                    $this->addFlash('error', 'L\'heure de début doit être avant l\'heure de fin.');
                    return $this->redirectToRoute('app_planning_new'); // Redirige vers le formulaire pour corriger l'erreur
                }

                // Si la validation est ok, on persiste les données
                $entityManager->persist($planning);
                $entityManager->flush();

                // Ajout d'un message de succès
                $this->addFlash('success', 'Planning créé avec succès!');

                // Redirection vers la liste des plannings
                return $this->redirectToRoute('app_planning_index', [], Response::HTTP_SEE_OTHER);
            } else {
                // Si le formulaire est invalide, afficher les erreurs
                $this->addFlash('error', 'Des erreurs ont été détectées dans le formulaire.');
            }
        }

        return $this->render('planning/new.html.twig', [
            'planning' => $planning,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/planning/{id}', name: 'app_planning_show', methods: ['GET'])]
    public function show(Planning $planning): Response
    {
        return $this->render('planning/show.html.twig', [
            'planning' => $planning,
        ]);
    }

    #[Route('/planning/{id}/edit', name: 'app_planning_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Planning $planning, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Si le formulaire est valide, on met à jour l'entité
                $entityManager->flush();

                // Ajout d'un message de succès
                $this->addFlash('success', 'Planning mis à jour avec succès!');

                // Redirection vers la liste des plannings
                return $this->redirectToRoute('app_planning_index', [], Response::HTTP_SEE_OTHER);
            } else {
                // Si le formulaire est invalide, afficher les erreurs
                $this->addFlash('error', 'Des erreurs ont été détectées dans le formulaire.');
            }
        }

        return $this->render('planning/edit.html.twig', [
            'planning' => $planning,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/planning/{id}', name: 'app_planning_delete', methods: ['POST'])]
public function delete(Request $request, Planning $planning, EntityManagerInterface $entityManager): Response
{
    // Check if any RendezVous is referencing this planning
    $rendezvous = $entityManager->getRepository(RendezVous::class)->findBy(['planning' => $planning]);

    if (count($rendezvous) > 0) {
        // If there are rendezvous, you could either:
        // 1. Delete them
        foreach ($rendezvous as $rdv) {
            $entityManager->remove($rdv);
        }

        // 2. Or, if you prefer, you could update them to not reference the planning
        // for ($rdv in $rendezvous) {
        //    $rdv->setPlanning(null);
        //    $entityManager->persist($rdv);
        // }
    }

    // Finally, delete the planning record
    $entityManager->remove($planning);
    $entityManager->flush();

    $this->addFlash('success', 'Planning supprimé avec succès!');
    
    return $this->redirectToRoute('app_planning_index', [], Response::HTTP_SEE_OTHER);
}

}
