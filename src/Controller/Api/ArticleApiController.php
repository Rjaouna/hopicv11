<?php

namespace App\Controller\Api;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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
        $limit = max(1, min(1000, (int)$request->query->get('limit', 100)));

        $q      = trim((string)$request->query->get('q', ''));
        $family = trim((string)$request->query->get('family', ''));

        // compat brand/marque, vehicle/vehicule
        $brand   = trim((string)$request->query->get('brand', ''));
        $marque  = trim((string)$request->query->get('marque', ''));
        $marque  = $brand !== '' ? $brand : $marque;

        $vehicle  = trim((string)$request->query->get('vehicle', ''));
        $vehicule = trim((string)$request->query->get('vehicule', ''));
        $vehicule = $vehicle !== '' ? $vehicle : $vehicule;

        $anneeFrom = (int)$request->query->get('anneeFrom', 0);
        $anneeTo   = (int)$request->query->get('anneeTo', 0);
        $annee     = trim((string)$request->query->get('annee', '')); // compat ancien

        $inStock = (string)$request->query->get('inStock', '');

        $onlyNew = (string)$request->query->get('new', '') === '1';
        $newDays = max(1, min(365, (int)$request->query->get('newDays', 5)));

        // ✅ pour remplir les dropdowns
        $needFacets = (string)$request->query->get('facets', '') === '1';

        $qb = $em->getRepository(Article::class)->createQueryBuilder('a');

        if ($q !== '') {
            $qb->andWhere('a.id LIKE :q OR a.desComClear LIKE :q OR a.uniqueId LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($family !== '') {
            $qb->andWhere('a.familyName = :family')->setParameter('family', $family);
        }

        if ($marque !== '') {
            $qb->andWhere('a.xxMarque = :marque')->setParameter('marque', $marque);
        }

        if ($vehicule !== '') {
            // garde ton LIKE (car xx_Vehicule peut contenir plusieurs marques séparées)
            $qb->andWhere('a.xxVehicule LIKE :veh')->setParameter('veh', '%'.$vehicule.'%');
        }

        // ✅ FILTRE ANNÉE (plage) : xx_Annee est string "2019-2020-2021..."
        if ($anneeFrom > 0 || $anneeTo > 0) {
            if ($anneeFrom <= 0) $anneeFrom = $anneeTo;
            if ($anneeTo <= 0)   $anneeTo   = $anneeFrom;
            if ($anneeFrom > $anneeTo) {
                [$anneeFrom, $anneeTo] = [$anneeTo, $anneeFrom];
            }

            $orX = $qb->expr()->orX();
            for ($y = $anneeFrom; $y <= $anneeTo; $y++) {
                $p = 'y'.$y;
                $orX->add($qb->expr()->like('a.xxAnnee', ':'.$p));
                $qb->setParameter($p, '%'.$y.'%');
            }
            $qb->andWhere($orX);
        } elseif ($annee !== '') {
            $qb->andWhere('a.xxAnnee LIKE :annee')->setParameter('annee', '%'.$annee.'%');
        }

        if ($inStock === '1') {
            $qb->andWhere('a.realStock > 0');
        }

        // ✅ NOUVEAUTÉS (sysCreatedDate)
        $cutoff = null;
        if ($onlyNew) {
            $cutoff = (new \DateTimeImmutable('now'))->modify('-'.$newDays.' days');
            $qb->andWhere('a.sysCreatedDate IS NOT NULL')
               ->andWhere('a.sysCreatedDate >= :cutoff')
               ->setParameter('cutoff', $cutoff);
        }

        $total = $this->countTotal($qb);

        $qb->orderBy('a.sysModifiedDate', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $items = $qb->getQuery()->getResult();

        // ✅ IMAGES_BASE_URL robuste (NE CASSE PAS)
        $imagesBaseUrl = '';
        try {
            // ton services.yaml: parameters: e_url: '%env(IMAGES_BASE_URL)%'
            $imagesBaseUrl = (string)$this->getParameter('e_url');
        } catch (\Throwable $e) {
            $imagesBaseUrl = (string)($_ENV['IMAGES_BASE_URL'] ?? getenv('IMAGES_BASE_URL') ?? '');
        }

        // ✅ bornes années depuis DB
        [$yearMin, $yearMax] = $this->getYearBounds($em);

        // ✅ facets dropdowns
        $facets = null;
        if ($needFacets) {
            $facets = $this->getFacets($em);
        }

        $norm = [];
        foreach ($items as $a) {
            $norm[] = $this->normalize($a, $onlyNew ? $cutoff : null);
        }

        $payload = [
            'ok' => true,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => (int)ceil($total / $limit),
            'items' => $norm,
            'imagesBaseUrl' => $imagesBaseUrl,
            'yearMin' => $yearMin,
            'yearMax' => $yearMax,
            'new' => $onlyNew ? 1 : 0,
            'newDays' => $newDays,
        ];

        if ($facets !== null) {
            $payload['facets'] = $facets;
        }

        return $this->json($payload);
    }

    private function normalize(Article $a, ?\DateTimeImmutable $cutoffForNew): array
    {
        $isNew = false;
        if ($cutoffForNew && $a->getSysCreatedDate()) {
            $isNew = $a->getSysCreatedDate() >= $cutoffForNew;
        }

        return [
            'UniqueId' => $a->getUniqueId(),
            'Id' => $a->getId(),
            'DesComClear' => $a->getDesComClear(),
            'RealStock' => $a->getRealStock(),
            'SalePriceVatIncluded' => $a->getSalePriceVatIncluded(),
            'SalePriceVatExcluded' => $a->getSalePriceVatExcluded(),
            'xx_Annee' => $a->getXxAnnee(),
            'xx_Vehicule' => $a->getXxVehicule(),
            'xx_Marque' => $a->getXxMarque(),
            'sysModifiedDate' => $a->getSysModifiedDate()?->format(\DateTimeInterface::ATOM),
            'sysCreatedDate' => $a->getSysCreatedDate()?->format(\DateTimeInterface::ATOM),
            'FamilyName' => $a->getFamilyName(),
            'ExportStartedAt' => $a->getExportStartedAt()?->format(\DateTimeInterface::ATOM),
            'FtpFinishedAt' => $a->getFtpFinishedAt()?->format(\DateTimeInterface::ATOM),
            'isNew' => $isNew,
        ];
    }

    private function countTotal(QueryBuilder $qb): int
    {
        $countQb = clone $qb;
        $countQb->select('COUNT(a.uniqueId)');
        return (int)$countQb->getQuery()->getSingleScalarResult();
    }

    private function getYearBounds(EntityManagerInterface $em): array
    {
        $conn = $em->getConnection();

        $sql = <<<SQL
SELECT
  MIN(CAST(SUBSTRING(xx_Annee, 1, 4) AS UNSIGNED)) AS yMin,
  MAX(CAST(RIGHT(xx_Annee, 4) AS UNSIGNED))       AS yMax
FROM article
WHERE xx_Annee IS NOT NULL
  AND xx_Annee <> ''
  AND xx_Annee REGEXP '^[0-9]{4}'
  AND xx_Annee REGEXP '[0-9]{4}$'
SQL;

        $row = $conn->fetchAssociative($sql) ?: ['yMin' => null, 'yMax' => null];

        $yMin = isset($row['yMin']) ? (int)$row['yMin'] : 0;
        $yMax = isset($row['yMax']) ? (int)$row['yMax'] : 0;

        if ($yMin <= 0 || $yMax <= 0 || $yMin > $yMax) {
            $yMin = 2000;
            $yMax = (int)(new \DateTimeImmutable('now'))->format('Y');
        }

        return [$yMin, $yMax];
    }

    private function getFacets(EntityManagerInterface $em): array
    {
        $conn = $em->getConnection();

        $families = $conn->fetchFirstColumn("
            SELECT DISTINCT FamilyName
            FROM article
            WHERE FamilyName IS NOT NULL AND FamilyName <> ''
            ORDER BY FamilyName ASC
            LIMIT 400
        ");

        $brands = $conn->fetchFirstColumn("
            SELECT DISTINCT xx_Marque
            FROM article
            WHERE xx_Marque IS NOT NULL AND xx_Marque <> ''
            ORDER BY xx_Marque ASC
            LIMIT 400
        ");

        $vehRaw = $conn->fetchFirstColumn("
            SELECT DISTINCT xx_Vehicule
            FROM article
            WHERE xx_Vehicule IS NOT NULL AND xx_Vehicule <> ''
            ORDER BY xx_Vehicule ASC
            LIMIT 600
        ");

        // on transforme "PEUGEOT,CITROEN" => ["PEUGEOT","CITROEN"]
        $vehSet = [];
        foreach ($vehRaw as $line) {
            $parts = preg_split('/[,;|]+/', (string)$line);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') $vehSet[$p] = true;
            }
        }
        $vehicles = array_keys($vehSet);
        sort($vehicles, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'families' => array_values(array_filter(array_map('strval', $families))),
            'brands' => array_values(array_filter(array_map('strval', $brands))),
            'vehicles' => array_values($vehicles),
        ];
    }
}