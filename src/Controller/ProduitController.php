<?php

namespace App\Controller;
use App\Entity\CartItem;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\Pagination\PaginationInterface;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Repository\PanierRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/produit')]
final class ProduitController extends AbstractController
{
    #[Route('/', name: 'app_produit_index', methods: ['GET'])]
public function index(Request $request, ProduitRepository $produitRepository): Response
{
    $searchTerm = $request->query->get('search', '');
    
    try {
        $produits = $produitRepository->searchByName($searchTerm);
        
        // If this is an AJAX request (from the search feature)
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'content' => $this->renderView('produit/_product_list.html.twig', [
                    'produits' => $produits
                ]),
                'count' => count($produits),
                'success' => true
            ]);
        }
        
        // Regular page load
        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
        ]);
    } catch (\Exception $e) {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred during search.',
                'error' => $e->getMessage()
            ], 500);
        }
        
        $this->addFlash('error', 'An error occurred during search.');
        return $this->render('produit/index.html.twig', [
            'produits' => [],
        ]);
    }
}

#[Route('/dismiss-notification/{id}', name: 'app_dismiss_notification', methods: ['POST'])]
public function dismissNotification(Request $request, Produit $produit, EntityManagerInterface $entityManager): JsonResponse
{
    // Check if user is logged in and is an admin
    $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Accès refusé: vous devez être administrateur');
    
    // Get the notification type from the request
    $notificationType = $request->request->get('type');
    
    // Store the dismissed notification ID in the session
    $session = $request->getSession();
    $dismissedNotifications = $session->get('dismissed_stock_notifications', []);
    
    // Save the notification as dismissed
    $key = $notificationType . '_' . $produit->getId();
    $dismissedNotifications[$key] = true;
    $session->set('dismissed_stock_notifications', $dismissedNotifications);
    
    return new JsonResponse(['success' => true]);
}

#[Route('/indexback', name: 'app_produit_indexback', methods: ['GET'])]
public function indexback(ProduitRepository $produitRepository, Request $request): Response
{
    // Check if user is logged in and is an admin
    $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Accès refusé: vous devez être administrateur');
    
    $user = $this->getUser();
    
    // Get all products
    $allProducts = $produitRepository->findAll();
    
    // Get dismissed notifications from session
    $session = $request->getSession();
    $dismissedNotifications = $session->get('dismissed_stock_notifications', []);
    
    // Find products that are out of stock (stockQuantite = 0)
    $outOfStockProducts = $produitRepository->findBy(['stock_quantite' => 0]);
    
    // Filter out dismissed notifications
    $filteredOutOfStock = array_filter($outOfStockProducts, function($product) use ($dismissedNotifications) {
        $key = 'out_of_stock_' . $product->getId();
        return !isset($dismissedNotifications[$key]);
    });
    
    // Find products with low stock (less than 5 items)
    $lowStockProducts = $produitRepository->createQueryBuilder('p')
        ->where('p.stock_quantite > 0')
        ->andWhere('p.stock_quantite <= 5')
        ->getQuery()
        ->getResult();
    
    // Filter out dismissed notifications
    $filteredLowStock = array_filter($lowStockProducts, function($product) use ($dismissedNotifications) {
        $key = 'low_stock_' . $product->getId();
        return !isset($dismissedNotifications[$key]);
    });
    
    // For admins, show all products with stock notifications
    return $this->render('produit/indexback.html.twig', [
        'produits' => $allProducts,
        'outOfStockProducts' => $filteredOutOfStock,
        'lowStockProducts' => $filteredLowStock,
        'user' => $user
    ]);
}

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Check if user is logged in and is an admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Accès refusé: vous devez être administrateur');
        
        $user = $this->getUser();
        if (!$user instanceof Utilisateur || $user->getRole() !== UserRole::ADMIN) {
            throw new AccessDeniedException('Seuls les administrateurs peuvent ajouter des produits.');
        }
        
        $produit = new Produit();
        $produit->setDate(new \DateTime());
        $produit->setCreatedBy($user); // Set the current admin as creator
    
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFiles = $request->files->get('produit')['images']; // Get uploaded files
    
            if (empty($imageFiles)) {
                $this->addFlash('error', 'Veuillez télécharger au moins une image.');
                return $this->redirectToRoute('app_produit_new');
            }
    
            $imageNames = [];
            foreach ($imageFiles as $imageFile) {
                if ($imageFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
    
                    try {
                        $imageFile->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );
                        $imageNames[] = $newFilename;
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                        return $this->redirectToRoute('app_produit_new');
                    }
                }
            }
    
            // Save image names as a comma-separated string
            $produit->setImage(implode(',', $imageNames));
    
            $entityManager->persist($produit);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_produit_indexback', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_produit_show', methods: ['GET'])]
public function show(ProduitRepository $repository, int $id): Response
{
    $produit = $repository->find($id);
    
    if (!$produit) {
        throw $this->createNotFoundException('Le produit n\'existe pas');
    }
    
    return $this->render('produit/show.html.twig', [
        'produit' => $produit,
    ]);
}

#[Route('/{id}/indexback', name: 'app_produit_showback', methods: ['GET'])]
public function showback(Produit $produit): Response
{
    // Check if user is logged in and is an admin
    $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Accès refusé: vous devez être administrateur');
    
    return $this->render('produit/showback.html.twig', [
        'produit' => $produit,
    ]);
}

