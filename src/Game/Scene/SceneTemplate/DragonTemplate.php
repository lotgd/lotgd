<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\Fighter;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\SimpleStageParameterEvent;
use LotGD2\Form\Scene\SceneTemplate\DragonTemplateType;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\Handler\DragonCounterHandler;
use LotGD2\Game\Handler\GoldHandler;
use LotGD2\Game\Handler\StatsHandler;
use LotGD2\Game\GameTime\NewDay;
use LotGD2\Game\Scene\SceneAttachment\BattleAttachment;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\SceneRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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

    const string OnCharacterReset = 'lotgd2.event.DragonTemplate.characterReset';

    public function __construct(
        readonly private LoggerInterface $logger,
        readonly private EventDispatcherInterface $eventDispatcher,
        readonly private AttachmentRepository $attachmentRepository,
        readonly private SceneRepository $sceneRepository,
        readonly private Battle $battle,
        readonly private NewDay $newDay,
        readonly private GoldHandler $gold,
        readonly private StatsHandler $stats,
        readonly private DragonCounterHandler $dragonCounter,
        readonly private ActionService $actionService,
    ) {

    }

    public function onSceneChange(): void
    {
        $op = $this->action->getParameter("op") ?? null;

        match($op) {
            default => $this->defaultAction(),
            "start" => $this->startFight(),
            "epilogue" => $this->epilogueAction(),
        };
    }

    public function getStage(): Stage
    {
        return $this->stage;
    }

    public function getScene(): Scene
    {
        return $this->scene;
    }

    public function addDefaultActions(Stage $stage, Scene $scene): void
    {
        $dragonName = $scene->templateConfig["dragonName"] ?? (new DragonTemplateType()->getDefaultData()["dragonName"]);

        $stage->addAction(
            actionGroup: ActionGroup::EMPTY,
            action: new Action(
                scene: $scene,
                title: "Seek out {$dragonName}",
                parameters: [
                    "op" => "start"
                ],
            )
        );
    }

    public function defaultAction(): void
    {
        $this->addDefaultActions($this->stage, $this->scene);
    }

    public function startFight(): void
    {
        $description = $this->scene->templateConfig["text"]["fightIntro"] ?? null;

        $attachment = $this->attachmentRepository->findOneBy(["attachmentClass" => BattleAttachment::class]);

        // No attachment - fail early
        if ($attachment === null) {
            $this->logger->critical("Cannot attach attachment " . BattleAttachment::class . ": Not installed.");

            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd2.paragraph.dragonTemplate.startFightFailed",
                    text: <<<TXT
                        The dragon suddenly withdraws into the inner caves, closing its path with a 
                        huge boulder. You don't much of a choice but to leave, and maybe inform the gods of this unnatural
                        behaviour.
                        TXT,
                )
            ];

            return;
        }

        $this->stage->clearActionGroups();

        $dragon = new Fighter(
            name: $this->scene->templateConfig["dragonName"] ?? "The Green Dragon",
            level: 18,
            weapon: $this->scene->templateConfig["dragonWeapon"] ?? "Great Flaming Maw",
            health: 150, # 300
            attack: 31, #45,
            defense: 15, #25,
        );

        $battleState = $this->battle->start($dragon, allowFlee: false);
        $params = ["op" => "fight"];
        $this->battle->addFightActions($this->stage, $this->scene, $battleState, $params);

        $this->stage->addAttachment($attachment, data: [
            "battleState" => $battleState,
        ]);

        if ($description !== null) {
            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd2.paragraph.dragonTemplate.startFight",
                    text: $description,
                    context: [
                        "badGuy" => $dragon,
                    ],
                )
            ];
        } else {
            $this->stage->paragraphs = [];
        }
    }

    public function onFightWon(SimpleStageParameterEvent $event, BattleState $battleState): void
    {
        $description = <<<TEXT
            With a mighty final blow, {{ badGuy.name }} lets out a tremendous bellow and falls at your feet, dead at last.
            TEXT;

        $this->logger->debug("{$event->character->id}: Victory over the Dragon.");

        $this->actionService->resetActionGroups($event->stage);
        $event->stage->addAction(
            ActionGroup::EMPTY,
            new Action(
                scene: $event->scene,
                title: "Continue",
                parameters: [
                    "op" => "epilogue",
                ]
            )
        );

        $event->stage->paragraphs = [
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
        $this->actionService->resetActionGroups($this->stage);
        $this->stage->title = "Victory!";
        $description = $this->scene->templateConfig["text"]["epilogue"] ?? null;

        $this->stage->addAction(ActionGroup::EMPTY, new Action(
            scene: $this->sceneRepository->getDefaultScene(),
            title: "Continue",
        ));

        $this->dragonCounter->dragonCounter++;
        $this->newDay->resetNewDay($this->character);

        if ($description !== null) {
            $this->stage->paragraphs = [
                new Paragraph(
                    id: "lotgd2.paragraph.dragonTemplate.epilogue",
                    text: $description,
                    context: [],
                )
            ];
        } else {
            $this->stage->paragraphs = [];
        }

        $this->resetCharacter();
    }

    public function resetCharacter(): void
    {
        $characterBefore = clone $this->character;

        $this->character->level = 1;

        $this->eventDispatcher->dispatch(
            event: new CharacterChangeEvent($this->character, $characterBefore),
            eventName: self::OnCharacterReset
        );
    }
}