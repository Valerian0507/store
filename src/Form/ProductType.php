<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ vide : chaîne vide (et non null) pour que le NotBlank de l'entité Product s'applique
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'empty_data' => '',
                'constraints' => [
                    new Length(max: 64, maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
            ])
            // Obligatoire : Assert\NotNull de l'entité Product
            ->add('category', EntityType::class, [
                'label' => 'Catégorie',
                'class' => Category::class,
                'choice_label' => fn (Category $category) => ucfirst((string) $category->getLabel()),
                'placeholder' => 'Sélectionnez une catégorie',
                'required' => true,
            ])
            ->add('title', TextType::class, [
                'label' => 'Nom du produit',
                'empty_data' => '',
                'constraints' => [
                    new Length(max: 128, maxMessage: 'Maximum {{ limit }} caractères.'),
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('replenishable', CheckboxType::class, [
                'label' => 'Autoriser un planning de réapprovisionnement (démo)',
                'help' => 'Pour les consommables uniquement. Sans paiement ni livraison automatique.',
                'required' => false,
            ])
            ->add('volumeM3', NumberType::class, [
                'label' => 'Volume du colis (m³)',
                'required' => true,
                'scale' => 3,
                'invalid_message' => 'Renseignez un nombre (ex. 0,25).',
                'constraints' => [
                    new NotBlank(message: 'Mettez le volume.'),
                    new PositiveOrZero(message: 'Le volume ne peut pas être négatif.'),
                ],
            ])
            ->add('weightKg', NumberType::class, [
                'label' => 'Poids (kg)',
                'required' => true,
                'scale' => 3,
                'invalid_message' => 'Renseignez un nombre (ex. 2,5).',
                'constraints' => [
                    new NotBlank(message: 'Mettez le poids.'),
                    new PositiveOrZero(message: 'Le poids ne peut pas être négatif.'),
                ],
            ])
            ->add('priceCents', IntegerType::class, [
                'label' => 'Prix (centimes)',
                // Obligatoire et positif : contraintes de l'entité Product
                'invalid_message' => 'Renseignez un nombre entier de centimes (ex. 49900).',
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Quantité à ajouter au stock',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new PositiveOrZero(message: 'La quantité ne peut pas être négative.'),
                ],
            ])

            ->add('image', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ],
                        mimeTypesMessage: 'Téléchargez une image valide (JPG, PNG, WEBP)',
                    )
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
