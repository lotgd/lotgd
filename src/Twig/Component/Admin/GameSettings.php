<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Admin;

use LotGD2\Form\GameSettingsType;
use LotGD2\Repository\GameStateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @phpstan-type GameSettingsDataType array<string, mixed>
 */
#[AsLiveComponent]
#[IsGranted("ROLE_ADMIN")]
class GameSettings extends AbstractController
{
    use DefaultActionTrait;

    const string FormExtensionEventName = "lotgd2.GameSettings.FormExtension";

    public function __construct(
        private readonly GameStateRepository $gameStateRepository,
    ) {

    }

    /**
     * @return array<string, mixed>
     */
    public function getGameSettings(): array
    {
        $settings = $this->gameStateRepository->getAllSettings();
        $renamedSettings = [];
        foreach ($settings as $key => $setting) {
            $renamedSettings[str_replace(".", "_", $key)] = $setting->state;
        }

        return $renamedSettings;
    }
}