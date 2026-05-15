<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProjetController extends AbstractController
{
    #[Route('/projet', name: 'app_projet')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        // On récupère toutes les catégories.
        // Grâce à la relation OneToMany entre Category et Project,
        // Doctrine peut charger automatiquement les projets liés à chaque catégorie.
        // findAll() retourne un tableau d'objets Category, chacun portant
        // sa collection de projets accessible via $category->getProjects().
        $categories = $categoryRepository->findAll();

        return $this->render('projet/index.html.twig', [
            // On passe le tableau de catégories à Twig.
            // Dans le template, on boucle dessus avec {% for category in categories %}.
            'categories' => $categories,
        ]);
    }
}