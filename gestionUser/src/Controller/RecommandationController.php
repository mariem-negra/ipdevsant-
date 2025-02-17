<?php

namespace App\Controller;

use App\Entity\Recommandation;
use App\Entity\Demande;
use App\Form\RecommandationType;
use App\Repository\RecommandationRepository;
use App\Repository\DemandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/recommandation')]
final class RecommandationController extends AbstractController
{
    #[Route('/', name: 'app_recommandation_index', methods: ['GET'])]
    public function index(RecommandationRepository $recommandationRepository): Response
    {
        return $this->render('recommandation/index.html.twig', [
            'recommandations' => $recommandationRepository->findAll(),
        ]);
    }

    // The new action now receives a Demande ID so the doctor can create a recommendation for that specific demand.
    #[Route('/new/{demandeId}', name: 'app_recommandation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        int $demandeId, 
        DemandeRepository $demandeRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        $demande = $demandeRepository->find($demandeId);
        if (!$demande) {
            throw $this->createNotFoundException('Demande not found.');
        }

        $recommandation = new Recommandation();
        // Automatically link the Recommendation with the given Demande.
        $recommandation->setDemande($demande);

        //$form = $this->createForm(RecommandationType::class, $recommandation);
        $form = $this->createForm(RecommandationType::class, $recommandation, [
            'demande' => $demande,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recommandation);
            $entityManager->flush();

            return $this->redirectToRoute('app_recommandation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recommandation/new.html.twig', [
            'recommandation' => $recommandation,
            'form' => $form->createView(),
            'demande' => $demande,
        ]);
    }
    
    #[Route('/dashboard', name: 'app_recommandation_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('recommandation/dashboard.html.twig');
    }
    
    

    #[Route('/{id}', name: 'app_recommandation_show', methods: ['GET'])]
    public function show(Recommandation $recommandation): Response
    {
        return $this->render('recommandation/show.html.twig', [
            'recommandation' => $recommandation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_recommandation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recommandation $recommandation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecommandationType::class, $recommandation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_recommandation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recommandation/edit.html.twig', [
            'recommandation' => $recommandation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_recommandation_delete', methods: ['POST'])]
    public function delete(Request $request, Recommandation $recommandation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $recommandation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($recommandation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_recommandation_index', [], Response::HTTP_SEE_OTHER);
    }
}
