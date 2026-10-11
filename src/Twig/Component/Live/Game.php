<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Live;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Handler\EquipmentHandler;
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
        private readonly RaceRepository $raceRepository,
        private readonly GameStateService $gameStateService,
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
        $health = new HealthHandler($this->gameStateService, null);
        $equipment = new EquipmentHandler(null);
        $stats = new StatsHandler(null, $equipment, $this->character);
        $gold = new WealthHandler($this->gameStateService, null, $this->character);
        $race = new RaceHandler(null, null, null, $this->raceRepository);

        return [
            ["Character"],
            ["Name", $this->character->name],
            ["Race", $race->getRaceName($this->character)],
            ["Level", $this->character->level],
            ["Experience", $stats->getExperience()],
            ["Turns", $health->getTurns($this->character), $health->getMaxTurns($this->character)],
            ["Health", $health->getHealth($this->character), $health->getMaxHealth($this->character)],
            ["Stats"],
            ["Attack", $stats->getTotalAttack()],
            ["Defense", $stats->getTotalDefense()],
            ["Inventory"],
            ["Gold in Hand", $gold->getGold($this->character)],
            ["Weapon", $equipment->getItemInSlot($this->character, EquipmentHandler::WeaponSlot)?->getName() ?? "Fists"],
            ["Armor", $equipment->getItemInSlot($this->character, EquipmentHandler::ArmorSlot)?->getName() ?? "T-Shirt"],
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
