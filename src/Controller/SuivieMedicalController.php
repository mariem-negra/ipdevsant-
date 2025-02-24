<?php

namespace App\Controller;

use App\Entity\SuivieMedical;
use App\Entity\HistoriqueTraitement;
use App\Form\SuivieMedicalType;
use App\Repository\SuivieMedicalRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\HistoriqueTraitementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/suivie/medical')]
final class SuivieMedicalController extends AbstractController
{
    
    #[Route(name: 'app_suivie_medical_index', methods: ['GET'])]
    public function index(SuivieMedicalRepository $suivieMedicalRepository): Response
    {
        return $this->render('suivie_medical/index.html.twig', [
            'suivie_medicals' => $suivieMedicalRepository->findAll(),
        ]);
    }

    #[Route('/front', name: 'app_suivie_medical_index_front', methods: ['GET'])]
    public function indexFront(SuivieMedicalRepository $suivieMedicalRepository, HistoriqueTraitementRepository $historiqueTraitementRepository): Response 
    {
        $historiques = $historiqueTraitementRepository->findAll();
        $suivieMedicals = $suivieMedicalRepository->findAll();
        $suivisCount = [];

        foreach ($historiques as $historique) {
            $count = $suivieMedicalRepository->count(['id_historique' => $historique]);
            $suivisCount[$historique->getId()] = $count;
        }

        return $this->render('suivie_medical/indexFront.html.twig', [
            'historiques' => $historiques,
            'suivie_medicals' => $suivieMedicals,
            'suivisCount' => $suivisCount,
        ]);
    }

    #[Route('/check/{historique_id}', name: 'app_suivie_medical_check', methods: ['GET'])]
    public function checkExistingSuivi(
        int $historique_id, 
        SuivieMedicalRepository $suivieMedicalRepository,
        HistoriqueTraitementRepository $historiqueTraitementRepository
    ): Response {
        $historique = $historiqueTraitementRepository->find($historique_id);
        
        if (!$historique) {
            return $this->json(['error' => 'Historique not found'], 404);
        }

        $count = $suivieMedicalRepository->count(['id_historique' => $historique]);
        
        return $this->json([
            'count' => $count
        ]);
    }

    #[Route('/new/{historique_id}', name: 'app_suivie_medical_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        ?int $historique_id = null
    ): Response {
        $suivieMedical = new SuivieMedical();
        $suivieMedical->setDate(new \DateTime());

        if ($historique_id) {
            $historiqueTraitement = $entityManager
                ->getRepository(HistoriqueTraitement::class)
                ->find($historique_id);

            if (!$historiqueTraitement) {
                $this->addFlash('error', 'Historique de traitement non trouvé.');
                return $this->redirectToRoute('app_suivie_medical_index_front');
            }

            $suivieMedical->setIdHistorique($historiqueTraitement);
        }

        $form = $this->createForm(SuivieMedicalType::class, $suivieMedical);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($suivieMedical);
                $entityManager->flush();
                $this->addFlash('success', 'Le suivi médical a été créé avec succès.');
                return $this->redirectToRoute('app_suivie_medical_index_front');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création du suivi médical.');
            }
        }

        $patientInfo = null;
        if (isset($historiqueTraitement)) {
            $patientInfo = [
                'nom' => $historiqueTraitement->getNom(),
                'prenom' => $historiqueTraitement->getPrenom(),
                'bilan' => $historiqueTraitement->getBilan()
            ];
        }

        return $this->render('suivie_medical/new.html.twig', [
            'suivie_medical' => $suivieMedical,
            'form' => $form->createView(),
            'patient_info' => $patientInfo
        ]);
    }

    #[Route('/{id}', name: 'app_suivie_medical_show', methods: ['GET'])]
    public function show(SuivieMedical $suivieMedical): Response
    {
        return $this->render('suivie_medical/show.html.twig', [
            'suivie_medical' => $suivieMedical,
        ]);
    }

    #[Route('/{id}', name: 'app_suivie_medical_showB', methods: ['GET'])]
    public function showB(SuivieMedical $suivieMedical): Response
    {
        return $this->render('suivie_medical/showB.html.twig', [
            'suivie_medical' => $suivieMedical,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_suivie_medical_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SuivieMedical $suivieMedical, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SuivieMedicalType::class, $suivieMedical);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Le suivi médical a été modifié avec succès.');
                return $this->redirectToRoute('app_suivie_medical_index_front', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du suivi médical.');
            }
        }

        return $this->render('suivie_medical/edit.html.twig', [
            'suivie_medical' => $suivieMedical,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_suivie_medical_delete', methods: ['POST'])]
    public function delete(Request $request, SuivieMedical $suivieMedical, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$suivieMedical->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($suivieMedical);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_suivie_medical_index_front', [], Response::HTTP_SEE_OTHER);
    }
}