<?php

namespace App\Entity;

use App\Repository\RecipeProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecipeProductRepository::class)]
class RecipeProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Recipes::class, inversedBy: 'recipeProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private $recipe;

    #[ORM\ManyToOne(targetEntity: Products::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $product;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)] // Изменили на decimal с 2 знаками после запятой
    #[Assert\NotBlank]               // Поле не может быть пустым
    #[Assert\GreaterThanOrEqual(0)]  // Значение должно быть >= 0
    private float $quantity = 0.00;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipe(): ?Recipes
    {
        return $this->recipe;
    }

    public function setRecipe(?Recipes $recipe): self
    {
        $this->recipe = $recipe;

        return $this;
    }

    public function getProduct(): ?Products
    {
        return $this->product;
    }

    public function setProduct(?Products $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s',
            $this->getProduct() ? $this->getProduct()->getName() : 'Unknown Product'
        );
/*        return sprintf(
            '%s (Quantity: %d)',
            $this->getProduct() ? $this->getProduct()->getName() : 'Unknown Product',
            $this->getQuantity() ?? 0
        );*/
    }

    public function getUnitMeasurement(): ?UnitMeasurement
    {
        // Получаем unit_measurement из связанного продукта
        return $this->getProduct()->getUnitMeasurement();
    }
}
