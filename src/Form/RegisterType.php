<?php
// Classe définissant la structure du Formulaire d'inscription d'un utilisateur


namespace App\Form;


use App\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegisterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     * @description Crée le Formulaire d'inscription d'un utilisateur
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //ajout des champs au formulaire
        $builder
        //le Type TextType est utilisé pour les champs de texte
            ->add('firstname',TextType::class,[
                'label'=>'Prénom',
                //attr permet de définir des attributs HTML sur le champ
                'attr'=>[
                    'placeholder'=>'Veuillez entrer votre prénom'
                ]
            ])
            //le Type TextType est utilisé pour les champs de texte
            ->add('lastname',TextType::class,[
                'label'=>'Nom',
                //attr permet de définir des attributs HTML sur le champ
                'attr'=>[
                    'placeholder'=>'Veuillez entrer votre Nom'
                ]
            ] )
            //le Type EmailType est utilisé pour les champs d'adresse e-mail
            ->add('email',EmailType::class,[
                'label'=>'E-mail',
                //attr permet de définir des attributs HTML sur le champ
                'attr'=>[
                    'placeholder'=>'Veuillez entrer votre e-mail'
                ]
            ])
            //le Type RepeatedType est utilisé pour forcer les entrées de deux champs à être identiques
            ->add('plainPassword',RepeatedType::class,[
                //le Type PasswordType est utilisé pour les champs de mot de passe
                'type'=>PasswordType::class,
                //first_options permet de définir les options du premier champ
                'first_options'=>[
                    'label'=>'Mot de passe',
                    /**
                    *  Normalement avec mapped => false, le champ n'est pas du tout lié à l'entité. 
                    * hash_property_path est une exception : il dit à Symfony :
                    * "Ne mappe pas la valeur brute, mais hache-la 
                    * et écris le résultat dans la propriété password de l'entité."
                    * Sans ça, tu devrais hasher le mot de passe manuellement dans le contrôleur avec UserPasswordHasherInterface.
                    *  C'est un raccourci pratique.
                    */
                    'hash_property_path'=>'password',
                    'attr'=>[
                        'placeholder'=>'Veuillez entrer un mot de passe'
                    ]
                ],
                //second_options permet de définir les options du deuxième champ
                'second_options'=>[
                    'label'=>'Confirmer le mot de passe',
                    'attr'=>[
                        'placeholder'=>'Veuillez confirmer le mot de passe'
                    ]
                ],
                'mapped'=>false,
                //constraints permet de définir les contraintes de validation
                //minimum 8 caractères pour le mdp
                'constraints' => [new Assert\Length(min: 8)],
            ])
                //le Type SubmitType est utilisé pour les boutons d'envoi de formulaire 
            ->add('inscription',SubmitType::class,[])
            
        ;
    }//pour affricher les messages de prévention lors des erreurs d'entrée dans les champs en français
    // mettre 
    // framework:
    //     default_locale: fr
    // dans config/packages/translation.yaml



    /**
     * @param OptionsResolver $resolver
     * @return void
     * @description Configure les options du Formulaire d'inscription d'un utilisateur
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            
            //data_class permet de définir la classe de données liée au formulaire
            'data_class' => User::class,
            //constraints permet de définir des contraintes de validation
            //UniqueEntity permet de définir l'entrée d'un champ comme étant unique dans la base de données
            //si la valeur entrée pour ce champ est déjà utilisée, l'utilisateur sera informé
            'constraints' => [new UniqueEntity(
                //dans notre cas, on vérifie que l'adresse e-mail n'est pas déjà utilisée
                /**
                 *L'ancienne façon (tableau d'options)
                 * new UniqueEntity(['fields' => 'email', 'message' => '...'])
                 * Symfony recevait un seul argument : un tableau associatif, puis devait deviner quelles clés correspondaient à quelles propriétés en le parcourant.
                 *  C'était une convention "maison" de Symfony, pas du PHP standard.
                 * La nouvelle façon (named arguments PHP)
                 * new UniqueEntity(fields: 'email', message: '...')
                 * Depuis PHP 8.0, le langage supporte nativement les named arguments. 
                 * Symfony 7.3 a décidé d'en profiter pour que ses contraintes soient des classes PHP normales, 
                 * où chaque paramètre du constructeur est explicitement nommé.
                 * 
                 */

                //fields permet de définir les champs à vérifier
                fields :['email'],
                message :'cet adresse e-mail est déjà utilisée'
            

                )],
        ]);
    }
}
