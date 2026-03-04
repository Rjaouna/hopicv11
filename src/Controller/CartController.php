<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/panier', name: 'cart_index', methods: ['GET'])]
    public function index()
    {
        return $this->render('cart/index.html.twig');
    }
}