<?php
namespace App\Controller;

use App\Entity\Commande;
use App\Service\SmsGenerator;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class PaymentController extends AbstractController
{
    #[Route('/process-payment/{id}', name: 'process_payment')]
    public function processPayment(
        Commande $commande,
        EntityManagerInterface $entityManager,
        string $stripeSK
    ): Response {
        Stripe::setApiKey($stripeSK);

        $total = 0;
        $lineItems = [];
        
        foreach ($commande->getCartItems() as $item) {
            $total += $item->getProduit()->getPrix() * $item->getQuantite();
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item->getProduit()->getNom(),
                        'description' => $item->getProduit()->getDescription() ?? '',
                    ],
                    'unit_amount' => (int)($item->getProduit()->getPrix() * 100),
                ],
                'quantity' => $item->getQuantite(),
            ];
        }

        try {
            // Create Stripe Checkout Session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'customer_email' => $commande->getEmail(),
                'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->generateUrl('payment_cancel', ['id' => $commande->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'metadata' => [
                    'order_id' => $commande->getId()
                ]
            ]);

            // Update order
            $commande->setPaymentStatus('pending');
            $commande->setStripeSessionId($session->id);
            $commande->setTotalAmount($total);
            $entityManager->flush();

            // Redirect to Stripe Checkout
            return $this->redirect($session->url, 303);

        } catch (\Exception $e) {
            error_log('Stripe session creation failed: ' . $e->getMessage());
            $this->addFlash('error', 'Payment initialization failed. Please try again.');
            return $this->redirectToRoute('payment_cancel', ['id' => $commande->getId()]);
        }
    }

    #[Route('/payment/success', name: 'payment_success')]
public function paymentSuccess(
    Request $request,
    EntityManagerInterface $entityManager,
    MailerInterface $mailer,
    SessionInterface $session,
    SmsGenerator $smsGenerator,
    string $stripeSK
): Response {
    try {
        $sessionId = $request->query->get('session_id');
        
        
        error_log('Success handler called with session ID: ' . $sessionId);
        
        // Find order
        $commande = $entityManager->getRepository(Commande::class)
            ->findOneBy(['stripeSessionId' => $sessionId]);
            
        if (!$commande) {
            error_log('Order not found for session: ' . $sessionId);
            throw new \Exception('Order not found');
        }
        
        error_log('Found order ID: ' . $commande->getId() . ' with status: ' . $commande->getPaymentStatus());

        // Verify with Stripe
        Stripe::setApiKey($stripeSK);
        $stripeSession = Session::retrieve($sessionId);
        
        error_log('Stripe reports payment_status as: ' . $stripeSession->payment_status);

        if ($stripeSession->payment_status === 'paid') {
            // Update order status
            $commande->setPaymentStatus('completed');
            $entityManager->flush();
            
            try {
                // Send SMS confirmation
                $smsGenerator->sendOrderConfirmation($commande);
                error_log('SMS confirmation sent successfully for order: ' . $commande->getId());
            } catch (\Exception $e) {
                // Log SMS error but don't disrupt the checkout flow
                error_log('SMS sending failed: ' . $e->getMessage());
            }
            
            // Clear session cart
            $session->remove('panier');
            
            // Redirect to success page
            return $this->render('payment/success.html.twig', [
                'commande' => $commande,
                'orderStatus' => 'completed',
                'orderId' => $commande->getId()
            ]);
        }
        
        error_log('Payment not confirmed as paid by Stripe');
        throw new \Exception('Payment not confirmed as paid');

    } catch (\Exception $e) {
        error_log('Payment success handler error: ' . $e->getMessage());
        return $this->redirectToRoute('payment_cancel', [
            'id' => $commande->getId(),
            'error' => $e->getMessage()
        ]);
    }
}

#[Route('/order/confirmation/{id}', name: 'order_confirmation')]
public function orderConfirmation(Commande $commande): Response
{
    if ($commande->getPaymentStatus() !== 'completed') {
        return $this->redirectToRoute('payment_cancel', ['id' => $commande->getId()]);
    }

    return $this->render('payment/success.html.twig', [
        'commande' => $commande
    ]);
}

    #[Route('/payment/cancel/{id}', name: 'payment_cancel')]
    public function paymentCancel(Commande $commande): Response
    {
        return $this->render('payment/cancel.html.twig', [
            'commande' => $commande
        ]);
    }
}