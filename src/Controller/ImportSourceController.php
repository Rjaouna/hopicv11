<?php

namespace App\Controller;

use App\Entity\ImportSource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;

class ImportSourceController extends AbstractController
{
    #[Route('/admin/import-source', name: 'import_source_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $source = $em->getRepository(ImportSource::class)->findOneBy([]); // une seule config
        return $this->render('import_source/index.html.twig', [
            'source' => $source,
            'csrf' => $this->container->get('security.csrf.token_manager')->getToken('import_source')->getValue(),
        ]);
    }

    #[Route('/admin/import-source/save', name: 'import_source_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode((string)$request->getContent(), true);

        if (!isset($data['_token']) || !$this->isCsrfTokenValid('import_source', $data['_token'])) {
            return $this->json(['ok' => false, 'message' => 'CSRF invalide'], 403);
        }

        $dir  = trim((string)($data['directoryPath'] ?? ''));
        $name = trim((string)($data['fileName'] ?? ''));

        if ($dir === '' || $name === '') {
            return $this->json(['ok' => false, 'message' => 'Chemin et nom de fichier obligatoires.'], 400);
        }

        /** @var ImportSource|null $source */
        $source = $em->getRepository(ImportSource::class)->findOneBy([]);
        if (!$source) $source = new ImportSource();

        $source->setDirectoryPath($dir);
        $source->setFileName($name);
        $source->touch();

        $em->persist($source);
        $em->flush();

        return $this->json([
            'ok' => true,
            'message' => 'Configuration enregistrée.',
            'fullPath' => $source->getFullPath(),
        ]);
    }
}