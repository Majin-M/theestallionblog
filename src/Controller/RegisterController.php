<?php
/**
 * Controller de la page d'inscription d'un utilisateur 
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    /**
     * @param Request $request
     * @param EntityManagerInterface $EntityManagerInterface
     * @return Response
     * @description Affiche la page d'inscription d'un utilisateur et 
     * enregistre l'utilisateur dans la base de données si le formulaire est valide
     */
    public function index(Request $request, EntityManagerInterface $EntityManagerInterface): Response
    {
        // creation d'un nouvel utilisateur (Entity User)
        $user= new User();
        // creation du formulaire affecté dans une variable $form
        //  prends en paramètre RegisterUserType qui définit le formulaire
        //et user l'utilisateur
        $form=$this->createForm(RegisterType::class, $user); 
        //inspecte la requête et appele la méthode submit() si le formulaire est soumis.
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            //persist() enregistre l'objet dans la mémoire de l'EntityManager pour qu'il sache qu'il devra l'insérer en base de données lors du prochain flush().
            // C'est une mise en file d'attente, pas une écriture immédiate.
            $EntityManagerInterface->persist($user);

            //flush() synchronise la base de données avec ce que l'EntityManager a en mémoire — c'est là que les requêtes SQL sont réellement exécutées.
            $EntityManagerInterface->flush();
            //ajoute un message flash de type succès
            $this->addFlash('success', 'Votre compte a bien été créé !');
            //redirection vers la page login
            return $this->redirectToRoute('app_login');
        }
        

        return $this->render('register/index.html.twig',[
            "register"=>$form->createView() //crée une vue de formulaire et l'affiche dans la page
        ]);
    }

    
}
