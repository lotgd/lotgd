<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

class SpecialTemplate implements SceneTemplateInterface, TaggedSceneInterface
{
    use DefaultSceneTemplate;

    public const string SceneTag = "lotgd2.special";

    public function getTag(): string
    {
        return self::SceneTag;
    }
}
