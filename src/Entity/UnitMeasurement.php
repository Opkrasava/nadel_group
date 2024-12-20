<?php

namespace App\Entity;

use App\Repository\UnitMeasurementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UnitMeasurementRepository::class)]
class UnitMeasurement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $name = null;

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
    // Реализация метода __toString()
    public function __toString(): string
    {
        return $this->name; // Возвращаем строковое представление единицы измерения
    }
}
