<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Live;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\EquipmentHandler;
use LotGD2\Game\Handler\HandlerRegistry;
use LotGD2\Game\Handler\WealthHandler;
use LotGD2\Game\Handler\HealthHandler;
use LotGD2\Game\Handler\RaceHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\GameLoop;
use LotGD2\Repository\RaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
class Game extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Character $character;

    public function __construct(
        private readonly GameLoop $game,
        private readonly HandlerRegistry $handlerRegistry,
    ) {
    }

    #[ExposeInTemplate]
    public function getStage(): Stage
    {
        return $this->game->getStage($this->character);
    }

    /**
     * @return array<array{string}|array{string, mixed}|array{string, numeric, numeric}>
     */
    #[ExposeInTemplate]
    public function getCharStats(): array
    {
        return [
            ["Character"],
            ["Name", $this->character->name],
            ["Race", $this->handlerRegistry->get(RaceHandler::class)->getRaceName($this->character)],
            ["Level", $this->character->level],
            ["Experience", $this->handlerRegistry->get(StatsHandler::class)->getExperience($this->character)],
            ["Turns", $this->handlerRegistry->get(HealthHandler::class)->getTurns($this->character), $this->handlerRegistry->get(HealthHandler::class)->getMaxTurns($this->character)],
            ["Health", $this->handlerRegistry->get(HealthHandler::class)->getHealth($this->character), $this->handlerRegistry->get(HealthHandler::class)->getMaxHealth($this->character)],
            ["Stats"],
            ["Attack", $this->handlerRegistry->get(StatsHandler::class)->getTotalAttack($this->character)],
            ["Defense", $this->handlerRegistry->get(StatsHandler::class)->getTotalDefense($this->character)],
            ["Inventory"],
            ["Gold in Hand", $this->handlerRegistry->get(WealthHandler::class)->getGold($this->character)],
            ["Weapon", $this->handlerRegistry->get(EquipmentHandler::class)->getItemInSlot($this->character, EquipmentHandler::WeaponSlot)?->getName() ?? "Fists"],
            ["Armor", $this->handlerRegistry->get(EquipmentHandler::class)->getItemInSlot($this->character, EquipmentHandler::ArmorSlot)?->getName() ?? "T-Shirt"],
        ];
    }

    #[LiveAction]
    #[LiveListener("takeAction")]
    public function takeAction(
        #[LiveArg]
        string $actionId,
    ): void {
        if ($this->character === null) {
            throw $this->createNotFoundException("Character disappeared");
        }

        $this->game->setCharacter($this->character);
        $stage = $this->game->takeAction($this->character, $actionId);
    }
}
