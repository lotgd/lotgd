<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\Fighter;
use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Form\Scene\SceneTemplate\DragonTemplateType;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Character\DragonCounter;
use LotGD2\Game\Character\Equipment;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Character\Stats;
use LotGD2\Game\GameTime\NewDay;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\SceneRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type DragonTemplateConfiguration array{
 *     dragonName: string,
 *     dragonWeapon: string,
 *     text: array{
 *         fightIntro: string,
 *         epilogue: string,
 *     },
 *  }
 * @implements SceneTemplateInterface<DragonTemplateConfiguration>
 */
#[Autoconfigure(public: true)]
#[TemplateType(DragonTemplateType::class)]
class DragonTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;
    use DefaultFightTrait;

    public function __construct(
        readonly private LoggerInterface $logger,
        readonly private EventDispatcherInterface $eventDispatcher,
        readonly private DiceBagInterface $diceBag,
        readonly private AttachmentRepository $attachmentRepository,
        readonly private SceneRepository $sceneRepository,
        readonly private Battle $battle,
        readonly private NewDay $newDay,
        readonly private Health $health,
        readonly private Gold $gold,
        readonly private Stats $stats,
        readonly private Equipment $equipment,
        readonly private DragonCounter $dragonCounter,
    ) {

    }

    public function onSceneChange(): void
    {
        $op = $this->action->getParameter("op") ?? null;

        match($op) {
            default => $this->defaultAction(),
            "start" => $this->startFight(),
            "fight" => $this->fightAction(),
            "epilogue" => $this->epilogueAction(),
        };
    }

    public function defaultAction(): void
    {
        $this->stage->addAction(
            actionGroup: ActionGroup::EMPTY,
            action: new Action(
                scene: $this->scene,
                title: "Seek out {$this->scene->templateConfig["dragonName"]}",
                parameters: [
                    "op" => "start"
                ],
            )
        );
    }

    public function startFight(): void
    {
        $description = $this->scene->templateConfig["text"]["fightIntro"] ?? null;

        $attachment = $this->attachmentRepository->findOneByAttachmentClass(BattleAttachment::class);

        // No attachment - fail early
        if ($attachment === null) {
            $this->logger->critical("Cannot attach attachment " . BattleAttachment::class . ": Not installed.");
            $description = "The dragon suddenly withdraws into the inner caves, closing its path with a 
                huge boulder. You don't much of a choice but to leave, and maybe inform the gods of this unnatural
                behaviour.";

            return;
        }

        $this->stage->clearActionGroups();

        $dragon = new Fighter(
            name: $this->scene->templateConfig["dragonName"] ?? "The Green Dragon",
            level: 18,
            weapon: $this->scene->templateConfig["dragonWeapon"] ?? "Great Flaming Maw",
            health: 160, # 300
            attack: 35, #45,
            defense: 20, #25,
        );

        $battleState = $this->battle->start($dragon, allowFlee: false);
        $params = ["op" => "fight"];
        $this->battle->addFightActions($this->stage, $this->scene, $battleState, $params);

        $this->stage->addAttachment($attachment, data: [
            "battleState" => $battleState,
        ]);

        $this->stage->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.dragonTemplate.startFight",
                text: $description,
                context: [
                    "badGuy" => $dragon,
                ],
            )
        ];
    }

    public function onFightWon(BattleState $battleState): void
    {
        $this->stats->levelUp();
        $this->health->heal();

        $description = <<<TEXT
            With a mighty final blow, {{ badGuy.name }} lets out a tremendous bellow and falls at your feet, dead at last.
            TEXT;

        $this->logger->debug("{$this->character->id}: Victory over the Dragon.");

        $this->stage->addAction(
            ActionGroup::EMPTY,
            new Action(
                scene: $this->scene,
                title: "Continue",
                parameters: [
                    "op" => "epilogue",
                ]
            )
        );

        $this->stage->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.dragonTemplate.fightWon",
                text: $description,
                context: [
                    "badGuy" => $battleState->badGuy,
                ],
            )
        ];
    }

    public function epilogueAction(): void
    {
        $this->stage->clearActionGroups();
        $this->stage->title = "Victory!";
        $description = $this->scene->templateConfig["text"]["epilogue"];
        $this->stage->addAction(ActionGroup::EMPTY, new Action(
            scene: $this->sceneRepository->getDefaultScene(),
            title: "Continue",
        ));

        $this->dragonCounter->dragonCounter++;
        $this->newDay->resetNewDay($this->character);


        $this->stage->paragraphs = [
            new Paragraph(
                id: "lotgd2.paragraph.dragonTemplate.epilogue",
                text: $description,
                context: [],
            )
        ];

        $this->resetCharacter();
    }

    public function resetCharacter(): void
    {
        $this->character->level = 1;
        $this->health->setMaxHealth(10);
        $this->health->setHealth(10);
        $this->stats->setExperience(0);
        $this->stats->setAttack(1);
        $this->stats->setDefense(1);
        $this->equipment->setItemInSlot(Equipment::WeaponSlot, new EquipmentItem(
            name: $this->equipment->getEmptyName(Equipment::WeaponSlot),
            strength: 0,
            value: 0,
        ));
        $this->equipment->setItemInSlot(Equipment::ArmorSlot, new EquipmentItem(
            name: $this->equipment->getEmptyName(Equipment::ArmorSlot),
            strength: 0,
            value: 0,
        ));

        // Later, offer the possibility to change this behaviour by raising events.
        //$this->eventDispatcher->dispatch();
    }
}