<?php

namespace App\Controller;

use App\Entity\Reminder;
use App\Repository\ReminderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reminder')]
class ReminderController extends AbstractController
{
    #[Route('/', name: 'app_reminder_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('reminder/index.html.twig');
    }

    #[Route('/new-ajax', name: 'app_reminder_new_ajax', methods: ['POST'])]
    public function newAjax(Request $request, ReminderRepository $reminderRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                throw new \Exception('Données invalides');
            }

            $reminder = new Reminder();
            $reminder->setTitle($data['title']);
            $reminder->setDateTime(new \DateTime($data['date'] . ' ' . $data['time']));
            $reminder->setNotifyBefore($data['notifyBefore']);
            $reminder->setRepeatType($data['repeatType'] ?? 'none');
            $reminder->setSoundEnabled(isset($data['soundEnabled']) ? (bool)$data['soundEnabled'] : true);

            $reminderRepository->save($reminder, true);

            return $this->json([
                'success' => true,
                'reminder' => [
                    'id' => $reminder->getId(),
                    'title' => $reminder->getTitle(),
                    'start' => $reminder->getDateTime()->format('c'),
                    'notifyBefore' => $reminder->getNotifyBefore(),
                    'repeatType' => $reminder->getRepeatType()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/api/reminders', name: 'api_reminders', methods: ['GET'])]
    public function apiReminders(Request $request, ReminderRepository $reminderRepository): JsonResponse
    {
        $month = $request->query->get('month');
        $year = $request->query->get('year');
        
        $startDate = new \DateTime("$year-$month-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        
        $reminders = $reminderRepository->findByDateRange($startDate, $endDate);
        $events = [];

        foreach ($reminders as $reminder) {
            $events[] = [
                'id' => $reminder->getId(),
                'title' => $reminder->getTitle(),
                'start' => $reminder->getDateTime()->format('c'),
                'end' => $reminder->getDateTime()->modify('+30 minutes')->format('c'),
                'extendedProps' => [
                    'notifyBefore' => $reminder->getNotifyBefore(),
                    'repeatType' => $reminder->getRepeatType()
                ]
            ];
        }

        return $this->json($events);
    }
    
    #[Route('/notifications', name: 'app_reminder_notifications', methods: ['GET'])]
    public function notifications(ReminderRepository $reminderRepository): Response
    {
        // Récupérer la date d'aujourd'hui
        $today = new \DateTime();
        $today->setTime(0, 0);
        
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');    
        
        // Récupérer les rappels du jour
        $reminders = $reminderRepository->createQueryBuilder('r')
            ->where('r.dateTime >= :today')
            ->andWhere('r.dateTime < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('r.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
            
        return $this->render('reminder/notifications.html.twig', [
            'reminders' => $reminders
        ]);
    }
    
    #[Route('/notifications-json', name: 'app_reminder_notifications_json')]
    public function notificationsJson(ReminderRepository $reminderRepository): JsonResponse
    {
        $today = new \DateTime('now');
        $today->setTime(0, 0, 0);
        
        $endDate = clone $today;
        $endDate->modify('+7 days');
        $endDate->setTime(23, 59, 59);
        
        $reminders = $reminderRepository->findByDateRange($today, $endDate);
        
        $data = array_map(function($reminder) {
            // Calculer la date et l'heure de notification en fonction des préférences
            $notificationTime = clone $reminder->getDateTime();
            $minutesBefore = intval($reminder->getNotifyBefore());
            $notificationTime->modify("-{$minutesBefore} minutes");
                    return [
            'id' => $reminder->getId(),
            'title' => $reminder->getTitle(),
            'dateTime' => $reminder->getDateTime()->format('Y-m-d H:i:s'),
            'notifyBefore' => $reminder->getNotifyBefore(),
            'notifyAt' => $notificationTime->format('Y-m-d H:i:s'),
            'repeatType' => $reminder->getRepeatType(),
            'formattedDate' => $reminder->getDateTime()->format('d/m/Y'),
            'remainingTime' => $this->calculateRemainingTime($reminder),
            'soundEnabled' => $reminder->isSoundEnabled() 
        ];
                }, $reminders);
        
        return new JsonResponse($data);
    }
    
    /**
     * Calculer le temps restant avant un rappel
     */
    private function calculateRemainingTime(Reminder $reminder): array
    {
        $now = new \DateTime('now');
        $eventTime = $reminder->getDateTime();
        
        // Calculer la différence en secondes
        $diff = $eventTime->getTimestamp() - $now->getTimestamp();
        
        if ($diff < 0) {
            return [
                'status' => 'past',
                'text' => 'Passé'
            ];
        }
        
        // Convertir en jours, heures, minutes
        $days = floor($diff / (60 * 60 * 24));
        $diff -= $days * (60 * 60 * 24);
        
        $hours = floor($diff / (60 * 60));
        $diff -= $hours * (60 * 60);
        
        $minutes = floor($diff / 60);
        
        // Formater le texte selon le temps restant
        if ($days > 0) {
            $text = sprintf('%d jour%s', $days, $days > 1 ? 's' : '');
        } elseif ($hours > 0) {
            $text = sprintf('%d heure%s', $hours, $hours > 1 ? 's' : '');
        } else {
            $text = sprintf('%d minute%s', $minutes, $minutes > 1 ? 's' : '');
        }
        
        return [
            'status' => 'upcoming',
            'text' => 'Dans ' . $text
        ];
    }
    
    /**
     * Endpoint pour vérifier les notifications imminentes
     */
    #[Route('/check-notifications', name: 'app_reminder_check_notifications', methods: ['GET'])]
    public function checkNotifications(ReminderRepository $reminderRepository): JsonResponse
    {
        $now = new \DateTime('now');
        $fiveMinutesLater = clone $now;
        $fiveMinutesLater->modify('+5 minutes');
        
        // Rechercher tous les rappels dont la notification est imminente
        $upcomingNotifications = [];
        $reminders = $reminderRepository->findByDateRange($now, $fiveMinutesLater);
        
        foreach ($reminders as $reminder) {
            $notificationTime = clone $reminder->getDateTime();
            $minutesBefore = intval($reminder->getNotifyBefore());
            $notificationTime->modify("-{$minutesBefore} minutes");
            
            // Si l'heure de notification est passée mais dans les 5 dernières minutes
            if ($notificationTime <= $now && $notificationTime >= $now->modify('-5 minutes')) {
                $upcomingNotifications[] = [
                    'id' => $reminder->getId(),
                    'title' => $reminder->getTitle(),
                    'dateTime' => $reminder->getDateTime()->format('Y-m-d H:i:s'),
                    'notifyBefore' => $reminder->getNotifyBefore()
                ];
            }
        }
        
        return new JsonResponse($upcomingNotifications);
    }

    
    #[Route('/editReminder/{id}', name: 'app_reminder_editReminder', methods: ['GET', 'POST'])]
    public function editReminder(Request $request, Reminder $reminder, ReminderRepository $reminderRepository): Response
    {
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $dateTime = new \DateTime($request->request->get('dateTime'));
            $notifyBefore = $request->request->get('notifyBefore');
            
            $reminder->setTitle($title);
            $reminder->setDateTime($dateTime);
            $reminder->setNotifyBefore($notifyBefore);
            
            $reminderRepository->save($reminder, true);
            
            // Rediriger vers la page d'index après l'enregistrement
            return $this->redirectToRoute('app_reminder_notifications');
        }
        
        return $this->render('reminder/editReminder.html.twig', [
            'reminder' => $reminder
        ]);
    }

    #[Route('/delete/{id}', name: 'app_reminder_delete', methods: ['POST'])]
    public function delete(Request $request, Reminder $reminder, ReminderRepository $reminderRepository): JsonResponse
    {
        try {
            $reminderRepository->remove($reminder, true);
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la suppression'], 500);
        }
    }
    
    
}