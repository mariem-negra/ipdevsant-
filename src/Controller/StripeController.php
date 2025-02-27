<?php
// src/Controller/StripeController.php
namespace App\Controller;

use App\Entity\Commande;
use App\Service\SmsGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Checkout\Session;

class StripeController extends AbstractController
{
    #[Route('/checkout/success', name: 'checkout_success')]
    public function checkoutSuccess(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get the session ID from the query parameters
        $sessionId = $request->query->get('session_id');
        
        if (!$sessionId) {
            throw $this->createNotFoundException('No session ID provided');
        }
        
        // Find the order by Stripe session ID
        $commande = $entityManager->getRepository(Commande::class)
            ->findOneBy(['stripeSessionId' => $sessionId]);
        
        if (!$commande) {
            throw $this->createNotFoundException('Order not found');
        }
        
        // Mark the order as paid
        $commande->setPaymentStatus('completed');
        $entityManager->flush();
        
        return $this->render('payment/success.html.twig', [
            'commande' => $commande,
        ]);
    }
    
    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function stripeWebhook(
        Request $request, 
        EntityManagerInterface $entityManager,
        SmsGenerator $smsGenerator
    ): Response {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];
        
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            
            // Handle the event
            if ($event->type == 'checkout.session.completed') {
                $session = $event->data->object;
                
                // Find the order by Stripe session ID
                $commande = $entityManager->getRepository(Commande::class)
                    ->findOneBy(['stripeSessionId' => $session->id]);
                
                if ($commande) {
                    // Update order status
                    $commande->setPaymentStatus('completed');
                    $entityManager->flush();
                    
                    // Send SMS confirmation
                    $smsGenerator->sendOrderConfirmation($commande);
                }
            }
            
            return new Response('Webhook received', 200);
        } catch (\Exception $e) {
            return new Response('Webhook error: ' . $e->getMessage(), 400);
        }
    }
}