<?php

namespace App\Form;

use App\Entity\Products;
use App\Entity\RecipeProduct;
use App\Entity\UnitMeasurement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',          // Название поля количества
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
