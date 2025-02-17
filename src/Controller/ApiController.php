<?php

namespace App\Controller;

use App\Entity\Planning;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api/get-planning-hours/{id}', name: 'api_get_planning_hours', methods: ['GET'])]
    public function getPlanningHours(Planning $planning): JsonResponse
    {
        return new JsonResponse([
            'heuredebut' => $planning->getHeuredebut()->format('H:i'),
            'heurefin' => $planning->getHeurefin()->format('H:i'),
        ]);
    }
}
