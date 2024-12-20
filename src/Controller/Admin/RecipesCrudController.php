<?php

namespace App\Controller\Admin;

use App\Entity\Products;
use App\Entity\RecipeHistory;
use App\Entity\RecipeProduct;
use App\Entity\Recipes;
use App\Form\RecipeProductType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RecipesCrudController extends AbstractCrudController
{

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Recipes) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();
        $changes = [];

        // Сравниваем текущие данные с оригинальными
        foreach ($entityInstance->getRecipeProducts() as $recipeProduct) {
            $originalData = $unitOfWork->getOriginalEntityData($recipeProduct);

            $originalQuantity = $originalData['quantity'] ?? null;
            $newQuantity = $recipeProduct->getQuantity();

            if ($originalQuantity !== $newQuantity) {
                $product = $recipeProduct->getProduct();
                $changes[] = sprintf(
                    'Продукт "%s" количество изменено с %d на %d',
                    $product->getName(),
                    $originalQuantity,
                    $newQuantity
                );
            }
        }

        // Записываем изменения в историю, если они есть
        foreach ($changes as $change) {
            $history = new RecipeHistory();
            $history->setRecipe($entityInstance)
                ->setChangedAt(new \DateTime())
                ->setDescription($change);
            $entityManager->persist($history);
        }

        // Обновляем сущность только если были изменения
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }



    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Recipes) {
            return;
        }

        // Очищаем зависимости
        foreach ($entityInstance->getRecipeProducts() as $recipeProduct) {
            $entityManager->remove($recipeProduct);
        }

        // Удаляем сам рецепт
        $entityManager->remove($entityInstance);
        $entityManager->flush();
    }

    public function confirmRecipe(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, EntityManagerInterface $entityManager): RedirectResponse
    {
        $recipe = $context->getEntity()->getInstance();

        if ($recipe instanceof Recipes) {
            $insufficientProducts = []; // Продукты с недостаточным количеством

            // Берём данные продуктов из базы
            foreach ($recipe->getRecipeProducts() as $recipeProduct) {
                $product = $recipeProduct->getProduct();
                $requiredQuantity = $recipeProduct->getQuantity();
                $availableQuantity = $product->getQuantity();

                if ($availableQuantity < $requiredQuantity) {
                    $insufficientProducts[] = sprintf(
                        '%s (Требуется: %d, Доступно: %d)',
                        $product->getName(),
                        $requiredQuantity,
                        $availableQuantity
                    );
                }
            }

            // Если недостаточно продуктов, отклоняем подтверждение
            if (!empty($insufficientProducts)) {
                $this->addFlash('danger', sprintf(
                    'Невозможно подтвердить рецепт "%s". Недостаточно следующих продуктов: %s',
                    $recipe->getName(),
                    implode('; ', $insufficientProducts)
                ));

                return new RedirectResponse($adminUrlGenerator->setController(self::class)
                    ->setAction('edit')
                    ->setEntityId($recipe->getId())
                    ->generateUrl());
            }

            // Списываем количество у продуктов
            $changes = [];
            foreach ($recipe->getRecipeProducts() as $recipeProduct) {
                $product = $recipeProduct->getProduct();
                $requiredQuantity = $recipeProduct->getQuantity();

                $product->setQuantity($product->getQuantity() - $requiredQuantity);
                $entityManager->persist($product);

                $changes[] = sprintf(
                    'Продукт "%s" списан в количестве %d. Остаток: %d',
                    $product->getName(),
                    $requiredQuantity,
                    $product->getQuantity()
                );
            }

            // Записываем изменения в историю
            foreach ($changes as $change) {
                $history = new RecipeHistory();
                $history->setRecipe($recipe)
                    ->setChangedAt(new \DateTime())
                    ->setDescription($change);
                $entityManager->persist($history);
            }

            // Устанавливаем статус рецепта
            $recipe->setStatus(Recipes::STATUS_CONFIRMED);
            $entityManager->persist($recipe);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Рецепт "%s" подтверждён.', $recipe->getName()));
        }

        return new RedirectResponse($adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl());
    }

    public function viewRecipe(AdminContext $context, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $recipe = $context->getEntity()->getInstance();

        if (!$recipe) {
            throw $this->createNotFoundException('Рецепт не найден');
        }

        // Получаем историю изменений
        $history = $entityManager->getRepository(RecipeHistory::class)
            ->findBy(['recipe' => $recipe], ['changedAt' => 'DESC']);

        // Генерируем URL для возврата на список рецептов
        $backUrl = $adminUrlGenerator
            ->setController(self::class)
            ->setAction('index')
            ->generateUrl();

        return $this->render('admin/recipe_view.html.twig', [
            'recipe' => $recipe,
            'history' => $history,
            'backUrl' => $backUrl,
        ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        // Кнопка "Подтвердить"
        $confirmAction = Action::new('confirm', 'Подтвердить')
            ->linkToCrudAction('confirmRecipe') // Связываем с методом контроллера
            ->setCssClass('btn btn-success') // CSS-класс для кнопки
            ->displayIf(function ($entity) {
                return $entity->getStatus() === Recipes::STATUS_CREATED; // Показывать, только если статус "Созданный"
            });

        // Кнопка "Просмотр"
        $viewAction = Action::new('view', 'История')
            ->linkToCrudAction('viewRecipe') // Связываем с методом контроллера
            ->setCssClass('btn btn-info'); // CSS-класс для кнопки

        return $actions
            ->add(Crud::PAGE_EDIT, $confirmAction) // Добавляем действие "Подтвердить"
            //->add(Crud::PAGE_INDEX, $viewAction) // Добавляем "Просмотр" на список
            ->add(Crud::PAGE_EDIT, $viewAction); // Добавляем "Просмотр" на редактирование
    }

    public static function getEntityFqcn(): string
    {
        return Recipes::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Recipe Name'),
            TextField::new('recipe_sku', 'Recipe SKU'),
            TextField::new('productNames', 'Products') // Используем метод getProductNames
            ->onlyOnIndex() // Отображается только в списке
            ->addCssClass('products-column'), // Добавляем кастомный CSS-класс
            IntegerField::new('productCount', 'Number of Products')
                ->formatValue(function ($value, $entity) {
                    // Подсчитываем количество связанных продуктов
                    return $entity->getRecipeProducts()->count();
                })
                ->onlyOnIndex(), // Отображается только в списке
            CollectionField::new('recipeProducts', 'Products with Quantity')
                ->setEntryType(RecipeProductType::class)
                ->setFormTypeOptions([
                    'by_reference' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                ])
                ->onlyOnForms(),
            MoneyField::new('directCost', 'Direct Cost')
                ->setCurrency('UAH') // Указываем валюту
                ->setStoredAsCents(false) // Указываем, что данные хранятся в стандартных единицах
                ->setNumDecimals(2) // Отображаем 2 знака после запятой
                ->onlyOnIndex(), // Отображается только в списке
            IntegerField::new('status', 'Status')
                ->formatValue(function ($value, $entity) {
                    // Подсчитываем количество связанных продуктов
                    return $entity->getStatusLabel();
                })
                ->onlyOnIndex(), // Отображается только в списке

        ];
    }
}
