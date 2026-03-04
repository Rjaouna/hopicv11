<?php

namespace App\Controller\Api;

use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ArticleApiController extends AbstractController
{
    #[Route('/articles', name: 'articles_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $page  = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(200, (int)$request->query->get('limit', 24)));
        $offset = ($page - 1) * $limit;

        $q        = trim((string)$request->query->get('q', ''));
        $family   = trim((string)$request->query->get('family', ''));
        $marque   = trim((string)($request->query->get('marque', '') ?: $request->query->get('brand', '')));
        $vehicule = trim((string)($request->query->get('vehicule', '') ?: $request->query->get('vehicle', '')));
        $annee    = trim((string)$request->query->get('annee', ''));
        $inStock  = (string)$request->query->get('inStock', '');

        $onlyNew = ((string)$request->query->get('new', '') === '1');
        $newDays = max(1, min(120, (int)$request->query->get('newDays', 5)));

        // Badge "nouveauté" (sur la liste normale aussi)
        $badgeDays = 5;

        // --- IMAGES BASE URL (robuste, sans hasParameter) ---
        $imagesBaseUrl = '';
        try { $imagesBaseUrl = (string)$this->getParameter('images_base_url'); } catch (\Throwable) {}
        if (!$imagesBaseUrl) {
            try { $imagesBaseUrl = (string)$this->getParameter('e_url'); } catch (\Throwable) {}
        }
        if (!$imagesBaseUrl) {
            $imagesBaseUrl = (string)($_ENV['IMAGES_BASE_URL'] ?? getenv('IMAGES_BASE_URL') ?: '');
        }
        $imagesBaseUrl = rtrim($imagesBaseUrl, '/');

        $conn = $em->getConnection();

        // --- WHERE ---
        $where = [];
        $params = [];
        $types  = [];

        if ($q !== '') {
            $where[] = "(`Id` LIKE :q OR `DesComClear` LIKE :q OR `UniqueId` LIKE :q)";
            $params['q'] = '%'.$q.'%';
            $types['q'] = ParameterType::STRING;
        }
        if ($family !== '') {
            $where[] = "`FamilyName` = :family";
            $params['family'] = $family;
            $types['family'] = ParameterType::STRING;
        }
        if ($marque !== '') {
            $where[] = "`xx_Marque` = :marque";
            $params['marque'] = $marque;
            $types['marque'] = ParameterType::STRING;
        }
        if ($vehicule !== '') {
            $where[] = "`xx_Vehicule` LIKE :veh";
            $params['veh'] = '%'.$vehicule.'%';
            $types['veh'] = ParameterType::STRING;
        }
        if ($annee !== '') {
            $where[] = "`xx_Annee` LIKE :annee";
            $params['annee'] = '%'.$annee.'%';
            $types['annee'] = ParameterType::STRING;
        }
        if ($inStock === '1') {
            $where[] = "`RealStock` > 0";
        }

        // --- Filtre NOUVEAUTÉS (STRICT sysCreatedDate vs NOW()-X days) ---
        if ($onlyNew) {
            $where[] = "`sysCreatedDate` IS NOT NULL";
            $where[] = "`sysCreatedDate` >= DATE_SUB(NOW(), INTERVAL :newDays DAY)";
            $params['newDays'] = $newDays;
            $types['newDays']  = ParameterType::INTEGER;
        }

        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        // --- COUNT ---
        $total = (int)$conn->fetchOne("SELECT COUNT(*) FROM `article` $whereSql", $params, $types);

        // --- LIST ---
        $orderBy = $onlyNew ? "`sysCreatedDate` DESC" : "`sysModifiedDate` DESC, `sysCreatedDate` DESC";

        $sql = "
            SELECT
                `UniqueId`,`Id`,`DesComClear`,`RealStock`,
                `SalePriceVatIncluded`,`SalePriceVatExcluded`,
                `xx_Annee`,`xx_Vehicule`,`xx_Marque`,
                `sysModifiedDate`,`sysCreatedDate`,
                `FamilyName`,`ExportStartedAt`,`FtpFinishedAt`,
                CASE
                    WHEN `sysCreatedDate` IS NOT NULL
                     AND `sysCreatedDate` >= DATE_SUB(NOW(), INTERVAL :badgeDays DAY)
                    THEN 1 ELSE 0
                END AS `isNew`
            FROM `article`
            $whereSql
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $limit;
        $params['offset'] = $offset;
        $params['badgeDays'] = $badgeDays;

        $types['limit'] = ParameterType::INTEGER;
        $types['offset'] = ParameterType::INTEGER;
        $types['badgeDays'] = ParameterType::INTEGER;

        $rows = $conn->fetchAllAssociative($sql, $params, $types);

        $items = array_map(static function(array $r): array {
            $toAtom = static function($v) {
                if (!$v) return null;
                try { return (new \DateTimeImmutable((string)$v))->format(\DateTimeInterface::ATOM); }
                catch (\Throwable) { return (string)$v; }
            };

            return [
                'UniqueId' => (string)$r['UniqueId'],
                'Id' => (string)$r['Id'],
                'DesComClear' => (string)$r['DesComClear'],
                'RealStock' => (int)$r['RealStock'],
                'SalePriceVatIncluded' => (string)$r['SalePriceVatIncluded'],
                'SalePriceVatExcluded' => (string)$r['SalePriceVatExcluded'],
                'xx_Annee' => (string)$r['xx_Annee'],
                'xx_Vehicule' => (string)$r['xx_Vehicule'],
                'xx_Marque' => (string)$r['xx_Marque'],
                'sysModifiedDate' => $toAtom($r['sysModifiedDate'] ?? null),
                'sysCreatedDate' => $toAtom($r['sysCreatedDate'] ?? null),
                'FamilyName' => (string)$r['FamilyName'],
                'ExportStartedAt' => $toAtom($r['ExportStartedAt'] ?? null),
                'FtpFinishedAt' => $toAtom($r['FtpFinishedAt'] ?? null),
                'isNew' => ((int)($r['isNew'] ?? 0) === 1),
            ];
        }, $rows);

        // debug utile (uniquement quand new=1)
        $meta = [
            'onlyNew' => $onlyNew,
            'newDays' => $newDays,
            'badgeDays' => $badgeDays,
        ];
        if ($onlyNew) {
            $meta['dbNow'] = (string)$conn->fetchOne('SELECT NOW()');
            $meta['cutoff'] = (string)$conn->fetchOne('SELECT DATE_SUB(NOW(), INTERVAL ? DAY)', [$newDays], [ParameterType::INTEGER]);
            $meta['maxSysCreatedDate'] = (string)$conn->fetchOne('SELECT MAX(`sysCreatedDate`) FROM `article`');
        }

        return $this->json([
            'ok' => true,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => (int)ceil($total / max(1, $limit)),
            'items' => $items,
            'imagesBaseUrl' => $imagesBaseUrl,
            'meta' => $meta,
        ]);
    }
}