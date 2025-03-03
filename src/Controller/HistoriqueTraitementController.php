<?php

namespace App\Controller;

use App\Entity\SuivieMedical;
use App\Entity\Rating;
use App\Entity\HistoriqueTraitement;
use App\Form\HistoriqueTraitementType;
use App\Repository\HistoriqueTraitementRepository;
use App\Repository\SuivieMedicalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\PdfService;


#[Route('/historique/traitement')]
final class HistoriqueTraitementController extends AbstractController
{
    #[Route(name: 'app_historique_traitement_index', methods: ['GET'])]
    public function index(Request $request, HistoriqueTraitementRepository $historiqueTraitementRepository): Response
    {
        // Récupération des critères de recherche
        $searchCriteria = [
            'nom' => $request->query->get('nom', ''),
            'prenom' => $request->query->get('prenom', ''),
            'maladie' => $request->query->get('maladie', ''),
            'typeTraitement' => $request->query->get('typeTraitement', '')
        ];
        
        // Récupération des paramètres de tri
        $sortField = $request->query->get('sort', 'id');
        $sortDirection = $request->query->get('direction', 'DESC');
        
        try {
            $hasSearchCriteria = array_filter($searchCriteria) !== [];
            
            $historiqueTraitements = $hasSearchCriteria
                ? $historiqueTraitementRepository->searchMultiCriteria($searchCriteria, $sortField, $sortDirection)
                : $historiqueTraitementRepository->findAll();
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'content' => $this->renderView('historique_traitement/_historique_traitement_list.html.twig', [
                        'historique_traitements' => $historiqueTraitements,
                        'currentSort' => [
                            'field' => $sortField,
                            'direction' => $sortDirection
                        ]
                    ]),
                    'count' => count($historiqueTraitements),
                    'success' => true
                ]);
            }
            
            return $this->render('historique_traitement/index.html.twig', [
                'historique_traitements' => $historiqueTraitements,
                'searchCriteria' => $searchCriteria,
                'currentSort' => [
                    'field' => $sortField,
                    'direction' => $sortDirection
                ]
            ]);
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la recherche.',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            $this->addFlash('error', 'Une erreur est survenue lors de la recherche: ' . $e->getMessage());
            return $this->render('historique_traitement/index.html.twig', [
                'historique_traitements' => [],
                'searchCriteria' => $searchCriteria,
                'currentSort' => [
                    'field' => $sortField,
                    'direction' => $sortDirection
                ]
            ]);
        }
    }

    #[Route('/indexFront', name: 'app_historique_traitement_indexFront', methods: ['GET'])]
    public function indexFront(HistoriqueTraitementRepository $historiqueTraitementRepository): Response
    {
        return $this->render('historique_traitement/indexFront.html.twig', [
            'historique_traitement' => $historiqueTraitementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_historique_traitement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {  
        $historiqueTraitement = new HistoriqueTraitement();
        $form = $this->createForm(HistoriqueTraitementType::class, $historiqueTraitement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $bilanFile = $form->get('bilan')->getData();

            if ($bilanFile) {
                $newFilename = uniqid().'.'.$bilanFile->guessExtension();

                // Move the file to the directory where bilan files are stored
                $bilanFile->move(
                    $this->getParameter('bilan_directory'), // Define this parameter in services.yaml
                    $newFilename
                );

                // Save the file path to the entity
                $historiqueTraitement->setBilan($newFilename);
            }
            $entityManager->persist($historiqueTraitement);
            $entityManager->flush();
            $this->addFlash('success', 'Historique de traitement créé avec succès.');

            return $this->redirectToRoute('app_historique_traitement_indexFront', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('historique_traitement/new.html.twig', [
            'historique_traitement' => $historiqueTraitement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_historique_traitement_show', methods: ['GET'])]
    public function show(
        HistoriqueTraitement $historiqueTraitement,
        EntityManagerInterface $entityManager
    ): Response {
        $suivieMedicals = $entityManager->getRepository(SuivieMedical::class)
        ->findBy(['id_historique' => $historiqueTraitement]);
        // Récupérer la note moyenne
        $averageRating = $entityManager->getRepository(Rating::class)
            ->getAverageRatingForTraitement($historiqueTraitement->getId());
        
        return $this->render('historique_traitement/show.html.twig', [
            'historique_traitement' => $historiqueTraitement,
            'suivie_medicals' => $suivieMedicals,
            'average_rating' => $averageRating ?: 0,
        ]);
    }

    #[Route('/{id}', name: 'app_historique_traitement_showB', methods: ['GET'])]
    public function showB(HistoriqueTraitement $historiqueTraitement): Response
    {
        return $this->render('historique_traitement/showB.html.twig', [
            'historique_traitement' => $historiqueTraitement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_historique_traitement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, HistoriqueTraitement $historiqueTraitement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HistoriqueTraitementType::class, $historiqueTraitement);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le fichier
            $bilanFile = $form->get('bilan')->getData();
            
            if ($bilanFile) {
                // Supprimer l'ancien fichier s'il existe
                $oldBilan = $historiqueTraitement->getBilan();
                if ($oldBilan) {
                    $oldFilePath = $this->getParameter('bilan_directory') . '/' . $oldBilan;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
    
                // Générer un nouveau nom de fichier
                $newFilename = uniqid() . '.' . $bilanFile->guessExtension();
    
                try {
                    // Déplacer le fichier
                    $bilanFile->move(
                        $this->getParameter('bilan_directory'),
                        $newFilename
                    );
                    
                    // Mettre à jour l'entité
                    $historiqueTraitement->setBilan($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement du fichier: ' . $e->getMessage());
                    return $this->redirectToRoute('app_historique_traitement_edit', ['id' => $historiqueTraitement->getId()]);
                }
            }
    
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Historique de traitement mis à jour avec succès.');
                return $this->redirectToRoute('app_historique_traitement_indexFront');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
            }
        }
    
        return $this->render('historique_traitement/edit.html.twig', [
            'historique_traitement' => $historiqueTraitement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_historique_traitement_delete', methods: ['POST'])]
    public function delete(Request $request, HistoriqueTraitement $historiqueTraitement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$historiqueTraitement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($historiqueTraitement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_historique_traitement_indexFront', [], Response::HTTP_SEE_OTHER);
    }
    
        #[Route('/historique/statistiques', name: 'app_historique_traitement_statistiques', methods: ['GET'])]
        public function statistiques(HistoriqueTraitementRepository $historiqueTraitementRepository): Response
        {
            // Récupérer les données de maladies et leurs occurrences
            $statistiques = $historiqueTraitementRepository->countByMaladie();
            
            // Préparer les données pour le graphique
            $labels = [];
            $data = [];
            $backgroundColor = [];
            
            foreach ($statistiques as $stat) {
                $labels[] = $stat['maladie'];
                $data[] = $stat['count'];
                // Générer des couleurs aléatoires pour chaque maladie
                $backgroundColor[] = sprintf(
                    'rgba(%d, %d, %d, 0.7)',
                    rand(0, 200),
                    rand(0, 200),
                    rand(100, 255)
                );
            }
            
            return $this->render('historique_traitement/statistiques.html.twig', [
                'labels' => $labels,
                'data' => $data,
                'backgroundColor' => $backgroundColor,
            ]);
        }
        #[Route('/{id}/pdf', name: 'app_historique_traitement_pdf', methods: ['GET'])]
public function exportPdf(HistoriqueTraitement $historiqueTraitement, SuivieMedicalRepository $suivieMedicalRepository, PdfService $pdfService): Response
{
    // Récupérer les suivis médicaux associés
    $suivieMedicals = $suivieMedicalRepository->findBy(['id_historique' => $historiqueTraitement]);
    
    // Générer le HTML du bilan
    $html = $this->renderView('historique_traitement/pdf_template.html.twig', [
        'historique_traitement' => $historiqueTraitement,
        'suivie_medicals' => $suivieMedicals,
    ]);
    
    // Nom de fichier personnalisé
    $filename = 'bilan_' . $historiqueTraitement->getNom() . '_' . $historiqueTraitement->getPrenom() . '.pdf';
    
    // Générer et envoyer le PDF
    return $pdfService->generatePdf($html, $filename);
}
    }

