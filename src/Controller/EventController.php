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
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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
                // Gestion des images
                $imageFiles = $request->files->get('event')['images'];
                
                // Vérifie si des images ont été téléchargées
                if (empty($imageFiles)) {
                    $this->addFlash('error', 'Veuillez télécharger au moins une image.');
                    return $this->redirectToRoute('app_event_new');
                }
        
                $imageNames = [];
                foreach ($imageFiles as $imageFile) {
                    if ($imageFile) {
                        $newFilename = uniqid().'.'.$imageFile->guessExtension();
                        
                        try {
                            $imageFile->move(
                                $this->getParameter('images_directory'),
                                $newFilename
                            );
                            $imageNames[] = $newFilename;
                        } catch (FileException $e) {
                            $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                            return $this->redirectToRoute('app_event_new');
                        }
                    }
                }
        
                // Sauvegarde les noms des images dans l'entité Event
                $event->setImage(implode(',', $imageNames));
                
                // Sauvegarde l'événement dans la base de données
                $entityManager->persist($event);
                $entityManager->flush();
        
                return $this->redirectToRoute('app_event_indexback', [], Response::HTTP_SEE_OTHER);
            }
        
            return $this->render('event/new.html.twig', [
                'form' => $form->createView(),
                'event' => $event,
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
          
            foreach ($event->getReservations() as $reservation) {
                $entityManager->remove($reservation);
            }
    
            $entityManager->remove($event);
            $entityManager->flush();
        }
    
        return $this->redirectToRoute('app_event_indexback', [], Response::HTTP_SEE_OTHER);
    }
    
   
    #[Route('/admin/events', name: 'app_event_indexr')]
    public function recherche(Request $request, EntityManagerInterface $entityManager): Response
    {
        $keyword = $request->query->get('keyword');
    
        if ($keyword) {
            // Recherche par titre (utilisant LIKE pour une recherche partielle)
            $events = $entityManager
                ->getRepository(Event::class)
                ->createQueryBuilder('e')
                ->where('e.titre LIKE :keyword')
                ->setParameter('keyword', '%' . $keyword . '%')
                ->orderBy('e.id', 'ASC')
                ->getQuery()
                ->getResult();
        } else {
            // Récupérer tous les événements
            $events = $entityManager
                ->getRepository(Event::class)
                ->findAll();
        }
    
        return $this->render('event/indexback.html.twig', [
            'events' => $events,
            'searchKeyword' => $keyword // Ajout du keyword dans le template
        ]);
    }
    #[Route('/admin/events/search', name: 'app_event_search_by_description')]
public function rechercheParDescription(Request $request, EntityManagerInterface $entityManager): Response
{
    $keyword = $request->query->get('keyword');

    if ($keyword) {
        // Recherche par description (LIKE pour une recherche partielle)
        $events = $entityManager
            ->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.discription LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    } else {
        // Récupérer tous les événements
        $events = $entityManager
            ->getRepository(Event::class)
            ->findAll();
    }

    return $this->render('event/indexback.html.twig', [
        'events' => $events,
        'searchKeyword' => $keyword // Ajout du mot-clé dans le template
    ]);
}
#[Route('/admin/events/search', name: 'app_event_search_by_description')]
public function rechercheParDescriptionf(Request $request, EntityManagerInterface $entityManager): Response
{
    $keyword = $request->query->get('keyword');

    if ($keyword) {
        // Recherche par description (LIKE pour une recherche partielle)
        $events = $entityManager
            ->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.discription LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    } else {
        // Récupérer tous les événements
        $events = $entityManager
            ->getRepository(Event::class)
            ->findAll();
    }

    return $this->render('event/index.html.twig', [
        'events' => $events,
        'searchKeyword' => $keyword // Ajout du mot-clé dans le template
    ]);
}

 
}
