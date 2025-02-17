<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\Utilisateur;
use App\Enum\UserRole;
use App\Entity\Demande;
use App\Form\DemandeType;
use App\Repository\DemandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/demande')]
final class DemandeController extends AbstractController
{
    #[Route(name: 'app_demande_index', methods: ['GET'])]
    public function index(DemandeRepository $demandeRepository): Response
    {
        return $this->render('demande/index.html.twig', [
            'demandes' => $demandeRepository->findAll(),
        ]);
    }
    #[Route('/dashboard', name: 'app_demande_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('demande/dashboard.html.twig');
    }

    #[Route('/new', name: 'app_demande_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $demande = new Demande();
        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Automatically assign the logged-in user as the Patient
            $demande->setPatient($this->getUser());
            
            $entityManager->persist($demande);
            $entityManager->flush();

            return $this->redirectToRoute('app_demande_dashboard', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demande/new.html.twig', [
            'demande' => $demande,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_demande_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Demande $demande): Response
    {
        return $this->render('demande/show.html.twig', [
            'demande' => $demande,
        ]);
    }
    #[Route('/my', name: 'app_demande_my', methods: ['GET'])]
    public function myDemande(Request $request, DemandeRepository $demandeRepository): Response
    {
        $user = $this->getUser();
        //dump('Current User:', $user);

        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $userId = $request->request->get('id') ?? $user->getId();
        //dump('User ID:', $userId);
        $latestDemande = $demandeRepository->findDemandeByPatientId($userId);
        //dump("latest demande id:" ,$latestDemande);
        
        if (!$latestDemande) {
            $this->addFlash('notice', 'Aucune demande trouvée.');
            return $this->redirectToRoute('app_demande_new');
        }

        
        return $this->redirectToRoute('app_demande_show', [
            'id' => $latestDemande->getId()
        ]);
    }



    #[Route('/{id}/edit', name: 'app_demande_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Optionally, you might restrict editing if a Recommendation already exists.
            $entityManager->flush();

            return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demande/edit.html.twig', [
            'demande' => $demande,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_demande_delete', methods: ['POST'])]
    public function delete(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$demande->getId(), $request->request->get('_token'))) {
            $entityManager->remove($demande);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/pending', name: 'app_demande_pending', methods: ['GET'])]
    public function pending(DemandeRepository $demandeRepository): Response
    {
        // Fetch pending demandes (i.e. those without a recommendation)
        $pendingDemandes = $demandeRepository->findPendingDemandes();

        return $this->render('demande/pending.html.twig', [
            'demandes' => $pendingDemandes,
        ]);
    }

}