#[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
{
    // Check if user is logged in and is an admin
    $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Accès refusé: vous devez être administrateur');
    
    // Optional: check if this admin created the product
    $user = $this->getUser();
    if (!$user instanceof Utilisateur || $user->getRole() !== UserRole::ADMIN) {
        throw new AccessDeniedException('Seuls les administrateurs peuvent modifier des produits.');
    }
    
    $form = $this->createForm(ProduitType::class, $produit);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Handle image uploads
        $imageFiles = $request->files->get('produit')['images'] ?? null;
        
        if ($imageFiles) {
            $imageNames = [];
            
            // If editing with new images, we'll replace the old ones
            // Handle case where it's a single file or multiple files
            if ($imageFiles instanceof UploadedFile) {
                $imageFiles = [$imageFiles]; // Convert to array for consistent processing
            }
            
            // Process each image
            foreach ($imageFiles as $imageFile) {
                if ($imageFile instanceof UploadedFile) {
                    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                        $this->addFlash('error', 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
                        return $this->render('produit/editback.html.twig', [
                            'produit' => $produit,
                            'form' => $form,
                        ]);
                    }

                    // Generate unique filename
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();

                    try {
                        // Move file to the directory where images are stored
                        $imageFile->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );

                        $imageNames[] = $newFilename;
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                        return $this->render('produit/editback.html.twig', [
                            'produit' => $produit,
                            'form' => $form,
                        ]);
                    }
                }
            }
            
            // If we have new images, update the product
            if (!empty($imageNames)) {
                // Replace old images with new ones
                $produit->setImage(implode(',', $imageNames));
            }
        }

        $entityManager->flush();
        $this->addFlash('success', 'Le produit a été modifié avec succès.');
        return $this->redirectToRoute('app_produit_indexback', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('produit/editback.html.twig', [
        'produit' => $produit,
        'form' => $form,
    ]);
}

#[Route('/{id}', name: 'app_produit_delete', methods: ['POST'])]
public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
{
    // Check if user is logged in and is an admin
    $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Accès refusé: vous devez être administrateur');
    
    // Optional: check if this admin created the product
    $user = $this->getUser();
    if (!$user instanceof Utilisateur || $user->getRole() !== UserRole::ADMIN) {
        throw new AccessDeniedException('Seuls les administrateurs peuvent supprimer des produits.');
    }
    
    if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
        try {
            // First, find and delete related cart items
            $cartItems = $entityManager->getRepository(CartItem::class)
                ->findBy(['produit' => $produit]);
            
            // Delete each cart item
            foreach ($cartItems as $cartItem) {
                $entityManager->remove($cartItem);
            }
            
            // Flush to delete cart items
            $entityManager->flush();
            
            // Now delete the product
            $entityManager->remove($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit a été supprimé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression du produit.');
        }
    }

    return $this->redirectToRoute('app_produit_indexback', [], Response::HTTP_SEE_OTHER);
}
   

#[Route('/search-products', name: 'app_produit_search', methods: ['GET'])]
public function search(Request $request, ProduitRepository $produitRepository): Response
{
    $searchTerm = $request->query->get('q', '');
    
    try {
        $produits = $produitRepository->searchByName($searchTerm);
        
        // If this is an AJAX request
        if ($request->isXmlHttpRequest()) {
            return $this->render('produit/_product_grid.html.twig', [
                'produits' => $produits,
            ]);
        }
        
        // Regular page load
        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
            'searchTerm' => $searchTerm,
        ]);
    } catch (\Exception $e) {
        // Log the error
        if ($request->isXmlHttpRequest()) {
            return new Response(
                '<p class="text-center">Une erreur est survenue: ' . $e->getMessage() . '</p>',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        
        $this->addFlash('error', 'Une erreur est survenue lors de la recherche');
        return $this->render('produit/index.html.twig', [
            'produits' => [],
            'searchTerm' => $searchTerm,
        ]);
    }
}

#[Route('/filter-products', name: 'filter', methods: ['GET', 'POST'])]
public function filterProducts(Request $request, ProduitRepository $produitRepository): Response
{
    // Determine if this is a GET or POST request and get parameters accordingly
    if ($request->isMethod('POST') && $request->getContent()) {
        $data = json_decode($request->getContent(), true) ?? [];
        $minPrice = $data['min'] ?? 0;
        $maxPrice = $data['max'] ?? 9999;
    } else {
        $minPrice = $request->query->get('min', 0);
        $maxPrice = $request->query->get('max', 9999);
    }
    
    // Convert to float and ensure valid values
    $minPrice = max(0, (float) $minPrice);
    $maxPrice = max($minPrice, (float) $maxPrice);
    
    // Query the database for products in the price range
    $produits = $produitRepository->findByPriceRange($minPrice, $maxPrice);
    
    // If AJAX request, return just the product grid
    if ($request->isXmlHttpRequest()) {
        return $this->render('produit/_product_filter.html.twig', [
            'produits' => $produits,
        ]);
    }
    
    // For non-AJAX requests, return the full page
    return $this->render('produit/index.html.twig', [
        'produits' => $produits,
        'min_price' => $minPrice,
        'max_price' => $maxPrice,
    ]);
}
    
}
