<?php
// ce fichier a été généré automatiquement par symfony console make:admin:crud User

namespace App\Controller\Admin; //Le namespace de la classe a été défini à ce moment 
                                // Namespace of the generated CRUD controller [App\Controller\Admin]:
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    /**
     * @descritpion Retourne la classe de l'entity User
     * @return string
    */
    public static function getEntityFqcn(): string
    {
        return User::class;
    }
    /**
     * @param Crud $crud
     * @descritpion Configure le CRUD de l'interface admin 
     * @return Crud
    */
     public function configureCrud(Crud $crud): Crud
    {
        return $crud
        //on choisit le nom du label pour l'entity user
            ->setEntityLabelInSingular('un utilisateur')//au singulier pour un élément
            ->setEntityLabelInPlural('Utilisateurs')//au pluriel pour le tableau d'éléments
            
            // ...
        ;
    }
    /**
     * @param string $pageName
     * @descritpion Configure les champs de l'interface admin
     * @return iterable
    */
    public function configureFields(string $pageName): iterable
    {
        return [
            //on modifie le label du champ firstname par Prénom dans l'interface admin
            TextField::new('firstname')->setLabel("Prénom"),
            //on modifie le label du champ lastname par Nom dans l'interface admin
            TextField::new('lastname')->setLabel("Nom"),
            //on modifie le label du champ email par email dans l'interface admin
            //et on le rend uniquement visible sur la page d'index de l'interface admin
            TextField::new('email')->setLabel("email")->onlyOnIndex(),
        ];
    }
    
}
