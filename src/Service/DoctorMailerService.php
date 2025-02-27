<?php

namespace App\Service;
use Psr\Log\LoggerInterface;

use App\Entity\Utilisateur;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DoctorMailerService
{
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, UrlGeneratorInterface $urlGenerator, LoggerInterface $logger) {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    public function sendVerificationPendingEmail(Utilisateur $doctor): void
    {
        $this->logger->info('Sending verification pending email to ' . $doctor->getEmail());

        try {
            $email = (new Email())
                ->from('chronoserena@gmail.com')
                ->to($doctor->getEmail())
                ->subject('Compte Médecin en Cours de Vérification')
                ->html(
                    '<p>Cher Dr. ' . htmlspecialchars($doctor->getPrenom()) . ' ' . htmlspecialchars($doctor->getNom()) . ',</p>' .
                    '<p>Nous vous remercions de votre inscription sur notre plateforme. Votre compte est actuellement en cours de vérification par notre équipe administrative.</p>' .
                    '<p>Ce processus peut prendre jusqu\'à 48 heures ouvrables. Vous recevrez un email dès que votre compte sera vérifié.</p>' .
                    '<p>Cordialement,<br>L\'équipe administrative</p>'
                );
    
            $this->mailer->send($email);
            $this->logger->info('Verification pending email sent to ' . $doctor->getEmail());
        } catch (\Exception $e) {
            $this->logger->error('Failed to send verification pending email: ' . $e->getMessage());
        }
    }

    public function sendVerificationApprovedEmail(Utilisateur $doctor): void
    {
        $loginUrl = $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $email = (new Email())
            ->from('chronoserena@gmail.com')
            ->to($doctor->getEmail())
            ->subject('Compte Médecin Vérifié - Vous pouvez maintenant vous connecter')
            ->html(
                '<p>Cher Dr. ' . htmlspecialchars($doctor->getPrenom()) . ' ' . htmlspecialchars($doctor->getNom()) . ',</p>' .
                '<p>Nous sommes heureux de vous informer que votre compte a été vérifié avec succès.</p>' .
                '<p>Vous pouvez maintenant vous connecter à votre compte en utilisant vos identifiants à l\'adresse suivante:</p>' .
                '<p><a href="' . $loginUrl . '">Se connecter</a></p>' .
                '<p>Cordialement,<br>L\'équipe administrative</p>'
            );

        $this->mailer->send($email);
    }
}