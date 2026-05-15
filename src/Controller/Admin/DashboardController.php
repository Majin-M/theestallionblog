<?php
//on a fait composer require easycorp/easyadmin-bundle pour pouvoir utiliser EasyAdmin avant de créer ce fichier
//cela a créer les dossiers  bundles/easyadmin et leur contenu; dans public 
//ce fichier a été généré automatiquement par symfony console make:admin:dashboard

namespace App\Controller\Admin;
use App\Controller\Admin\UserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {


        // Option 1. You can make your dashboard redirect to some common page of your backend

        // AdminUrlGenerator est un service d'EasyAdmin qui sait construire des URLs vers les pages d'administration.
        // $this->container est le conteneur de services de Symfony : c'est une "boîte" qui contient tous les services
        // disponibles dans l'application. On lui demande ici de nous donner une instance d'AdminUrlGenerator
        // en passant son nom de classe (AdminUrlGenerator::class) comme identifiant.
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect( 
            // $this->redirect() est une méthode héritée de AbstractController (le contrôleur de base de Symfony).
            // Elle prend une URL en paramètre et retourne une réponse HTTP de type "redirection" (code 302).
            // Contrairement à redirectToRoute() qui prend un nom de route, redirect() prend une URL complète.

    $adminUrlGenerator                               // on part du générateur d'URL EasyAdmin
        ->setController(UserCrudController::class)   // on lui dit : "je veux aller vers le CRUD de l'entité User"
                                                     // UserCrudController::class est le nom complet de la classe
                                                     // sous forme de chaîne, ex: "App\Controller\Admin\UserCrudController"

        ->generateUrl()                              // on déclenche la génération de l'URL finale.
                                                     // EasyAdmin construit alors une URL du type :
                                                     // /admin?crudController=UserCrudController&crudAction=index
                                                     // qui correspond à la liste des utilisateurs dans l'admin
);
        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('HOTTIES MERCH DASHBOARD');//titre du projet dans le dashboard
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        //Ajoute au menu de l'interface un élément correspondant 
        // à l'entité du controller crud, dans notre cas User
        //'Utilisateurs' est le nom que l'on choisi pour cet élément
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fas fa-user');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fas fa-list');
        yield MenuItem::linkTo(ProjectCrudController::class, 'Projets', 'fas fa-compact-disc');
    }
    
}
