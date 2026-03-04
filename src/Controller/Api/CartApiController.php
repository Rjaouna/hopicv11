<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\CartRepository;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cart', name: 'api_cart_')]
class CartApiController extends AbstractController
{
    private function imagesBaseUrl(): string
    {
        try {
            return (string)$this->getParameter('e_url');
        } catch (\Throwable) {
            return (string)($_ENV['IMAGES_BASE_URL'] ?? getenv('IMAGES_BASE_URL') ?? '');
        }
    }

    private function requireUser(): ?object
    {
        return $this->getUser(); // si pas connecté => null
    }

    private function getOrCreateOpenCart(object $user, EntityManagerInterface $em, CartRepository $repo): Cart
    {
        /** @var \App\Entity\User $user */
        $cart = $repo->findOpenByUser($user);
        if ($cart) return $cart;

        $cart = new Cart();
        $cart->setUser($user);
        $cart->setStatus('open');
        $em->persist($cart);
        $em->flush();

        return $cart;
    }

    #[Route('/summary', name: 'summary', methods: ['GET'])]
    public function summary(EntityManagerInterface $em, CartRepository $cartRepo): JsonResponse
    {
        $user = $this->requireUser();
        if (!$user) {
            return $this->json(['ok' => false, 'message' => 'Non connecté'], 401);
        }

        $cart = $cartRepo->findOpenByUser($user);
        if (!$cart) {
            return $this->json(['ok' => true, 'totalQty' => 0, 'itemsCount' => 0, 'totalAmount' => "0.00"]);
        }

        $totalQty = 0;
        $totalAmount = 0.0;
        foreach ($cart->getItems() as $it) {
            $totalQty += $it->getQuantity();
            $totalAmount += (float)$it->getLineTotal();
        }

        return $this->json([
            'ok' => true,
            'itemsCount' => $cart->getItems()->count(),
            'totalQty' => $totalQty,
            'totalAmount' => number_format($totalAmount, 2, '.', '')
        ]);
    }

    #[Route('', name: 'get', methods: ['GET'])]
    public function getCart(EntityManagerInterface $em, CartRepository $cartRepo): JsonResponse
    {
        $user = $this->requireUser();
        if (!$user) {
            return $this->json(['ok' => false, 'message' => 'Non connecté'], 401);
        }

        $cart = $cartRepo->findOpenByUser($user);
        if (!$cart) {
            return $this->json([
                'ok' => true,
                'items' => [],
                'totalQty' => 0,
                'totalAmount' => "0.00",
                'imagesBaseUrl' => $this->imagesBaseUrl()
            ]);
        }

        $items = [];
        $totalQty = 0;
        $totalAmount = 0.0;

        $base = rtrim($this->imagesBaseUrl(), '/');

        foreach ($cart->getItems() as $it) {
            $a = $it->getArticle();
            if (!$a) continue;

            $code = $a->getId();
            $uid = $a->getUniqueId();

            $totalQty += $it->getQuantity();
            $totalAmount += (float)$it->getLineTotal();

            $items[] = [
                'code' => $code,
                'uniqueId' => $uid,
                'designation' => $a->getDesComClear(),
                'qty' => $it->getQuantity(),
                'unitPriceTtc' => $it->getUnitPriceTtc(),
                'lineTotal' => $it->getLineTotal(),
                'image' => ($base && $uid) ? ($base . '/' . $uid . '.png') : ''
            ];
        }

        return $this->json([
            'ok' => true,
            'items' => $items,
            'totalQty' => $totalQty,
            'totalAmount' => number_format($totalAmount, 2, '.', ''),
            'imagesBaseUrl' => $this->imagesBaseUrl()
        ]);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepo,
        CartItemRepository $itemRepo
    ): JsonResponse {
        $user = $this->requireUser();
        if (!$user) {
            return $this->json(['ok' => false, 'message' => 'Non connecté'], 401);
        }

        $data = json_decode($request->getContent() ?: '{}', true) ?: [];
        $code = trim((string)($data['code'] ?? ''));
        $qty  = (int)($data['qty'] ?? 1);
        $qty  = max(1, min(999, $qty));

        if ($code === '') {
            return $this->json(['ok' => false, 'message' => 'Code manquant'], 400);
        }

        $article = $em->getRepository(Article::class)->findOneBy(['id' => $code]);
        if (!$article) {
            return $this->json(['ok' => false, 'message' => 'Article introuvable'], 404);
        }

        $cart = $this->getOrCreateOpenCart($user, $em, $cartRepo);

        $item = $itemRepo->findOneByCartAndArticle($cart, $article);
        if (!$item) {
            $item = new CartItem();
            $item->setCart($cart);
            $item->setArticle($article);
            $item->setQuantity($qty);
            $item->setUnitPriceTtc($article->getSalePriceVatIncluded());
            $em->persist($item);
        } else {
            $item->setQuantity($item->getQuantity() + $qty);
        }

        $cart->touch();
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepo,
        CartItemRepository $itemRepo
    ): JsonResponse {
        $user = $this->requireUser();
        if (!$user) return $this->json(['ok' => false, 'message' => 'Non connecté'], 401);

        $data = json_decode($request->getContent() ?: '{}', true) ?: [];
        $code = trim((string)($data['code'] ?? ''));
        $qty  = (int)($data['qty'] ?? 1);

        $cart = $cartRepo->findOpenByUser($user);
        if (!$cart) return $this->json(['ok' => true]);

        $article = $em->getRepository(Article::class)->findOneBy(['id' => $code]);
        if (!$article) return $this->json(['ok' => true]);

        $item = $itemRepo->findOneByCartAndArticle($cart, $article);
        if (!$item) return $this->json(['ok' => true]);

        if ($qty <= 0) {
            $em->remove($item);
        } else {
            $item->setQuantity(min(999, $qty));
        }

        $cart->touch();
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/remove', name: 'remove', methods: ['POST'])]
    public function remove(
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepo,
        CartItemRepository $itemRepo
    ): JsonResponse {
        $user = $this->requireUser();
        if (!$user) return $this->json(['ok' => false, 'message' => 'Non connecté'], 401);

        $data = json_decode($request->getContent() ?: '{}', true) ?: [];
        $code = trim((string)($data['code'] ?? ''));

        $cart = $cartRepo->findOpenByUser($user);
        if (!$cart) return $this->json(['ok' => true]);

        $article = $em->getRepository(Article::class)->findOneBy(['id' => $code]);
        if (!$article) return $this->json(['ok' => true]);

        $item = $itemRepo->findOneByCartAndArticle($cart, $article);
        if ($item) $em->remove($item);

        $cart->touch();
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/clear', name: 'clear', methods: ['POST'])]
    public function clear(EntityManagerInterface $em, CartRepository $cartRepo): JsonResponse
    {
        $user = $this->requireUser();
        if (!$user) return $this->json(['ok' => false, 'message' => 'Non connecté'], 401);

        $cart = $cartRepo->findOpenByUser($user);
        if (!$cart) return $this->json(['ok' => true]);

        foreach ($cart->getItems() as $it) {
            $em->remove($it);
        }

        $cart->touch();
        $em->flush();

        return $this->json(['ok' => true]);
    }
}