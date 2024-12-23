<?php

namespace App\Entity;

use App\Repository\RecipesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipesRepository::class)]
class Recipes
{
    public const STATUS_CREATED = 1;
    public const STATUS_CONFIRMED = 2;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(type: 'integer')]
    private int $status = self::STATUS_CREATED; // По умолчанию статус "созданный"

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $recipe_sku = null;

    #[ORM\OneToMany(mappedBy: 'recipe', targetEntity: RecipeProduct::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $recipeProducts;

    public function __construct()
    {
        $this->recipeProducts = new ArrayCollection();
    }

    public function generateSku(): string
    {
        // Если SKU уже заполнено, возвращаем его
        if (!empty($this->recipe_sku)) {
            return $this->recipe_sku;
        }

        // Проверяем связанные продукты
        /** @var RecipeProduct $recipeProduct */
        foreach ($this->getRecipeProducts() as $recipeProduct) {
            $product_sku = $recipeProduct->getProduct()->getProductSku();

            // Ищем SKU, который начинается с "10"
            if (strpos($product_sku, '10') === 0) {
                return $product_sku;
            }
        }

        // Если подходящий SKU не найден, возвращаем стандартное значение
        return 'DEFAULT-SKU';
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

// Сеттер
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getRecipeSku(): ?string
    {
        return $this->recipe_sku;
    }

    public function setRecipeSku(string $recipe_sku): static
    {
        $this->recipe_sku = $recipe_sku;

        return $this;
    }

    public function getRecipeProducts(): Collection
    {
        return $this->recipeProducts;
    }

    public function addRecipeProduct(RecipeProduct $recipeProduct): self
    {
        if (!$this->recipeProducts->contains($recipeProduct)) {
            $this->recipeProducts[] = $recipeProduct;
            $recipeProduct->setRecipe($this);
        }

        return $this;
    }

    public function removeRecipeProduct(RecipeProduct $recipeProduct): self
    {
        if ($this->recipeProducts->removeElement($recipeProduct)) {
            // set the owning side to null (unless already changed)
            if ($recipeProduct->getRecipe() === $this) {
                $recipeProduct->setRecipe(null);
            }
        }

        return $this;
    }

    public function getProductCount(): int
    {
        return $this->getRecipeProducts()->count(); // Возвращаем количество связанных продуктов
    }

    public function getProductNames(): string
    {
        return implode(', ', $this->getRecipeProducts()->map(function ($recipeProduct) {
            return $recipeProduct->getProduct()->getName();
        })->toArray());
    }

    public function getDirectCost(): float
    {
        return array_sum($this->getRecipeProducts()->map(function ($recipeProduct) {
            return $recipeProduct->getProduct()->getCost() * $recipeProduct->getQuantity();
        })->toArray());
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_CREATED => 'Созданный',
            self::STATUS_CONFIRMED => 'Подтвержденный',
            default => 'Неизвестный',
        };
    }
}
