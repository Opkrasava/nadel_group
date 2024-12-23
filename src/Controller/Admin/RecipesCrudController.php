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
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use http\Env\Request;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RecipesCrudController extends AbstractCrudController
{

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Recipes) {
            return;
        }

        // Генерация SKU, если его нет
        if (empty($entityInstance->getRecipeSku())) {
            $entityInstance->setRecipeSku($entityInstance->generateSku());
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

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

    public function confirmRecipe(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, EntityManagerInterface $entityManager, \Symfony\Component\HttpFoundation\Request $request): RedirectResponse
    {
        $user = $this->getUser();
        $recipe = $context->getEntity()->getInstance();

        if ($recipe instanceof Recipes) {
            $insufficientProducts = []; // Продукты с недостаточным количеством

            // Получаем данные из формы
            $unit = (float) $request->request->get('recipe_unit'); // Берём unit из POST-запроса
            $recipe->setUnit($unit);

            // Проверяем доступность продуктов
            foreach ($recipe->getRecipeProducts() as $recipeProduct) {
                $product = $recipeProduct->getProduct();
                $requiredQuantity = $recipeProduct->getQuantity() * $unit; // Умножаем на unit
                $availableQuantity = $product->getQuantity();

                if ($availableQuantity < $requiredQuantity) {
                    $insufficientProducts[] = sprintf(
                        '%s (Требуется: %.2f, Доступно: %.2f)',
                        $product->getName(),
                        $requiredQuantity,
                        $availableQuantity
                    );
                }
            }

            // Если недостаточно продуктов
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

            // Списываем продукты
            $changes = [];
            foreach ($recipe->getRecipeProducts() as $recipeProduct) {
                $product = $recipeProduct->getProduct();
                $requiredQuantity = $recipeProduct->getQuantity() * $unit; // Умножаем на unit

                $product->setQuantity($product->getQuantity() - $requiredQuantity);
                $entityManager->persist($product);

                $changes[] = sprintf(
                    '%s (%s): Продукт "%s" списан в количестве %.2f. Остаток: %.2f',
                    $user->getUserIdentifier(),
                    $recipe->getComment(),
                    $product->getName(),
                    $requiredQuantity,
                    $product->getQuantity()
                );
            }

            // Записываем историю изменений
            foreach ($changes as $change) {
                $history = new RecipeHistory();
                $history->setRecipe($recipe)
                    ->setChangedAt(new \DateTime())
                    ->setDescription($change);
                $entityManager->persist($history);
            }

            // Обновляем статус рецепта
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
            ->setHtmlAttributes([
                'type' => 'submit',               // Делаем кнопку отправкой формы
                'form' => 'edit-form',            // Привязываем к форме с id="edit-form"
                'class' => 'btn btn-success'      // Добавляем стиль
            ])
            ->setCssClass('btn btn-success js-confirm-action'); // CSS-класс для кнопки

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

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('js/confirm.js'); // Путь относительно папки public
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Recipe Name'),
            TextField::new('recipe_sku', 'Recipe SKU')
                ->setHelp('Оставьте пустым для автогенерации.'),
            NumberField::new('unit', 'Unit')
                ->setNumDecimals(2) // Две цифры после запятой
                ->setHelp('Введите значение с точностью до сотых.')
                ->setRequired(false), // Необязательное поле
            TextField::new('comment', 'Comment') // Добавляем поле комментария
            ->setRequired(false)
                ->setHelp('Добавьте комментарий к рецепту.'),
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
