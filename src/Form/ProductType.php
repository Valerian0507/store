<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 64),
                ],
            ])
            ->add('category', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 128),
                ],
            ])
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 128),
                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('volumeM3', NumberType::class, [
                'required' => false,
                'scale' => 3,
                'constraints' => [
                    new NotBlank(['message' => 'Заменить в From на фр.']),
                    new Type([
                        'type' => 'numeric',
                        'message' => 'Введите корректное число.',
                    ]),
                    new PositiveOrZero([
                        'message' => 'Объем не может быть отрицательным.',
                    ]),
                ],
            ])
            ->add('weightKg', NumberType::class, [
                'required' => false,
                'scale' => 3,
                'constraints' => [
                    new NotBlank(['message' => 'Введите вес.']),
                    new Type([
                        'type' => 'numeric',
                        'message' => 'Введите корректное число.',
                    ]),
                    new PositiveOrZero([
                        'message' => 'Вес не может быть отрицательным.',
                    ]),
                ],
            ])
            ->add('priceCents', IntegerType::class, [
                'constraints' => [
                    new NotBlank(),
                    new PositiveOrZero()
                ],
                'help' => 'Price in cents (e.g. 1299 = 12.99',
            ])
            ->add('image', TextType::class, [
                'required' => false,
                'help' => 'Image filename or URL (temporary; file upload later)',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Введите reference image.',
                    ]),
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'Reference не должен превышать 50 символов.',
                    ]),
                    new Regex([
                        'pattern' => '/^REF-\d+$/',
                        'message' => 'Формат должен быть REF-123',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
