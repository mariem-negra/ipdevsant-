<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Enum\UserRole;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(Security $security): Response
    {
        /** @var Utilisateur $user */
        $user = $security->getUser(); // Get the authenticated user

        // If the user is a Médecin and not verified, block access
        if ($user && $user->getRole() === UserRole::MEDECIN && !$user->isVerified()) {
            $this->addFlash('warning', 'Votre compte est en attente de vérification par l\'administrateur.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'user' => $user, // Pass user data to Twig
        ]);
    }
}