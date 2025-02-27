<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Service\SmsGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SmsController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('sms/index.html.twig', ['smsSent' => false]);
    }

    #[Route('/sendSms', name: 'send_sms', methods: ['POST'])]
    public function sendSms(Request $request, SmsGenerator $smsGenerator): Response
    {
        $number = $request->request->get('number');
        $name = $request->request->get('name');
        $text = $request->request->get('text');
        
        $number_test = $_ENV['twilio_to_number'];
        
        $smsGenerator->sendOrderConfirmation($number_test, $name, $text);
        
        return $this->render('produit/index.html.twig', ['smsSent' => true]);
    }

    #[Route('/send-order-confirmation/{id}', name: 'send_order_confirmation')]
    public function sendOrderConfirmation(
        Commande $commande, 
        SmsGenerator $smsGenerator,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            // Verify the order exists and is paid
            if ($commande->getPaymentStatus() !== 'completed') {
                throw new \Exception('Order is not completed');
            }

            // Send the confirmation SMS
            $smsGenerator->sendOrderConfirmation($commande);

            $this->addFlash('success', 'Order confirmation SMS sent successfully');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send order confirmation SMS: ' . $e->getMessage());
        }

        // Redirect back to the order detail page
        return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
    }

    #[Route('/webhook/sms-status', name: 'sms_status_webhook', methods: ['POST'])]
    public function smsStatusWebhook(Request $request): Response
    {
        // Get the SMS status from Twilio webhook
        $messageStatus = $request->request->get('MessageStatus');
        $messageSid = $request->request->get('MessageSid');

        // Log the status (you should implement proper logging)
        error_log("SMS {$messageSid} status: {$messageStatus}");

        return new Response('Webhook received', Response::HTTP_OK);
    }

    #[Route('/resend-order-sms/{id}', name: 'resend_order_sms', methods: ['POST'])]
    public function resendOrderSms(
        Commande $commande,
        SmsGenerator $smsGenerator,
        Request $request
    ): Response {
        if (!$this->isCsrfTokenValid('resend-sms'.$commande->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        try {
            $smsGenerator->sendOrderConfirmation($commande);
            $this->addFlash('success', 'Order confirmation SMS resent successfully');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to resend SMS: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
    }
}