<?php
namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
class MapController extends AbstractController
{
    #[Route('/mapb', name: 'app_mapb')]
    public function map(EventRepository $eventRepository): Response
    {
        $event = $eventRepository->findAll();
        $markers = [];
        
        foreach ($event as $ev) {
            $markers[] = [
                'latitude' => $ev->getLatitude(),
                'longitude' => $ev->getLongitude()
            ];
        }
        
        return $this->render('map/mapb.html.twig', [
            'markers' => $markers,
        ]);
    }
    #[Route('/mapf', name: 'app_map')]
    public function mapf(EventRepository $eventRepository): Response
    {
        $event = $eventRepository->findAll();
        $markers = [];
        
        foreach ($event as $ev) {
            $markers[] = [
                'latitude' => $ev->getLatitude(),
                'longitude' => $ev->getLongitude()
            ];
        }
        
        return $this->render('map/mapf.html.twig', [
            'markers' => $markers,
        ]);
    }
    #[Route('/delete-event/{id}', name: 'delete_event', methods: ['DELETE'])]
    public function deleteEvent(int $id, EventRepository $eventRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $event = $eventRepository->find($id);

        if (!$event) {
            return new JsonResponse(['message' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        // Remove associated reservations before deleting event
        foreach ($event->getReservations() as $reservation) {
            $entityManager->remove($reservation);
        }

        $entityManager->remove($event);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Event deleted successfully',
            'id' => $id // ✅ Send ID so frontend knows which marker to remove
        ]);
    }
}