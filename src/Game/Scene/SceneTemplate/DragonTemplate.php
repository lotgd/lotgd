<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\BattleState;
use LotGD2\Entity\Battle\Fighter;
use LotGD2\Entity\Character\EquipmentItem;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
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
class DragonTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;
    use DefaultFightTrait;

    private ?Stage $stage = null;
    private ?Action $action = null;
    private ?Scene $scene = null;
    private ?Character $character = null;

    public function __construct(
        readonly private LoggerInterface $logger,
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

    public static function validateConfiguration(array $config): array
    {
        $resolver = new OptionsResolver();

        $resolver->define("dragonName")->allowedTypes("string")->default("The Green Dragon");
        $resolver->define("dragonWeapon")->allowedTypes("string")->default("Great Flaming Maw");

        $resolver->setOptions("text",  function (OptionsResolver $resolver): void {
            $resolver
                ->define("fightIntro")
                ->required()
                ->allowedTypes('string')
                ->default(<<<TXT
                    Fighting down every urge to flee, you cautiously enter the cave entrance, intent on catching 
                    the great Green Dragon sleeping, so that you might slay it with a minimum of pain. Sadly, this 
                    is not to be the case, for as you round a corner within the cave you discover the great beast 
                    sitting on its haunches on a huge pile of gold, picking its teeth with a rib.
                TXT);

            $resolver
                ->define("epilogue")
                ->required()
                ->allowedTypes('string')
                ->default(<<<TXT
                    Before you, the great dragon lies immobile, its heavy breathing like acid to your lungs. You are 
                    covered, head to toe, with the foul creature's thick black blood. The great beast begins to move 
                    its mouth.  You spring back, angry at yourself for having been fooled by its ploy of death, 
                    and watch for its huge tail to come sweeping your way. But it does not. Instead the dragon begins to speak.
                    
                    <<Why have you come here mortal? What have I done to you?>> it says with obvious effort. <<Always 
                    my kind are sought out to be destroyed. Why? Because of stories from distant lands that tell of 
                    dragons preying on the weak? I tell you that these stories come only from misunderstanding of us, 
                    and not because we devour your children.>> The beast pauses, breathing heavily before continuing, 
                    <<I will tell you a secret. Behind me now are my eggs. They will hatch, and the young will battle 
                    each other. Only one will survive, but she will be the strongest. She will quickly grow, and be 
                    as powerful as me.>> Breath comes shorter and shallower for the great beast.

                    <<Why do you tell me this? Don't you know that I will destroy your eggs?>> you ask. 

                    <<No, you will not, for I know of one more secret that you do not.>>

                    <<Pray tell oh mighty beast!>>

                    The great beast pauses, gathering the last of its energy. <<Your kind cannot tolerate the blood 
                    of my kind. Even if you survive, you will be a feeble creature, barely able to hold a weapon, 
                    your mind blank of all that you have learned. No, you are no threat to my children, for you are 
                    already dead!>>
                    
                    Realizing that already the edges of your vision are a little dim, you flee from the cave, bound 
                    to reach the healer's hut before it is too late. Somewhere along the way you lose your weapon, 
                    and finally you trip on a stone in a shallow stream, sight now limited to only a small circle 
                    that seems to float around your head. As you lay, staring up through the trees, you think that 
                    nearby you can hear the sounds of the village. Your final thought is that although you defeated 
                    the dragon, you reflect on the irony that it defeated you.

                    As your vision winks out, far away in the dragon's lair, an egg shuffles to its side, and a small 
                    crack appears in its thick leathery skin.

                    You wake up in the midst of some trees.  Nearby you hear the sounds of a village. Dimly you remember 
                    that you are a new warrior, and something of a dangerous Green Dragon that is plaguing the area. 
                    You decide you would like to earn a name for yourself by perhaps some day confronting this vile creature.
                    TXT);
        });

        return $resolver->resolve($config);
    }

    public function setSceneParameter(Stage $stage, Action $action, Scene $scene): void
    {
        $this->stage = $stage;
        $this->action = $action;
        $this->scene = $scene;
        $this->character = $stage->owner;
    }

    public function onSceneChange(Stage $stage, Action $action, Scene $scene): void
    {
        $op = $action->getParameter("op") ?? null;
        $this->setSceneParameter($stage, $action, $scene);

        match($op) {
            default => $this->defaultAction(),
            "start" => $this->startFight(),
            "fight" => $this->fightAction($stage, $action, $scene),
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
        $this->stage->description = $this->scene->templateConfig["text"]["fightIntro"];

        $attachment = $this->attachmentRepository->findOneByAttachmentClass(BattleAttachment::class);

        // No attachment - fail early
        if ($attachment === null) {
            $this->logger->critical("Cannot attach attachment " . BattleAttachment::class . ": Not installed.");
            $this->stage->description = "The dragon suddenly withdraws into the inner caves, closing its path with a 
                huge boulder. You don't much of a choice but to leave, and maybe inform the gods of this unnatural
                behaviour.";

            return;
        }

        $this->stage->clearActionGroups();

        $dragon = new Fighter(
            name: "The Green Dragon",
            level: 18,
            weapon: "Great Flaming Maw",
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
    }

    public function onFightWon(Stage $stage, Action $action, Scene $scene, BattleState $battleState): void
    {
        $this->stats->levelUp();
        $this->health->heal();

        $this->stage->description = <<<TEXT
            With a mighty final blow, The Green Dragon lets out a tremendous bellow and falls at your feet, dead at last.
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
    }

    public function epilogueAction(): void
    {
        $this->stage->clearActionGroups();
        $this->stage->title = "Victory!";
        $this->stage->description = $this->scene->templateConfig["text"]["epilogue"];
        $this->stage->addAction(ActionGroup::EMPTY, new Action(
            scene: $this->sceneRepository->getDefaultScene(),
            title: "Continue",
        ));

        $this->dragonCounter->dragonCounter++;
        $this->newDay->resetNewDay($this->character);

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
    }
}