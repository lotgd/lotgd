<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

/**
 * @template TConfig of array<string, mixed> = array<string, mixed>
 * @implements SceneTemplateInterface<TConfig>
 */
class SpecialTemplate implements SceneTemplateInterface, TaggedSceneInterface
{
    /**
     * @uses DefaultSceneTemplate<TConfig>
     */
    use DefaultSceneTemplate;

    public const string SceneTag = "lotgd2.special";

    public function getTag(): string
    {
        return self::SceneTag;
    }
}
