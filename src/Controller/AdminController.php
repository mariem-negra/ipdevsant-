<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\DoctorMailerService;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/pending-doctors', name: 'admin_pending_doctors', methods: ['GET'])]
    public function pendingDoctors(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $doctors = $em->getRepository(Utilisateur::class)->findBy([
            'role' => UserRole::MEDECIN,
            'isVerified' => false
        ]);

        return $this->render('admin/pending_doctors.html.twig', [
            'doctors' => $doctors,
            'user' => $user,
           
        ]);
    }
    #[Route('/check-verification', name: 'admin_check_verification', methods: ['GET'])]
    public function checkVerification(): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
    
        // If the user is a Médecin and not verified, block access
        if ($user && $user->getRole() === UserRole::MEDECIN && !$user->isVerified()) {
            $this->addFlash('warning', 'Votre compte est en attente de vérification par l\'administrateur.');
            return $this->redirectToRoute('app_login');
        }
    
        // If the user is verified, allow access to the home page
        return $this->redirectToRoute('app_home');
    }
    #[Route('/verify-doctor/{id}', name: 'admin_verify_doctor', methods: ['POST'])]
    public function verifyDoctor(Utilisateur $doctor, EntityManagerInterface $em, DoctorMailerService $doctorMailer): Response
    {
        // Check if the user has the 'ROLE_ADMIN' role
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
    
        $doctor->setIsVerified(true);
        $em->flush();
        $doctorMailer->sendVerificationApprovedEmail($doctor);

        $this->addFlash('success', 'Doctor account has been verified.');
        return $this->redirectToRoute('admin_pending_doctors');
    }
}
