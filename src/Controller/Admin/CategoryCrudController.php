<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{  /**
     * @descritpion Retourne la classe de l'entity Category
     * @return string
     */
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
        //on choisit le nom du label pour l'entity category
            ->setEntityLabelInSingular('une categorie')//au singulier pour un élément
            ->setEntityLabelInPlural('Categories')//au pluriel pour le tableau d'éléments
            
        ;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setLabel('Nom')->setHelp('Entrez le nom de la catégorie'),
            SlugField::new('slug')->setLabel('url')->setTargetFieldName('name')->setHelp('L\'url de la catégorie')
        ];
    }
    
}
