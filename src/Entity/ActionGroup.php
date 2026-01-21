<?php
declare(strict_types=1);

namespace LotGD2\Entity;

use Traversable;
use TypeError;

class ActionGroup
{
    const HIDDEN = "lotgd.actionGroup.hidden";
    const EMPTY = "lotgd.actionGroup.empty";

    /** @var array<string, Action> */
    public array $actions = [];

    /** @var array<string, string> */
    public array $actionsByReference = [];

    /**
     * @param Action[] $actions
     */
    public function __construct(
        private(set) ?string $id = null,
        private(set) ?string $title = null,
        private int $weight = 0,
        array $actions = [],
    ) {
        $this->setActions($actions);
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): string
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
        $this->actions[$action->id] = $action;

        if ($action->reference) {
            if (isset($this->actionsByReference[$action->reference])) {
                throw new TypeError("An action reference must be unique within an action group. The reference {$action->reference} already exists.");
            }

            $this->actionsByReference[$action->reference] = $action->id;
        }

        return $this;
    }

    public function getActionByReference(string $reference): ?Action
    {
        $actionId = $this->actionsByReference[$reference] ?? null;
        if ($actionId !== null and isset($this->actions[$actionId])) {
            return $this->actions[$actionId];
        }

        return null;
    }

    public function getActionById(string $id): ?Action
    {
        return $this->actions[$id] ?? null;
    }

    /**
     * @param list<Action> $actions
     * @return $this
     */
    public function setActions(array $actions): self
    {
        $this->actions = [];
        $this->actionsByReference = [];

        foreach ($actions as $action) {
            if (!$action instanceof Action) {
                throw new TypeError("All array elements must be an instance of ".Action::class);
            }

            $this->addAction($action);
        }

        return $this;
    }

    /**
     * @return Action[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }
}