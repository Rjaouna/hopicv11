<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\HeroSlideRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(HeroSlideRepository $heroSlideRepository): Response
    {
        $slides = $heroSlideRepository->findForHomepage();
        return $this->render('home/index.html.twig',['slides' => $slides,]);
    }

    #[Route('/articles', name: 'articles_page', methods: ['GET'])]
    public function articlesPage(): Response
    {
        return $this->render('articles/index.html.twig');
    }
}