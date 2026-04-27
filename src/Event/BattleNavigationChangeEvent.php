<?php
declare(strict_types=1);

namespace LotGD2\Event;

use Doctrine\Common\Collections\ArrayCollection;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use Symfony\Contracts\EventDispatcher\Event;

class BattleNavigationChangeEvent extends Event
{
    /**
     * @param Character $character
     * @param BattleState $battleState
     * @param ActionGroup[] $actionGroups
     */
    public function __construct(
        protected(set) Character $character,
        protected(set) BattleState $battleState,
        protected(set) array $actionGroups,
        protected(set) Scene $scene,
        protected(set) array $actionParams,
    ) {

    }

    /**
     * @param string $title
     * @param string $reference
     * @param array<string, mixed> $parameters
     * @return Action
     */
    public function getAction(string $title, string $reference, array $parameters): Action
    {
        return new Action(
            scene: $this->scene,
            title: $title,
            parameters: [
                ... $parameters,
                ... $this->actionParams,
                "battleState" => $this->battleState
            ],
            reference: $reference,
        );
    }

    /**
     * @param ActionGroup $actionGroup
     * @return void
     */
    public function addActionGroup(ActionGroup $actionGroup): void
    {
        $this->actionGroups[] = $actionGroup;
    }
}