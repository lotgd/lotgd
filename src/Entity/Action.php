<?php
declare(strict_types=1);

namespace LotGD2\Entity;

# Not mapped
use LotGD2\Entity\Param\Param;
use LotGD2\Entity\Param\ParamBag;
use LotGD2\Game\Random\DiceBag;

class Action
{
    public ?string $id = null;
    public ?string $title = null;
    /** @var array<string, scalar> */
    public array $parameters = [];
    public ?int $sceneId = null;

    /**
     * @param array<string, scalar> $parameters
     */
    public function __construct(
        ?Scene $scene = null,
        ?string $title = null,
        array $parameters = [],
        ?DiceBag $diceBag = null,
    ) {
        if ($diceBag === null) {
            $diceBag = new DiceBag();
        }

        $this->id = $diceBag->getRandomString(8);

        if ($scene !== null) {
            $this->setSceneId($scene->getId());
        }

        if ($title !== null) {
            $this->setTitle($title);
        }

        $this->setParameters($parameters);
    }

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