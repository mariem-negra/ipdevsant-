<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/event')]
final class EventController extends AbstractController
{
    #[Route(name: 'app_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        return $this->render('event/index.html.twig', [
            'events' => $eventRepository->findAll(),
        ]);
    }
    #[Route('/indexback' ,name: 'app_event_indexback', methods: ['GET'])]
    public function indexback(EventRepository $eventRepository): Response
    {
        return $this->render('event/indexback.html.twig', [
            'events' => $eventRepository->findAll(),
        ]);}

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    // Crée un nouvel objet Event
    $event = new Event();
    
    // Crée le formulaire et passe l'objet $event
    $form = $this->createForm(EventType::class, $event);
    
    // Gère la soumission du formulaire
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        // Sauvegarde l'événement dans la base de données
        $entityManager->persist($event);
        $entityManager->flush();

        // Redirige vers la liste des événements
        return $this->redirectToRoute('app_event_indexback', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('event/new.html.twig', [
        'form' => $form->createView(),  // Passe le formulaire à la vue
        'event' => $event,  // Passe l'objet event à la vue
    ]);
}
  
    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }
    #[Route('/{id}/indexback', name: 'app_event_showback', methods: ['GET'])]
    public function showback(Event $event): Response
    {
        return $this->render('event/showback.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_event_indexback', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_indexback', [], Response::HTTP_SEE_OTHER);
    }
}
