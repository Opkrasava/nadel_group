<?php

namespace App\Entity;

use App\Repository\RecipeHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipeHistoryRepository::class)]
class RecipeHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Recipes::class)]
    private Recipes $recipe;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $changedAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $description;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipe(): Recipes
    {
        return $this->recipe;
    }

    public function setRecipe(Recipes $recipe): self
    {
        $this->recipe = $recipe;
        return $this;
    }

    public function getChangedAt(): \DateTimeInterface
    {
        return $this->changedAt;
    }

    public function setChangedAt(\DateTimeInterface $changedAt): self
    {
        $this->changedAt = $changedAt;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
}
