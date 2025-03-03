<?php

namespace App\Service;

use App\Entity\Reservation;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReservationMailerService
{
    private $mailer;
    private $urlGenerator;
    private $logger;

    public function __construct(
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    public function sendConfirmationEmail(Reservation $reservation): void
    {
        try {
            $event = $reservation->getEvent();
            
            // Générer l'URL du PDF
            $pdfUrl = $this->urlGenerator->generate(
                'generate_pdf_reservation',
                ['id' => $reservation->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            
            // Créer l'email
            $email = (new Email())
                ->from('chronoserena@gmail.com')
                ->to($reservation->getMail())
                ->subject('Confirmation de votre réservation - ' . $event->getTitre())
                ->html($this->getEmailTemplate($reservation, $pdfUrl));
            
            // Envoyer l'email
            $this->mailer->send($email);
            
            $this->logger->info('Email de confirmation envoyé avec succès', [
                'reservation_id' => $reservation->getId(),
                'email' => $reservation->getMail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Échec de l\'envoi de l\'email de confirmation', [
                'reservation_id' => $reservation->getId(),
                'exception' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    private function getEmailTemplate(Reservation $reservation, string $pdfUrl): string
    {
        $event = $reservation->getEvent();
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Confirmation de réservation</title>
            <style>
                body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4b6cb7; color: white; padding: 10px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { text-align: center; font-size: 12px; margin-top: 20px; color: #777; }
                .button { display: inline-block; padding: 10px 20px; background-color: #4b6cb7; color: white; 
                          text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Confirmation de réservation</h1>
                </div>
                
                <div class='content'>
                    <p>Bonjour " . $reservation->getNomreserv() . ",</p>
                    
                    <p>Nous vous confirmons votre réservation pour l'événement suivant :</p>
                    
                    <ul>
                        <li><strong>Événement :</strong> " . $event->getTitre() . "</li>
                        <li><strong>Date :</strong> " . $event->getDateevent()->format('d/m/Y') . "</li>
                        <li><strong>Lieu :</strong> " . $event->getLieu() . "</li>
                        <li><strong>Nombre de personnes :</strong> " . $reservation->getNbrpersonne() . "</li>
                    </ul>
                    
                    <p>Vous pouvez télécharger votre billet au format PDF en cliquant sur le lien suivant :</p>
                    
                    <p style='text-align: center;'>
                        <a href='" . $pdfUrl . "' class='button'>Télécharger votre billet</a>
                    </p>
                    
                    <p>Nous vous remercions pour votre réservation et nous nous réjouissons de vous accueillir !</p>
                </div>
                
                <div class='footer'>
                    <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}