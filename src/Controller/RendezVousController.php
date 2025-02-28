<?php

namespace App\Controller;

use App\Entity\Planning;
use App\Entity\RendezVous;
use App\Form\RendezVousType;
use App\Repository\RendezVousRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    #[Route('/rendez/vous/{id}/qr-code', name: 'app_rendez_vous_qr_code', methods: ['GET'])]
    public function generateQrCode(RendezVous $rendezVous): Response
    {
        // Prepare QR code data
        $qrData = sprintf(
            "Rendez-vous ID: %d\nDate et Heure: %s\nStatut: %s\nDescription: %s\nPlanning: %s - %s",
            $rendezVous->getId(),
            $rendezVous->getDateheure()->format('d/m/Y H:i'),
            $rendezVous->getStatut(),
            $rendezVous->getDescription(),
            $rendezVous->getPlanning()->getJour(),
            $rendezVous->getPlanning()->getHeuredebut()->format('H:i') . " - " . $rendezVous->getPlanning()->getHeurefin()->format('H:i')
        );

        // Create the QR Code instance
        $qrCode = new QrCode($qrData);

        // Create PNG writer
        $writer = new PngWriter();
        $qrCodeString = $writer->write($qrCode)->getString();

        // Return as a PNG response
        return new StreamedResponse(function () use ($qrCodeString) {
            echo $qrCodeString;
        }, Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }




    #[Route('/new', name: 'app_rendez_vous_new', methods: ['GET', 'POST'])]
    public function new(
        Request                $request,
        EntityManagerInterface $entityManager,
        RendezVousRepository   $rendezVousRepository,
        EmailService           $emailService
    ): Response
    {
        $rendezVous = new RendezVous();
        $form = $this->createForm(RendezVousType::class, $rendezVous);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = [];

            if ($rendezVous->getDateheure() < new \DateTime()) {
                $errors[] = "La date du rendez-vous doit être dans le futur.";
            }

            $statutsValides = ['Confirmé', 'Annulé', 'En attente'];
            if (!in_array($rendezVous->getStatut(), $statutsValides, true)) {
                $errors[] = "Statut invalide. Veuillez sélectionner 'Confirmé', 'Annulé' ou 'En attente'.";
            }

            if (strlen($rendezVous->getDescription()) < 5) {
                $errors[] = "La description doit contenir au moins 5 caractères.";
            }

            if ($rendezVous->getPlanning() === null) {
                $errors[] = "Veuillez sélectionner un planning.";
            }

            $existingRendezVous = $rendezVousRepository->findOneBy([
                'dateheure' => $rendezVous->getDateheure(),
                'planning' => $rendezVous->getPlanning(),
            ]);

            if ($existingRendezVous !== null) {
                $errors[] = "Un rendez-vous avec la même date et planning existe déjà.";
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_rendez_vous_new');
            }

            $entityManager->persist($rendezVous);
            $entityManager->flush();

            $adminEmail = 'mohamed.jaffel08@gmail.com';
            $subject = 'Nouveau Rendez-Vous Créé';
            $body = sprintf(
                'Un nouveau rendez-vous a été créé:<br><br>
            <strong>Description:</strong> %s<br>
            <strong>Date et Heure:</strong> %s<br>
            <strong>Statut:</strong> %s<br>
            <strong>Planning:</strong> %s',
                $rendezVous->getDescription(),
                $rendezVous->getDateheure()->format('Y-m-d H:i'),
                $rendezVous->getStatut(),
                $rendezVous->getPlanning()->getJour()
            );

            try {
                $emailService->sendEmail($adminEmail, $subject, $body);
                $this->addFlash('success', 'Le rendez-vous a été créé avec succès.');
            } catch (TransportExceptionInterface $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email: ' . $e->getMessage());
                throw $e;
            }

            return $this->redirectToRoute('app_rendez_vous_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('rendez_vous/new.html.twig', [
            'form' => $form->createView(),
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
        if ($this->isCsrfTokenValid('delete' . $rendezVou->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($rendezVou);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rendez_vous_index', [], Response::HTTP_SEE_OTHER);
    }
}
