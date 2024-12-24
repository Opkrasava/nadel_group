<?php

namespace App\Form;

use App\Entity\Products;
use App\Entity\RecipeProduct;
use App\Entity\UnitMeasurement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RecipeProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Products::class,       // Указываем класс сущности Product
                'choice_label' => 'name',       // Поле, которое будет отображаться
                'label' => 'Product',           // Название поля в форме
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantity',          // Название поля количества
                'attr' => [
                    'min' => 0, // HTML-ограничение в браузере (минимум 0)
                    'step' => 0.01, // Дробные числа с шагом 0.01
                    'required' => 'required', // HTML-валидация на уровне браузера
                ],
                'constraints' => [
                    new Assert\NotBlank([ // Поле не может быть пустым
                        'message' => 'Quantity не может быть пустым.',
                    ]),
                    new Assert\GreaterThanOrEqual([ // Значение должно быть >= 0
                        'value' => 0,
                        'message' => 'Quantity не может быть отрицательным.',
                    ]),
                ]
            ])
            ->add('unitMeasurement', EntityType::class, [
                'class' => unitMeasurement::class,       // Указываем класс сущности Product
                'label' => 'unitMeasurement',          // Название поля количества
                'disabled' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RecipeProduct::class, // Связываем форму с сущностью RecipeProduct
        ]);
    }
}
