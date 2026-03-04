<?php

namespace App\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/debug', name: 'api_debug_')]
class ArticleDebugController extends AbstractController
{
    #[Route('/created', name: 'created', methods: ['GET'])]
    public function created(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $days = max(1, min(120, (int)$request->query->get('days', 5)));
        $conn = $em->getConnection();

        $dbNow = $conn->fetchOne('SELECT NOW()');
        $cutoff = $conn->fetchOne('SELECT DATE_SUB(NOW(), INTERVAL ? DAY)', [$days]);
        $maxCreated = $conn->fetchOne('SELECT MAX(`sysCreatedDate`) FROM `article`');

        $countNotNull = $conn->fetchOne('SELECT COUNT(*) FROM `article` WHERE `sysCreatedDate` IS NOT NULL');
        $countNew = $conn->fetchOne(
            'SELECT COUNT(*) FROM `article` WHERE `sysCreatedDate` IS NOT NULL AND `sysCreatedDate` >= DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$days]
        );

        $latest10 = $conn->fetchAllAssociative("
            SELECT `Id`,`UniqueId`,`sysCreatedDate`
            FROM `article`
            WHERE `sysCreatedDate` IS NOT NULL
            ORDER BY `sysCreatedDate` DESC
            LIMIT 10
        ");

        return $this->json([
            'ok' => true,
            'days' => $days,
            'dbNow' => $dbNow,
            'cutoff' => $cutoff,
            'maxSysCreatedDate' => $maxCreated,
            'countSysCreatedNotNull' => (int)$countNotNull,
            'countNew' => (int)$countNew,
            'latest10' => $latest10,
        ]);
    }
}