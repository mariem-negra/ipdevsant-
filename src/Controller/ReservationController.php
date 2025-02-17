<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Event;
use App\Form\ReservationType;
<<<<<<< HEAD
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
=======
use App\Repository\ReservationRepository;
use App\Repository\EventRepository;
>>>>>>> ff5014e (third commit)
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
<<<<<<< HEAD
#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    private $entityManager;
    private $eventRepository;

    // Injection des services Doctrine via le constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->eventRepository = $this->entityManager->getRepository(Event::class);
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
    public function new(int $id, Request $request)
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

            // Redirection après la réservation
            $this->addFlash('success', 'Réservation effectuée avec succès !');
            return $this->redirectToRoute('app_reservation_index'); // Redirection vers la liste des réservations dans le back-office
=======

#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private ReservationRepository $reservationRepository
    ) {
    }

    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('reservation/index.html.twig', [
            'reservations' => $this->reservationRepository->findAll(),
        ]);
    }
    
    #[Route('/indexback', name: 'app_reservation_indexback', methods: ['GET'])]
    public function indexback(): Response
    {
        return $this->render('reservation/indexback.html.twig', [
            'reservations' => $this->reservationRepository->findAll(),
        ]);
    }

    #[Route('/new/{eventId}', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $eventId): Response
    {
        // Convertir l'ID en entier
        $eventIdInt = (int) $eventId;
        
        // Récupérer l'événement
        $event = $this->eventRepository->find($eventIdInt);
        
        if (!$event) {
            throw $this->createNotFoundException("L'événement avec l'ID $eventId n'existe pas.");
        }

        $reservation = new Reservation();
        $reservation->setEvent($event);

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($reservation);
                $this->entityManager->flush();

                $this->addFlash('success', 'Votre réservation a été effectuée avec succès !');
                return $this->redirectToRoute('app_reservation_index', ['id' => $eventId]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la réservation.');
            }
>>>>>>> ff5014e (third commit)
        }

        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

<<<<<<< HEAD
    #[Route('/reservation/{id}', name: 'reservation_show')]
    public function show(Reservation $reservation)
    {
=======
    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $reservationId = (int) $id;
        $reservation = $this->reservationRepository->find($reservationId);
        
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

>>>>>>> ff5014e (third commit)
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }
<<<<<<< HEAD

    #[Route('/{id}/indexback', name: 'app_reservation_showback', methods: ['GET'])]
    public function showback(Reservation $reservation): Response
    {
=======
    
    #[Route('/{id}/indexback', name: 'app_reservation_showback', methods: ['GET'])]
    public function showback(string $id): Response
    {
        $reservationId = (int) $id;
        $reservation = $this->reservationRepository->find($reservationId);
        
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

>>>>>>> ff5014e (third commit)
        return $this->render('reservation/showback.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
<<<<<<< HEAD
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
=======
    public function edit(Request $request, string $id): Response
    {
        $reservationId = (int) $id;
        $reservation = $this->reservationRepository->find($reservationId);
        
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

>>>>>>> ff5014e (third commit)
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
<<<<<<< HEAD
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_index', ['id' => $reservation->getId()], Response::HTTP_SEE_OTHER);
=======
            try {
                $this->entityManager->flush();
                $this->addFlash('success', 'Réservation mise à jour avec succès');
                return $this->redirectToRoute('app_reservation_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour de la réservation');
            }
>>>>>>> ff5014e (third commit)
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
<<<<<<< HEAD
            'form' => $form->createView(),
=======
            'form' => $form,
>>>>>>> ff5014e (third commit)
        ]);
    }

    #[Route('/{id}/delete', name: 'app_reservation_delete', methods: ['POST'])]
<<<<<<< HEAD
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reservation_indexback', [], Response::HTTP_SEE_OTHER);
    }
}
=======
    public function delete(Request $request, string $id): Response
    {
        $reservationId = (int) $id;
        $reservation = $this->reservationRepository->find($reservationId);
        
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $this->entityManager->remove($reservation);
                $this->entityManager->flush();
                $this->addFlash('success', 'Réservation supprimée avec succès');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression de la réservation');
            }
        }

        return $this->redirectToRoute('app_reservation_index');
    }

    #[Route('/{id}/deleteback', name: 'app_reservation_deleteback', methods: ['POST'])]
    public function deleteback(Request $request, string $id): Response
    {
        $reservationId = (int) $id;
        $reservation = $this->reservationRepository->find($reservationId);
        
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $this->entityManager->remove($reservation);
                $this->entityManager->flush();
                $this->addFlash('success', 'Réservation supprimée avec succès');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression de la réservation');
            }
        }

        return $this->redirectToRoute('app_reservation_indexback');
    }
}
>>>>>>> ff5014e (third commit)
