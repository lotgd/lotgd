<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate\Special;

use LotGD2\Game\Scene\SceneTemplate\SpecialTemplate;
use LotGD2\Service\SpecialService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class StumbleSpecialTemplate extends SpecialTemplate
{
    public function __construct(
        private readonly SpecialService $specialService,
    ) {
    }

    public function onSceneChange(): void
    {
        $this->specialService->addReturnAction($this->stage);
    }
}