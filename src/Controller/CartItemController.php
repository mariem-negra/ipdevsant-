<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Repository\CartItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartItemController extends AbstractController
{
    #[Route('/cart/item/indexback', name: 'app_cart_item_indexback')]
    public function indexback(CartItemRepository $cartItemRepository): Response
    {
        return $this->render('cart_item/indexback.html.twig', [
            'cart_items' => $cartItemRepository->findAll(),
        ]);
    }

    #[Route('/cart/item/{id}/showback', name: 'app_cart_item_showback', methods: ['GET'])]
public function showback(CartItem $cart_item): Response
{
    return $this->render('cart_item/showback.html.twig', [
        'cart_item' => $cart_item,
    ]);
}
}