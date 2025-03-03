<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\SuivieMedical;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ratings', name: 'api_ratings_')]
class RatingApiController extends AbstractController
{
    #[Route('/check/{id}', name: 'check', methods: ['GET'])]
    public function checkRating(
        int $id, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $suiviMedical = $entityManager->getRepository(SuivieMedical::class)->find($id);
        
        if (!$suiviMedical) {
            return $this->json(['error' => 'Suivi médical non trouvé'], 404);
        }
        
        $rating = $entityManager->getRepository(Rating::class)->findBySuiviMedical($id);
        
        if (!$rating) {
            return $this->json(['exists' => false]);
        }
        
        return $this->json([
            'exists' => true,
            'rating' => $rating->getValue(),
            'comment' => $rating->getComment()
        ]);
    }
    
    #[Route('/save', name: 'save', methods: ['POST'])]
    public function saveRating(
        Request $request, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['suiviId']) || !isset($data['rating'])) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }
        
        $suiviMedical = $entityManager->getRepository(SuivieMedical::class)->find($data['suiviId']);
        
        if (!$suiviMedical) {
            return $this->json(['error' => 'Suivi médical non trouvé'], 404);
        }
        
        // Vérifier si une notation existe déjà
        $existingRating = $entityManager->getRepository(Rating::class)->findBySuiviMedical($data['suiviId']);
        
        if ($existingRating) {
            // Mettre à jour la notation existante
            $rating = $existingRating;
        } else {
            // Créer une nouvelle notation
            $rating = new Rating();
            $rating->setRatings($suiviMedical); // Use the correct setter method
            $rating->setCreatedAt(new \DateTime());
        }
        
        $rating->setValue($data['rating']);
        
        if (isset($data['comment'])) {
            $rating->setComment($data['comment']);
        }
        
        $entityManager->persist($rating);
        $entityManager->flush();
        
        return $this->json(['success' => true, 'id' => $rating->getId()]);
    }
    
    #[Route('/average/{traitementId}', name: 'average', methods: ['GET'])]
    public function getAverageRating(
        int $traitementId, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $average = $entityManager->getRepository(Rating::class)->getAverageRatingForTraitement($traitementId);
        
        return $this->json(['average' => $average ?: 0]);
    }
}