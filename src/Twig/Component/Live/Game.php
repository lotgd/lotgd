<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\Live;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
use LotGD2\Game\GameLoop;
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
        $health = new Health(null, $this->character);
        $equipment = new Equipment(null, $this->character);
        $stats = new Stats(null, $equipment, $health, $this->character);
        $gold = new Gold(null, $this->character);

        return [
            ["Character"],
            ["Name", $this->character->getName()],
            ["Level", $this->character->getLevel()],
            ["Experience", $stats->getExperience()],
            ["Health", $health->getHealth(), $health->getMaxHealth()],
            ["Stats"],
            ["Attack", $stats->getTotalAttack()],
            ["Defense", $stats->getTotalDefense()],
            ["Inventory"],
            ["Gold in Hand", $gold->getGold()],
            ["Weapon", $equipment->getItemInSlot(Equipment::WeaponSlot)?->getName() ?? "Fists"],
            ["Armor", $equipment->getItemInSlot(Equipment::ArmorSlot)?->getName() ?? "T-Shirt"],
        ];
    }

    #[LiveAction]
    #[LiveListener("takeAction")]
    public function takeAction(
        #[LiveArg]
        string $actionId,
    ): void {
        $this->game->setCharacter($this->character);
        $stage = $this->game->takeAction($this->character, $actionId);
    }
}
