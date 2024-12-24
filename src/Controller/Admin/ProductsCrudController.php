<?php

namespace App\Controller\Admin;

use App\Entity\Products;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Products::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Product Name'),
            AssociationField::new('category', 'Category')->formatValue(function ($value, $entity) {
                return $entity->getCategory()->getName(); // Отображаем имя категории
            }),
            TextField::new('productSku', 'SKU'),
            NumberField::new('cost', 'Cost')
                ->setFormTypeOption('attr', ['min' => 0]), // Ограничение в форме
            NumberField::new('quantity', 'Quantity')
                ->setFormTypeOption('attr', ['min' => 0]), // Ограничение в форме,
            AssociationField::new('unitMeasurement', 'Unit Measurement')->formatValue(function ($value, $entity) {
                return $entity->getUnitMeasurement()->getName();
            }),
        ];
    }

}
