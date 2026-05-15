<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProjectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

         public function configureCrud(Crud $crud): Crud
    {
        return $crud
        //on choisit le nom du label pour l'entity projet
            ->setEntityLabelInSingular('un projet')//au singulier pour un élément
            ->setEntityLabelInPlural('Projets')//au pluriel pour le tableau d'éléments
            
            // ...
        ;
    }
    
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setLabel('Nom du projet'),
            TextField::new('genre')->setLabel('Genre du projet'),
            TextEditorField::new('description')->setLabel('Description du projet'),
            SlugField::new('slug')->setLabel('url projet')->setTargetFieldName('name')->setHelp('L\'url du projet'),
            ImageField::new('image')->setLabel('choisir une image')
            ->setUploadedFileNamePattern("[year]-[month]-[day]-[contenthash].[extension]")
            ->setBasePath('/images')
            ->setUploadDir('public/images'),
            TextField::new('spotifyEmbed')
            ->setLabel('Lien embed Spotify')
            ->setHelp('Coller le lien embed Spotify'),
            NumberField::new('price')->setLabel('Prix du projet'),
            NumberField::new('tva')->setLabel('taux de valeur ajouté'),
            AssociationField::new('category')->setLabel('Catégorie'),
            NumberField::new('year')->setLabel('Année de sortie')
        ];
    }

}
