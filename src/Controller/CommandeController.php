<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CartItem;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use App\Repository\cartItemRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/commande')]
final class CommandeController extends AbstractController
{
    #[Route(name: 'app_commande_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository): Response
    {
        // Get the current user
        $user = $this->getUser();
        
        if (!$user instanceof Utilisateur) {
            throw new AccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        // If user is not an admin, only show their orders
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            $commandes = $commandeRepository->findBy(['utilisateur' => $user]);
        } else {
            // Admin can see all orders
            $commandes = $commandeRepository->findAll();
        }
        
        return $this->render('commande/index.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/indexback', name: 'app_commande_indexback', methods: ['GET'])]
    public function indexback(CommandeRepository $commandeRepository): Response
    {
        return $this->render('commande/indexback.html.twig', [
            'commandes' => $commandeRepository->findAll(),
        ]);
    }


    #[Route('/stats', name: 'app_commande_stats', methods: ['GET'])]
public function stats(CommandeRepository $commandeRepository): Response
{
    $stats = $commandeRepository->getCheckoutStats();
    
    // Debugging: Dump the stats to see what is being returned
    dump($stats);
    
    return $this->render('commande/stats.html.twig', [
        'stats' => $stats,
    ]);
}

    
#[Route('/new', name: 'app_commande_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
{
    // Get the currently logged-in user
    $user = $this->getUser();
    
    if (!$user instanceof Utilisateur) {
        throw new AccessDeniedException('Vous devez être connecté pour passer une commande.');
    }
    
    $commande = new Commande();
    
    // Pre-populate form fields with user data
    $commande->setNom($user->getNom());
    $commande->setPrenom($user->getPrenom());
    $commande->setEmail($user->getEmail());
    $commande->setPhoneNumber((string)$user->getTelephone());
    
    // Link the order to the user
    $commande->setUtilisateur($user);
    
    $form = $this->createForm(CommandeType::class, $commande);
    $form->handleRequest($request);

    // Get cart items from session
    $sessionCart = $session->get('panier', []);
    if (empty($sessionCart)) {
        $this->addFlash('error', 'Votre panier est vide');
        return $this->redirectToRoute('app_panier');
    }

    // Get products for items in session cart
    $produits = [];
    $panier = []; // New array for template display
    $total = 0;
    $stockError = false;
    $errorMessage = '';

    foreach ($sessionCart as $produitId => $quantity) {
        $produit = $entityManager->getRepository(Produit::class)->find($produitId);
        if (!$produit) {
            continue;
        }

        // Check stock
        if ($quantity > $produit->getStockQuantite()) {
            $stockError = true;
            $errorMessage = "Stock insuffisant pour {$produit->getNom()}. Quantité disponible: {$produit->getStockQuantite()}";
            break;
        }

        $produits[$produitId] = [
            'produit' => $produit,
            'quantity' => $quantity
        ];

        // Add to panier array for template display
        $panier[] = [
            'produit' => $produit,
            'quantity' => $quantity
        ];

        $total += $produit->getPrix() * $quantity;
    }

    if ($form->isSubmitted() && $form->isValid()) {
        if ($stockError) {
            $this->addFlash('error', $errorMessage);
            return $this->redirectToRoute('app_panier');
        }

        try {
            // Start transaction
            $entityManager->beginTransaction();

            // Set total amount for the order
            $commande->setTotalAmount($total);
            $entityManager->persist($commande);

            // Create new cart items for this order
            foreach ($produits as $produitId => $data) {
                $produit = $data['produit'];
                $quantity = $data['quantity'];

                // Create a new CartItem
                $cartItem = new CartItem();
                $cartItem->setProduit($produit);
                $cartItem->setQuantite($quantity);
                $cartItem->setCommande($commande);
                $cartItem->setUtilisateur($user); // Link cart item to user
                
                // Update product stock
                $newStock = $produit->getStockQuantite() - $quantity;
                $produit->setStockQuantite($newStock);
                
                $entityManager->persist($cartItem);
                $entityManager->persist($produit);
            }

            $entityManager->flush();
            $entityManager->commit();

            // Clear the session cart
            $session->remove('panier');

            return $this->redirectToRoute('process_payment', ['id' => $commande->getId()]);

        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->addFlash('error', 'Une erreur est survenue lors de la commande: ' . $e->getMessage());
            return $this->redirectToRoute('app_panier');
        }
    }

    return $this->render('commande/new.html.twig', [
        'commande' => $commande,
        'form' => $form,
        'produits' => $produits,
        'panier' => $panier,
        'total' => $total,
        'stockError' => $stockError,
        'errorMessage' => $errorMessage
    ]);
}

private function getActiveCartItems(EntityManagerInterface $entityManager, SessionInterface $session): array
{
    // Get the cart items from session
    $panier = $session->get('panier', []);
    
    // Get only the cart items that are in the current session
    $cartItems = $entityManager->getRepository(CartItem::class)->findBy([
        'commande' => null
    ]);
    
    $activeItems = [];
    foreach ($cartItems as $cartItem) {
        $produitId = $cartItem->getProduit()->getId();
        if (isset($panier[$produitId])) {
            $activeItems[] = [
                'produit' => $cartItem->getProduit(),
                'quantity' => $panier[$produitId],
                'cartItem' => $cartItem
            ];
        }
    }
    
    return $activeItems;
}

private function removeOldCartItems(EntityManagerInterface $entityManager, array $activeCartItems): void
{
    foreach ($activeCartItems as $itemData) {
        if (isset($itemData['cartItem'])) {
            $entityManager->remove($itemData['cartItem']);
        }
    }
    $entityManager->flush();
}

private function getCartItemsFromSession(Request $request, EntityManagerInterface $entityManager): array
{
    $session = $request->getSession();
    $panier = $session->get('panier', []);

    $cartItems = [];
    foreach ($panier as $id => $quantity) {
        $produit = $entityManager->getRepository(Produit::class)->find($id);
        if ($produit) {
            $cartItems[] = [
                'produit' => $produit,
                'quantity' => $quantity,
            ];
        }
    }

    return $cartItems;
}

private function getCartItemsFromDatabase(EntityManagerInterface $entityManager): array
{
    $cartItems = $entityManager->getRepository(CartItem::class)->findBy(['commande' => null]);
    
    $items = [];
    foreach ($cartItems as $cartItem) {
        $items[] = [
            'produit' => $cartItem->getProduit(),
            'quantity' => $cartItem->getQuantite(),
            'cartItem' => $cartItem  // Keep reference to the original cart item
        ];
    }
    
    return $items;
}

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        // Get the current user
        $user = $this->getUser();
        
        // Check if the user is the owner of the order or an admin
        if (!$user instanceof Utilisateur || 
            ($commande->getUtilisateur() !== $user && !in_array('ROLE_ADMIN', $user->getRoles()))) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette commande.');
        }
        
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/indexback', name: 'app_commande_showback', methods: ['GET'])]
    public function showback(Commande $commande): Response
    {
        return $this->render('commande/showback.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_commande_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        // Get the current user
        $user = $this->getUser();
        
        // Check if the user is the owner of the order or an admin
        if (!$user instanceof Utilisateur || 
            ($commande->getUtilisateur() !== $user && !in_array('ROLE_ADMIN', $user->getRoles()))) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à modifier cette commande.');
        }
        
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // No need to modify cart items during edit
            // Just save the updated commande
            $entityManager->flush();
    
            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        // Get the current user
        $user = $this->getUser();
        
        // Only admins can delete orders
        if (!$user instanceof Utilisateur || !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette commande.');
        }
        
        if ($this->isCsrfTokenValid('delete'.$commande->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($commande);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }

    // Existing utility methods...
   


}