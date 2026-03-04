<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArticleController extends AbstractController
{
    #[Route('/articles', name: 'articles_page', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Tu peux même supprimer "initial" car la Twig le calcule si absent,
        // mais je te le laisse propre.
        return $this->render('articles/index.html.twig', [
            'initial' => [
                'q' => (string)$request->query->get('q', ''),
                'family' => (string)$request->query->get('family', ''),
                'brand' => (string)($request->query->get('brand', '') ?: $request->query->get('marque', '')),
                'vehicle' => (string)($request->query->get('vehicle', '') ?: $request->query->get('vehicule', '')),
                'annee' => (string)$request->query->get('annee', ''),
                'inStock' => (string)$request->query->get('inStock', ''),
                'new' => (string)$request->query->get('new', ''),
                'newDays' => (int)$request->query->get('newDays', 5),
            ],
        ]);
    }
}