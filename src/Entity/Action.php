<?php
declare(strict_types=1);

namespace LotGD2\Entity;

# Not mapped
use Random\Randomizer;

class Action
{
    public ?string $id = null;
    public ?string $title = null;
    public array $parameters = [];
    public ?int $sceneId = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setSceneId(?int $sceneId): self
    {
        $this->sceneId = $sceneId;
        return $this;
    }

    public function getSceneId(): int
    {
        return $this->sceneId;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }
}