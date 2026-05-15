<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function index(Request $request, ProjectRepository $projectRepository): Response
    {
        $query = trim($request->query->get('q', ''));

        $projects = [];
        $images   = [];

        if ($query !== '') {
            // ── Recherche dans les projets ──────────────────────────────
            // Cherche dans name, genre, description (LIKE %query%)
            $projects = $projectRepository->searchByQuery($query);

            // ── Recherche dans les images de la galerie ─────────────────
            // Les images sont stockées en dur dans le template galerie.
            // On les centralise ici sous forme de tableau PHP.
            // Tu pourras les déplacer en BDD plus tard si tu veux.
            $images = $this->searchGalleryImages($query);
        }

        return $this->render('search/results.html.twig', [
            'query'    => $query,
            'projects' => $projects,
            'images'   => $images,
        ]);
    }

    /**
     * Filtre les images de la galerie dont l'alt contient la requête (insensible à la casse).
     *
     * @return array<int, array{src: string, alt: string, category: string}>
     */
    private function searchGalleryImages(string $query): array
    {
        // 👇 Copie ici toutes tes images de la galerie avec leur alt exact
        $allImages = [
            ['src' => '/images/galerie/todoroki.jpg',   'alt' => 'Cosplay Todoroki',          'category' => 'Cosplay'],
            ['src' => '/images/galerie/deku.jpg',       'alt' => 'Cosplay Deku',               'category' => 'Cosplay'],
            ['src' => '/images/galerie/outfit1.jpg',    'alt' => 'Outfit Hot Girl Summer',     'category' => 'Outfits'],
            // … ajoute toutes tes images ici
        ];

        $q = mb_strtolower($query);

        return array_values(array_filter(
            $allImages,
            fn(array $img) => str_contains(mb_strtolower($img['alt']), $q)
        ));
    }
}