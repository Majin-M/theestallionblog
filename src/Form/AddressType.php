<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shippingFirstname', TextType::class, [
                'label'       => 'Prénom',
                'attr'        => ['placeholder' => 'Prénom'],
                'constraints' => [new NotBlank(), new Length(max: 100)],
            ])
            ->add('shippingLastname', TextType::class, [
                'label'       => 'Nom',
                'attr'        => ['placeholder' => 'Nom'],
                'constraints' => [new NotBlank(), new Length(max: 100)],
            ])
            ->add('shippingAddress', TextType::class, [
                'label'       => 'Adresse',
                'attr'        => ['placeholder' => '12 rue de la Paix'],
                'constraints' => [new NotBlank(), new Length(max: 255)],
            ])
            ->add('shippingPostalCode', TextType::class, [
                'label'       => 'Code postal',
                'attr'        => ['placeholder' => '75001'],
                'constraints' => [new NotBlank(), new Length(max: 10)],
            ])
            ->add('shippingCity', TextType::class, [
                'label'       => 'Ville',
                'attr'        => ['placeholder' => 'Paris'],
                'constraints' => [new NotBlank(), new Length(max: 100)],
            ])
            ->add('shippingCountry', CountryType::class, [
                // CountryType génère automatiquement une liste déroulante
                // de tous les pays avec leurs codes ISO (FR, BE, CH...)
                'label'    => 'Pays',
                'preferred_choices' => ['FR', 'BE', 'CH', 'LU'], // pays en haut de la liste
                'constraints' => [new NotBlank()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}