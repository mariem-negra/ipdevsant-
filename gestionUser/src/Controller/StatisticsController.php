<?php
// src/Controller/StatisticsController.php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatisticsController extends AbstractController
{
    #[Route('/admin/statistics', name: 'app_admin_statistics')]
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        // Récupérer les statistiques
        $stats = $utilisateurRepository->getStatsByRole();
        $user = $this->getUser();

        return $this->render('admin/statistics.html.twig', [
            'stats' => $stats,
            'user' => $user,

        ]);
    }
}