<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Admin;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Form\GameSettingsType;
use LotGD2\Game\GameStateService;
use LotGD2\Repository\GameStateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent()]
class GameSettingsForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public array $gameSettings = [];

    #[LiveProp]
    public ?bool $saved = null;

    public function __construct(
        private readonly GameStateRepository $gameStateRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly GameStateService $gameStateService,
    ) {
    }

    public function __invoke(): void
    {
        $this->saved = false;
    }

    #[LiveAction]
    public function save()
    {
        $this->submitForm();

        /** @var array<string, mixed> $data */
        $data = $this->getForm()->getData();

        foreach ($data as $key => $value) {
            $settingsKey = str_replace("_", ".", $key);

            $this->gameStateService->setSetting($settingsKey, $value);
        }

        $this->gameSettings = $data;

        $this->entityManager->flush();
        $this->saved = true;
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(GameSettingsType::class, $this->gameSettings);
    }
}