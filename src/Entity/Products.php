<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[UniqueEntity(fields: ['product_sku'], message: 'Этот SKU уже существует.')] // Валидация на уникальность
class Products
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Categories::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $category;

    #[ORM\Column(length: 50)]
    private ?string $product_sku = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    #[Assert\NotBlank]               // Поле не может быть пустым
    #[Assert\GreaterThanOrEqual(0)]  // Значение должно быть >= 0
    private ?string $cost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\GreaterThanOrEqual(0)]
    private ?string $quantity = null;

    #[ORM\ManyToOne(targetEntity: UnitMeasurement::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $unitMeasurement = null;

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

    public function getCategory(): ?Categories
    {
        return $this->category;
    }

    public function setCategory(?Categories $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getProductSku(): ?string
    {
        return $this->product_sku;
    }

    public function setProductSku(string $product_sku): static
    {
        $this->product_sku = $product_sku;

        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(?string $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(?string $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitMeasurement(): ?UnitMeasurement
    {
        return $this->unitMeasurement;
    }

    public function setUnitMeasurement(UnitMeasurement $unitMeasurement): self
    {
        $this->unitMeasurement = $unitMeasurement;

        return $this;
    }
}
