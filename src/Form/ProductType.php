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
                    new Length(max: 64, maxMessage: 'Максимум {{ limit }} символов.'),
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'label',
                'placeholder' => 'Выберите категорию',
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Champ obligatoire.'),
                ],
            ])
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(message: 'Champ obligatoire.'),
                    new Length(max: 128, maxMessage: 'Максимум {{ limit }} символов.'),

                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('volumeM3', NumberType::class, [
                'required' => true,
                'scale' => 3,
                'invalid_message' => 'Введите число (например 1.25).',
                'constraints' => [
                    new NotBlank(message: 'Введите объем.'),
                    new PositiveOrZero(message: 'Объем не может быть отрицательным.'),
                ],
            ])
            ->add('weightKg', NumberType::class, [
                'required' => true,
                'scale' => 3,
                'invalid_message' => 'Введите число (например 2.5).',
                'constraints' => [
                    new NotBlank(message: 'Введите вес.'),
                    new PositiveOrZero(message: 'Вес не может быть отрицательным.'),
                ],
            ])
            ->add('priceCents', IntegerType::class, [
                'constraints' => [
                    new NotBlank(message: 'Введите цену.'),
                    new PositiveOrZero(message: 'Цена не может быть отрицательной.'),
                ],
                'help' => 'Price in cents (e.g. 1299 = 12.99)',
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false,   // !!! КЛЮЧЕВОЕ
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
//            для того что бы просто вводить текст не загружать именно файл, оставить пока не настрою код с загрузкой фото
//            ->add('image', TextType::class, [
//                'required' => true,
//                'constraints' => [
//                    new NotBlank(message: 'Введите номер/имя картинки (например: 1.jpg).'),
//                    new Length(
//                        max: 255,
//                        maxMessage: 'Имя файла слишком длинное (макс. {{ limit }} символов).'
//                    ),
//                    // опционально: запрет пробелов и странных символов
//                    new Regex(
//                        pattern: '/^[A-Za-z0-9._-]+$/',
//                        message: 'Только латиница/цифры и символы . _ - (без пробелов).'
//                    ),
//                ],
//                'help' => 'Например: 1.jpg или product_12.png (файл должен быть в public/images/products/).',
//            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
