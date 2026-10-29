<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    // Delivery countries (the name is stored as is in the address, then in the order) - also used by CheckoutController
    public const COUNTRIES = ['France', 'Belgique', 'Luxembourg', 'Suisse', 'Monaco', 'Allemagne', 'Espagne', 'Italie', 'Pays-Bas', 'Portugal'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // empty_data '': a cleared field arrives as an empty string (the setters refuse null), so the entity NotBlank applies
        $builder
            ->add('country', ChoiceType::class, [
                'label' => 'Pays',
                'choices' => array_combine(self::COUNTRIES, self::COUNTRIES),
                'invalid_message' => 'Choisissez un pays dans la liste.',
            ])
            ->add('street', TextType::class, [
                'label' => 'Rue et numéro',
                'empty_data' => '',
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'empty_data' => '',
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'empty_data' => '',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
