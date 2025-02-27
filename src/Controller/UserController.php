<?php

namespace App\Controller;


use App\Entity\CartItem;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/back', name: 'app_back')]
    public function indexB(): Response
    {
        return $this->render('back/index.html.twig', [
            'controller_name' => 'BackController',
        ]);
    }


    #[Route('/all-products', name: 'app_produit_display', methods: ['GET'])]
public function displayProducts(Request $request, ProduitRepository $produitRepository): Response
{
    $searchTerm = $request->query->get('search', '');
         
    try {
        // Fetch all products or search results if search term is provided
        $produits = $produitRepository->searchByName($searchTerm);
                 
        // If AJAX request (for search functionality)
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'content' => $this->renderView('produit/_product_list.html.twig', [
                    'produits' => $produits
                ]),
                'count' => count($produits),
                'success' => true
            ]);
        }
                 
        // For regular page load, render the produit/index.html.twig template
        // instead of rendering base.html.twig directly
        return $this->render('produit/front.html.twig', [
            'produits' => $produits,
        ]);
    } catch (\Exception $e) {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue pendant la recherche.',
                'error' => $e->getMessage()
            ], 500);
        }
                 
        $this->addFlash('error', 'Une erreur est survenue pendant la recherche.');
        return $this->render('produit/front.html.twig', [
            'produits' => [],
        ]);
    }
}

    

    
}
