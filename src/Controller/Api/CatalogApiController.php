<?php

namespace App\Controller\Api;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog', name: 'api_catalog_')]
class CatalogApiController extends AbstractController
{
    #[Route('/brands', name: 'brands', methods: ['GET'])]
    public function brands(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $family  = trim((string)$request->query->get('family', ''));
        $vehicle = trim((string)$request->query->get('vehicle', ''));

        $qb = $em->getRepository(Article::class)->createQueryBuilder('a')
            ->select('a.xxMarque AS v, COUNT(a.uniqueId) AS c')
            ->where('a.xxMarque IS NOT NULL AND a.xxMarque <> \'\'')
            ->groupBy('a.xxMarque');

        if ($family !== '') {
            $qb->andWhere('a.familyName = :family')->setParameter('family', $family);
        }
        if ($vehicle !== '') {
            $qb->andWhere('a.xxVehicule LIKE :veh')->setParameter('veh', '%'.$vehicle.'%');
        }

        $rows = $qb->getQuery()->getArrayResult();

        $map = [];
        foreach ($rows as $r) {
            $v = trim((string)($r['v'] ?? ''));
            $c = (int)($r['c'] ?? 0);
            if ($v === '' || $c <= 0) continue;
            $map[$v] = ($map[$v] ?? 0) + $c;
        }

        return $this->json(['ok' => true, 'items' => $this->sortAsItems($map)]);
    }

    #[Route('/families', name: 'families', methods: ['GET'])]
    public function families(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $brand   = trim((string)$request->query->get('brand', ''));
        $vehicle = trim((string)$request->query->get('vehicle', ''));

        $qb = $em->getRepository(Article::class)->createQueryBuilder('a')
            ->select('a.familyName AS v, COUNT(a.uniqueId) AS c')
            ->where('a.familyName IS NOT NULL AND a.familyName <> \'\'')
            ->groupBy('a.familyName');

        if ($brand !== '') {
            $qb->andWhere('a.xxMarque = :brand')->setParameter('brand', $brand);
        }
        if ($vehicle !== '') {
            $qb->andWhere('a.xxVehicule LIKE :veh')->setParameter('veh', '%'.$vehicle.'%');
        }

        $rows = $qb->getQuery()->getArrayResult();

        $map = [];
        foreach ($rows as $r) {
            $v = trim((string)($r['v'] ?? ''));
            $c = (int)($r['c'] ?? 0);
            if ($v === '' || $c <= 0) continue;
            $map[$v] = ($map[$v] ?? 0) + $c;
        }

        return $this->json(['ok' => true, 'items' => $this->sortAsItems($map)]);
    }

    #[Route('/vehicles', name: 'vehicles', methods: ['GET'])]
    public function vehicles(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $brand  = trim((string)$request->query->get('brand', ''));
        $family = trim((string)$request->query->get('family', ''));

        // xxVehicule peut contenir "PEUGEOT,CITROEN" -> split et compte
        $qb = $em->getRepository(Article::class)->createQueryBuilder('a')
            ->select('a.xxVehicule AS v')
            ->where('a.xxVehicule IS NOT NULL AND a.xxVehicule <> \'\'');

        if ($brand !== '') {
            $qb->andWhere('a.xxMarque = :brand')->setParameter('brand', $brand);
        }
        if ($family !== '') {
            $qb->andWhere('a.familyName = :family')->setParameter('family', $family);
        }

        $rows = $qb->getQuery()->getArrayResult();

        $map = [];
        foreach ($rows as $r) {
            $raw = trim((string)($r['v'] ?? ''));
            if ($raw === '') continue;

            foreach (explode(',', $raw) as $one) {
                $one = trim($one);
                if ($one === '') continue;
                $map[$one] = ($map[$one] ?? 0) + 1; // 1 occurrence = au moins 1 article
            }
        }

        return $this->json(['ok' => true, 'items' => $this->sortAsItems($map)]);
    }

    private function sortAsItems(array $map): array
    {
        $items = [];
        foreach ($map as $value => $count) {
            $count = (int)$count;
            $value = trim((string)$value);
            if ($value === '' || $count <= 0) continue;
            $items[] = ['value' => $value, 'count' => $count];
        }

        usort($items, function($a, $b){
            if ($a['count'] === $b['count']) return strcmp($a['value'], $b['value']);
            return $b['count'] <=> $a['count'];
        });

        return $items;
    }
}