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
            "name" => ["Name", $this->character->name],
            "race" => ["Race", $this->handlerRegistry->get(RaceHandler::class)->getRaceName($this->character)],
            "level" => ["Level", $this->character->level],
            "experience" => ["Experience", $this->handlerRegistry->get(StatsHandler::class)->getExperience($this->character), $this->handlerRegistry->get(StatsHandler::class)->getRequiredExperience($this->character)],
            "turns" => ["Turns", $this->handlerRegistry->get(HealthHandler::class)->getTurns($this->character), $this->handlerRegistry->get(HealthHandler::class)->getMaxTurns($this->character)],
            "health" => ["Health", $this->handlerRegistry->get(HealthHandler::class)->getHealth($this->character), $this->handlerRegistry->get(HealthHandler::class)->getMaxHealth($this->character)],
            ["Stats"],
            "attack" => ["Attack", $this->handlerRegistry->get(StatsHandler::class)->getTotalAttack($this->character)],
            "defense" => ["Defense", $this->handlerRegistry->get(StatsHandler::class)->getTotalDefense($this->character)],
            ["Inventory"],
            "gold" => ["Gold in Hand", $this->handlerRegistry->get(WealthHandler::class)->getGold($this->character)],
            "weapon" => ["Weapon", $this->handlerRegistry->get(EquipmentHandler::class)->getItemInSlot($this->character, EquipmentHandler::WeaponSlot)?->getName() ?? "Fists"],
            "armor" => ["Armor", $this->handlerRegistry->get(EquipmentHandler::class)->getItemInSlot($this->character, EquipmentHandler::ArmorSlot)?->getName() ?? "T-Shirt"],
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
