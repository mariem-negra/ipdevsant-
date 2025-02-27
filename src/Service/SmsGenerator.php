<?php
// src/Service/SmsGenerator.php
namespace App\Service;

use App\Entity\Commande;
use Twilio\Rest\Client;

class SmsGenerator
{
    private $twilioAccountSid;
    private $twilioAuthToken;
    private $twilioFromNumber;

    public function __construct(string $twilioAccountSid, string $twilioAuthToken, string $twilioFromNumber)
    {
        $this->twilioAccountSid = $twilioAccountSid;
        $this->twilioAuthToken = $twilioAuthToken;
        $this->twilioFromNumber = $twilioFromNumber;
    }

    public function sendOrderConfirmation(Commande $commande): void
    {
        // Format the phone number properly
        $toNumber = $this->formatPhoneNumber($commande->getPhoneNumber());

        // Build the message
        $message = "Merci pour votre commande chez ChronoSerena !\n\n";
        $message .= "Détails de la commande :\n";
        $message .= "Nom de Client: " . $commande->getNom() . " " . $commande->getPrenom() . "\n";
        $message .= "Prix total: €" . number_format($commande->getTotalAmount(), 2) . "\n\n";
        
        // Add product details
        $message .= "Produits:\n";
        foreach ($commande->getCartItems() as $item) {
            $message .= "- " . $item->getProduit()->getNom() . " x" . $item->getQuantite() . "\n";
        }
        
        $message .= "\nMerci d'avoir choisi ChronoSerena !";

        try {
            // Send the SMS
            $client = new Client($this->twilioAccountSid, $this->twilioAuthToken);
            $client->messages->create(
                $toNumber,
                [
                    'from' => $this->twilioFromNumber,
                    'body' => $message,
                ]
            );
        } catch (\Exception $e) {
            // Log the error but don't let it disrupt the checkout flow
            error_log('SMS sending failed: ' . $e->getMessage());
        }
    }

    private function formatPhoneNumber($number): string
    {
        // Remove spaces, dashes, etc.
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        
        // If it doesn't start with +, assume it's a local number and add country code
        if (substr($cleaned, 0, 1) !== '+') {
            return '+216' . $cleaned; // Assuming Tunisia (+216) as default country code
        }
        return $number;
    }
}