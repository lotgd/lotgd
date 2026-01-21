<?php
declare(strict_types=1);

namespace LotGD2\Entity;

use Traversable;

class ActionGroup
{
    const HIDDEN = "lotgd.actionGroup.hidden";
    const EMPTY = "lotgd.actionGroup.empty";

    private string $id;
    private ?string $title = null;
    private int $weight = 0;

    /** @var Action[] */
    public array $actions = [];

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function addAction(Action $action): self
    {
        $this->actions[] = $action;
        return $this;
    }

    public function setActions(array $actions): self
    {
        foreach ($actions as $action) {
            if (!$action instanceof Action) {
                throw new \TypeError("All array elements must be an instance of ".Action::class);
            }
        }

        $this->actions = $actions;
        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }
}