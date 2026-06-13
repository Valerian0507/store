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
            ->add('reference', TextType::class, [
                'constraints' => [
                    new NotBlank(message: 'Champ obligatoire.'),
                    new Length(max: 64, maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'label',
                'placeholder' => 'Choisissez une catégorie',
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Champ obligatoire.'),
                ],
            ])
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(message: 'Champ obligatoire.'),
                    new Length(max: 128, maxMessage: 'Maximum {{ limit }} caractères.'),

                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('volumeM3', NumberType::class, [
                'required' => true,
                'scale' => 3,
                'invalid_message' => 'Renseignez un nombre (ex. 1.25).',
                'constraints' => [
                    new NotBlank(message: 'Mettez le volume.'),
                    new PositiveOrZero(message: 'Le volume ne peut pas être négatif.'),
                ],
            ])
            ->add('weightKg', NumberType::class, [
                'required' => true,
                'scale' => 3,
                'invalid_message' => 'Renseignez un nombre (ex. 2.5).',
                'constraints' => [
                    new NotBlank(message: 'Mettez le poids.'),
                    new PositiveOrZero(message: 'Le poids ne peut pas être négatif.'),
                ],
            ])
            ->add('priceCents', IntegerType::class, [
                'constraints' => [
                    new NotBlank(message: 'Mettez le prix en centimes.'),
                    new PositiveOrZero(message: 'Le prix ne peut pas être négatif.'),
                ],
                'help' => 'Price in cents (e.g. 1299 = 12.99)',
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
