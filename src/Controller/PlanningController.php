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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    #[Route('/planning/{id}/pdf', name: 'app_planning_pdf', methods: ['GET'])]
    public function generatePdf(Planning $planning, EntityManagerInterface $entityManager): Response
    {
        // Get associated RendezVous
        $rendezVousList = $entityManager->getRepository(RendezVous::class)->findBy(['planning' => $planning]);

        // Render the HTML template
        $html = $this->renderView('planning/pdf.html.twig', [
            'planning' => $planning,
            'rendezVousList' => $rendezVousList,
        ]);

        // Configure Dompdf options
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Create a response for the generated PDF
        return new StreamedResponse(function () use ($dompdf) {
            echo $dompdf->output();
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="planning_' . $planning->getId() . '.pdf"',
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

    #[Route('/api/get-rendezvous/{id}', name: 'api_get_rendezvous', methods: ['GET'])]
    public function getRendezVous(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $planning = $entityManager->getRepository(Planning::class)->find($id);

        if (!$planning) {
            return new JsonResponse(['error' => 'Planning not found'], Response::HTTP_NOT_FOUND);
        }

        $currentDate = (new \DateTime())->format('Y-m-d');

        $heuredebut = new \DateTime($currentDate . ' ' . $planning->getHeuredebut()->format('H:i:s'));
        $heurefin = new \DateTime($currentDate . ' ' . $planning->getHeurefin()->format('H:i:s'));

        $rendezVous = $entityManager->getRepository(RendezVous::class)->createQueryBuilder('r')
            ->where('r.planning = :planning')
            ->andWhere('r.dateheure BETWEEN :heuredebut AND :heurefin')
            ->setParameter('planning', $planning)
            ->setParameter('heuredebut', $heuredebut)
            ->setParameter('heurefin', $heurefin)
            ->getQuery()
            ->getResult();

        $formattedRendezVous = array_map(function (RendezVous $rendezVous) {
            return [
                'description' => $rendezVous->getDescription(),
                'dateheure' => $rendezVous->getDateheure()->format('Y-m-d H:i'),
            ];
        }, $rendezVous);

        return new JsonResponse($formattedRendezVous);
    }

    #[Route('/planning/new', name: 'app_planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $planning = new Planning();
        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($planning->getHeuredebut() >= $planning->getHeurefin()) {
                    $this->addFlash('error', 'L\'heure de début doit être avant l\'heure de fin.');
                    return $this->redirectToRoute('app_planning_new');
                }

                $entityManager->persist($planning);
                $entityManager->flush();

                $this->addFlash('success', 'Planning créé avec succès!');

                return $this->redirectToRoute('app_planning_index', [], Response::HTTP_SEE_OTHER);
            } else {
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


        $entityManager->remove($planning);
        $entityManager->flush();

        $this->addFlash('success', 'Planning supprimé avec succès!');

        return $this->redirectToRoute('app_planning_index', [], Response::HTTP_SEE_OTHER);
    }

}
