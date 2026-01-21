<?php
declare(strict_types=1);

namespace LotGD2\Entity;

# Not mapped
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Param\Param;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;

class Action
{
    public string $id;
    public ?int $sceneId {
        get => $this->sceneId;
        set(null|int|Scene $value) {
            if ($value instanceof Scene) {
                $this->sceneId = $value->id;
            } else {
                $this->sceneId = $value;
            }
        }
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public function __construct(
        ?Scene $scene = null,
        public ?string $title = null,
        public array $parameters = [],
        ?DiceBagInterface $diceBag = null,
        public ?string $reference = null,
    ) {
        if ($diceBag === null) {
            $diceBag = new DiceBag();
        }

        $this->id = $diceBag->getRandomString(8);
        $this->sceneId = $scene;

        $this->setParameters($parameters);
    }

    /**
     * @return array<mixed, scalar>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array<mixed, scalar> $parameters
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getParameter(string $name, mixed $default = null): mixed
    {
        return $this->parameters[$name] ?? $default;
    }

    public function setParameter(string $name, mixed $value): self
    {
        $this->parameters[$name] = $value;
        return $this;
    }
}