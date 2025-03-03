<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Event;
use App\Form\ReservationType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use App\Service\ReservationMailerService;
#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    private $entityManager;
    private $eventRepository;
    private $logger;

    // Injection des services Doctrine via le constructeur
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->eventRepository = $entityManager->getRepository(Event::class);
        $this->logger = $logger;
    }

    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/indexback', name: 'app_reservation_indexback', methods: ['GET'])]
    public function indexback(ReservationRepository $reservationRepository): Response
    {
        // Récupérer toutes les réservations pour l'affichage dans le back-office
        return $this->render('reservation/indexback.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/reservation/new/{id}', name: 'reservation_new')]
    public function new(int $id, Request $request, ReservationMailerService $mailerService): Response
    {
        // Récupérer l'événement avec l'ID fourni
        $event = $this->eventRepository->find($id);
        
        // Vérifier si l'événement existe
        if (!$event) {
            throw $this->createNotFoundException("L'événement avec l'ID $id n'existe pas.");
        }
        
        // Créer une nouvelle réservation
        $reservation = new Reservation();
        $reservation->setEvent($event);
        
        // Créer le formulaire
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
        
        // Vérifier si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($reservation);
            $this->entityManager->flush();
            
            // Envoyer l'email de confirmation avec le PDF
            try {
                $mailerService->sendConfirmationEmail($reservation);
                $this->addFlash('success', 'Réservation effectuée avec succès ! Un email de confirmation vous a été envoyé.');
            } catch (\Exception $e) {
                // En cas d'erreur d'envoi d'email, on journalise et on affiche un message différent
                $this->logger->error('Erreur lors de l\'envoi de l\'email de confirmation', [
                    'reservation_id' => $reservation->getId(),
                    'error' => $e->getMessage()
                ]);
                $this->addFlash('success', 'Réservation effectuée avec succès ! Vous pourrez télécharger votre billet dans votre espace personnel.');
            }
            
            // Redirection après la réservation
            return $this->redirectToRoute('app_reservation_index'); // Redirection vers la liste des réservations dans le back-office
        }
        
        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }
    #[Route('/reservation/{id}', name: 'reservation_show')]
    public function show(Reservation $reservation)
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/indexback', name: 'app_reservation_showback', methods: ['GET'])]
    public function showback(Reservation $reservation): Response
    {
        return $this->render('reservation/showback.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_index', ['id' => $reservation->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reservation_indexback', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/admin/reservations', name: 'app_reservation_indexr')]
public function recherche(Request $request, EntityManagerInterface $entityManager): Response
{
    $keyword = $request->query->get('keyword');

    if ($keyword) {
        // Recherche par nom de réservation ou email (utilisant LIKE pour une recherche partielle)
        $reservations = $entityManager
            ->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->where('r.nomreserv LIKE :keyword')
            ->orWhere('r.mail LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    } else {
        // Récupérer toutes les réservations
        $reservations = $entityManager
            ->getRepository(Reservation::class)
            ->findAll();
    }

    return $this->render('reservation/indexback.html.twig', [
        'reservations' => $reservations,
        'searchKeyword' => $keyword
    ]);
}
#[Route('/admin/reservations', name: 'app_reservation_indexr')]
public function recherchef(Request $request, EntityManagerInterface $entityManager): Response
{
    $keyword = $request->query->get('keyword');

    if ($keyword) {
        // Recherche par nom de réservation ou email (utilisant LIKE pour une recherche partielle)
        $reservations = $entityManager
            ->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->where('r.nomreserv LIKE :keyword')
            ->orWhere('r.mail LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    } else {
        // Récupérer toutes les réservations
        $reservations = $entityManager
            ->getRepository(Reservation::class)
            ->findAll();
    }

    return $this->render('reservation/index.html.twig', [
        'reservations' => $reservations,
        'searchKeyword' => $keyword
    ]);
}
#[Route('/{id}/pdf', name: 'generate_pdf_reservation')]
public function generatePdf(int $id, ReservationRepository $reservationRepository, BuilderInterface $qrBuilder): Response
{
    try {
        // 1. Récupération des données de base
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            throw new \Exception('Réservation non trouvée');
        }
        
        $event = $reservation->getEvent();
        if (!$event) {
            throw new \Exception('Événement non trouvé');
        }
        
        $qrData = "Réservation #" . $id . " pour " . $reservation->getNomreserv() . 
        " | Événement: " . $event->getTitre() . 
        " | Lieu: " . $event->getLieu() . 
        " | Date: " . $event->getDateevent()->format('Y-m-d');
        $qrResult = $qrBuilder
            ->size(150)
            ->margin(10)
            ->data($qrData)
            ->build();
        $qrCodeImage = $qrResult->getDataUri();
        
        // 3. Création d'un HTML minimal
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Réservation #$id</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .qrcode { text-align: center; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Confirmation de réservation</h1>
                </div>
                
                <p><strong>Nom:</strong> " . $reservation->getNomreserv() . "</p>
                <p><strong>Email:</strong> " . $reservation->getMail() . "</p>
                <p><strong>Nombre de personnes:</strong> " . $reservation->getNbrpersonne() . "</p>
                <p><strong>Événement:</strong> " . $event->getTitre() . "</p>
                
                <div class='qrcode'>
                    <img src='" . $qrCodeImage . "' alt='QR Code'>
                    <p>Scannez ce code pour vérifier votre réservation</p>
                </div>
            </div>
        </body>
        </html>";
        
        // 4. Configuration minimale de Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        // 5. Génération du PDF
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // 6. Sortie directe du PDF
        $pdfOutput = $dompdf->output();
        
        // 7. Création d'une réponse HTTP basique
        $response = new Response($pdfOutput);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="reservation-' . $id . '.pdf"');
        
        return $response;
        
    } catch (\Exception $e) {
        // 8. Journalisation détaillée de l'erreur
        dump("ERREUR PDF: " . $e->getMessage());
        error_log("ERREUR PDF: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        
        // 9. Retour d'une réponse textuelle pour le débogage
        return new Response(
            "Une erreur est survenue pendant la génération du PDF: " . $e->getMessage(),
            500
        );
    }
}


}


