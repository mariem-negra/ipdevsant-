<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProduitRepository;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\CartItem;

final class PanierController extends AbstractController{
    #[Route('/panier', name: 'app_panier')]
public function index(EntityManagerInterface $entityManager, SessionInterface $session): Response
{
    // Get the current logged-in user
    $utilisateur = $this->getUser();
    
    // Get cart items from session
    $panier = $session->get('panier', []);
    $produits = [];
    $total = 0;

    // If user is logged in, sync with their cart items in the database
    if ($utilisateur) {
        $cartItems = $entityManager->getRepository(CartItem::class)
            ->findBy(['utilisateur' => $utilisateur]);
        
        // Sync session with database
        $panier = [];
        foreach ($cartItems as $cartItem) {
            $produitId = $cartItem->getProduit()->getId();
            $panier[$produitId] = $cartItem->getQuantite();
        }
        $session->set('panier', $panier);
    }

    // Load products based on cart contents
    foreach ($panier as $id => $quantity) {
        $produit = $entityManager->getRepository(Produit::class)->find($id);
        if ($produit) {
            $produits[] = $produit;
            $total += $produit->getPrix() * $quantity;
        }
    }

    return $this->render('panier/index.html.twig', [
        'produits' => $produits,
        'panier' => $panier,
        'total' => $total
    ]);
}
    #[Route('/remove-from-panier/{id}', name: 'remove_from_panier', methods: ['GET', 'POST'])]
public function removeFromPanier(
    int $id,
    SessionInterface $session,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    // Remove from session first
    $panier = $session->get('panier', []);
    
    if (isset($panier[$id])) {
        unset($panier[$id]);
        $session->set('panier', $panier);
        
        // Get the current logged-in user
        $utilisateur = $this->getUser();
        
        // Then remove from database if user is logged in
        if ($utilisateur) {
            $cartItem = $entityManager->getRepository(CartItem::class)
                ->findOneBy([
                    'produit' => $id,
                    'utilisateur' => $utilisateur
                ]);
                
            if ($cartItem) {
                $entityManager->remove($cartItem);
                $entityManager->flush();
            }
        }
        
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'count' => count($panier)
            ]);
        }
        
        $this->addFlash('success', 'Le produit a été supprimé du panier');
    }
    
    return $this->redirectToRoute('app_panier');
}

    #[Route('/add-to-panier/{id}', name: 'add_to_panier', methods: ['POST'])]
public function addToPanier(int $id, Request $request, EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $quantity = $data['quantity'] ?? 1;
    
    $produit = $entityManager->getRepository(Produit::class)->find($id);
    
    if (!$produit) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Produit non trouvé'
        ]);
    }

    // Check if product is in stock
    if ($produit->getStockQuantite() < $quantity) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Stock insuffisant'
        ]);
    }

    // Get the current logged-in user
    $utilisateur = $this->getUser();

    // Check if product exists in session cart
    $panier = $session->get('panier', []);
    $productExistsInCart = isset($panier[$id]);
    
    if ($productExistsInCart) {
        $panier[$id] = $quantity; // Update quantity
        $message = 'Déjà existant'; // Already exists in French
    } else {
        $panier[$id] = $quantity;
        $message = 'Ajouté'; // Added in French
    }
    
    $session->set('panier', $panier);
    
    // Update or create cart item in database with user association
    if ($utilisateur) {
        $cartItem = $entityManager->getRepository(CartItem::class)
            ->findOneBy([
                'produit' => $produit,
                'utilisateur' => $utilisateur
            ]);
            
        if (!$cartItem) {
            $cartItem = new CartItem();
            $cartItem->setProduit($produit);
            $cartItem->setUtilisateur($utilisateur); // Set the user
        }
        
        $cartItem->setQuantite($quantity);
        $entityManager->persist($cartItem);
        $entityManager->flush();
    }

    return new JsonResponse([
        'success' => true,
        'message' => $message,
        'count' => count($panier)
    ]);
}
    
#[Route('/update-quantity/{id}', name: 'update_quantity', methods: ['POST'])]
public function updateQuantity(
    Request $request,
    Produit $produit,
    SessionInterface $session,
    EntityManagerInterface $entityManager
): JsonResponse {
    $data = json_decode($request->getContent(), true);
    $newQuantity = $data['quantite'] ?? 0;
    
    // Validate quantity
    if ($newQuantity <= 0) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Quantité invalide'
        ]);
    }
    
    // Check stock availability
    if ($newQuantity > $produit->getStockQuantite()) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Stock insuffisant',
            'availableStock' => $produit->getStockQuantite()
        ]);
    }
    
    // Update session cart
    $panier = $session->get('panier', []);
    $panier[$produit->getId()] = $newQuantity;
    $session->set('panier', $panier);
    
    // Get the current logged-in user
    $utilisateur = $this->getUser();
    
    // Update or create cart item in database with user association
    if ($utilisateur) {
        $cartItem = $entityManager->getRepository(CartItem::class)
            ->findOneBy([
                'produit' => $produit->getId(),
                'utilisateur' => $utilisateur
            ]);
            
        if (!$cartItem) {
            $cartItem = new CartItem();
            $cartItem->setProduit($produit);
            $cartItem->setUtilisateur($utilisateur); // Set the user
        }
        
        $cartItem->setQuantite($newQuantity);
        $entityManager->persist($cartItem);
        $entityManager->flush();
    }
    
    // Calculate new totals
    $productTotal = $produit->getPrix() * $newQuantity;
    $cartTotal = 0;
    foreach ($panier as $id => $qty) {
        $p = $entityManager->getRepository(Produit::class)->find($id);
        if ($p) {
            $cartTotal += $p->getPrix() * $qty;
        }
    }
    
    return new JsonResponse([
        'success' => true,
        'quantite' => $newQuantity,
        'newTotal' => number_format($productTotal, 2),
        'cartTotal' => number_format($cartTotal, 2)
    ]);
}


    #[Route('/panier-count', name: 'panier_count')]
    public function getPanierCount(SessionInterface $session): JsonResponse
    {
        $panier = $session->get('panier', []);
        return new JsonResponse([
            'count' => count($panier)
        ]);
    }
    

    /*#[Route('/panier/ajouter/{id}', name: 'ajouter_au_panier')]
public function ajouterAuPanier(int $id, SessionInterface $session, EntityManagerInterface $entityManager): Response
{
    $panier = $session->get('panier', []);

    // Find the product
    $produit = $entityManager->getRepository(Produit::class)->find($id);
    if (!$produit) {
        return $this->redirectToRoute('app_produits');
    }

    // ✅ Store only the quantity, not the full product array
    if (!isset($panier[$id])) {
        $panier[$id] = 1; // First time adding the product
    } else {
        $panier[$id]++; // Increment quantity
    }

    $session->set('panier', $panier);

    $this->addFlash('success', 'Produit ajouté au panier !');

    return $this->redirectToRoute('app_panier');
}

#[Route('/clear-panier', name: 'clear_panier')]
public function clearPanier(SessionInterface $session): Response
{
    $session->remove('panier'); // Clear the panier session
    return new JsonResponse(['success' => true, 'message' => 'Panier cleared']);
}
*/
}
