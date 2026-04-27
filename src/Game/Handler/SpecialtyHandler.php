<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\Buff;
use LotGD2\Event\BattleNavigationChangeEvent;
use LotGD2\Event\BattleSkillActivationEvent;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneTemplate\DefaultFightTrait;
use LotGD2\Game\Scene\SceneTemplate\FightTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class SpecialtyHandler
{
    const string ActionGodMode = "lotgd2.action.SpecialtyHandler.godMode";

    public function __construct(
        private LoggerInterface $logger, // @phpstan-ignore property.onlyWritten
        private Security $security,
        private ?DiceBagInterface $diceBag = null, // @phpstan-ignore property.onlyWritten
    ) {

    }

    #[AsEventListener(Battle::OnAddFightActions)]
    public function onBattleNavigationAddCheats(BattleNavigationChangeEvent $event): void
    {
        if (!$this->security->isGranted("ROLE_CHEATS_ENABLED")) return;

        $cheatActionGroup = new ActionGroup("nothing", "Cheats", -80);
        $cheatActionGroup->addAction($event->getAction(
            title: "God mode",
            reference: self::ActionGodMode,
            parameters: [
                "how" => "skill",
                "skill" => "godmode",
            ]
        ));

        $event->addActionGroup($cheatActionGroup);
    }

    #[AsEventListener(FightTemplate::OnSkillActivationEvent)]
    public function onSkillActivationEvent(BattleSkillActivationEvent $event): void
    {
        if (!$this->security->isGranted("ROLE_CHEATS_ENABLED")) return;

        if ($event->skillName === "godmode") {
            $this->logger->debug("{$event->character}: Godmode buff called.");

            $event->buff->addBuff($event->character, new Buff(
                id: "lotgd2.buff.DefaultFightTrait.GodMode",
                name: "GOD MODE",
                activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
                rounds: 1,
                startMessage: "You feel god-like",
                endMessage: "You are mortal again",
                goodGuyAttackModifier: 25,
                goodGuyDefenseModifier: 25,
                goodGuyInvulnerable: true,
            ));

            $event->stopPropagation();
        }
    }
}