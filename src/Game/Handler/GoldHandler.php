<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use JetBrains\PhpStorm\Deprecated;
use LotGD2\Entity\Character\LootPosition;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\FormExtensionEvent;
use LotGD2\Event\LootBagEvent;
use LotGD2\Event\NewEntityEvent;
use LotGD2\Form\GroupedFormType;
use LotGD2\Game\Character\CharacterService;
use LotGD2\Game\GameStateService;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use LotGD2\Game\Scene\SceneTemplate\FightTemplate;
use LotGD2\Twig\Component\Admin\GameSettings;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @phpstan-import-type GameSettingsDataType from GameSettings
 */
readonly class GoldHandler
{
    const string PropertyName = 'gold';
    const string GoldLoot = "lotgd2.loot.Gold";
    const string GoldLootClaimParagraph = "lotgd2.paragraph.Gold.LootBagClaim";
    const string DefaultGoldGameSetting = "lotgd2_gameSetting_defaultGold";
    const string MaxStartGoldGameSetting = "lotgd2_gameSetting_maxGold";
    const string StartGoldScalesWithDragonKillGameSetting = "lotgd2_gameSetting_defaultGoldDkScaling";

    public function __construct(
        private GameStateService $gameStateService,
        private ?LoggerInterface $logger,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        private ?Character $character = null,
    ) {
    }

    public function getGold(?Character $character = null): int
    {
        $character = $character ?? $this->character;
        return $character->getProperty(self::PropertyName, null) ?? 50;
    }

    public function setGold(?Character $character, int $gold): static
    {
        $character = $character ?? $this->character;
        $this->logger->debug("{$character} set new gold amount ($gold). Was {$this->getGold($character)}.");

        $character->setProperty(self::PropertyName, $gold);
        return $this;
    }

    public function addGold(?Character $character, int $gold): static
    {
        $character = $character ?? $this->character;
        $newGoldAmount = $this->getGold($character) + $gold;
        $this->logger->debug("{$character} add gold ($gold). Was {$this->getGold($character)}, is now {$newGoldAmount}");

        $character->setProperty(self::PropertyName, $newGoldAmount);
        return $this;
    }

    #[AsEventListener(FightTemplate::OnLootBagFill)]
    public function onLootBagFill(LootBagEvent $event): void
    {
        $event->lootBag->add(new LootPosition(self::GoldLoot, [
            "minValue" => 0,
            "maxValue" => $event->battleState->badGuy->kwargs["gold"] ?? 1,
        ]));
    }

    #[AsEventListener(FightTemplate::OnLootBagClaim)]
    public function onLootBagClaim(LootBagEvent $event): void
    {
        $lootBag = $event->lootBag;
        $position = $lootBag->get(self::GoldLoot);

        if ($position === null) {
            $this->logger->debug("Impossible to claim gold loot: No gold loot exists.");
            return;
        }

        if (!isset($position->loot["minValue"])) {
            $this->logger->debug("There is no minValue on GoldLoot position. It was probably removed accidentally.");
        }

        if (!isset($position->loot["maxValue"])) {
            $this->logger->debug("There is no minValue on GoldLoot position. It was probably removed accidentally.");
        }

        $goldReward = $lootBag->diceBag->pseudoBell($position->loot["minValue"] ?? 0, $position->loot["maxValue"] ?? 0);

        if ($goldReward > 0) {
            $this->addGold($event->character, $goldReward);

            $event->stage?->addParagraph(new Paragraph(
                self::GoldLootClaimParagraph,
                text: <<<TXT
                    You earn {{ gold }} gold.
                    TXT,
                context: [
                    "gold" => $goldReward,
                ]
            ));
        } else {
            $this->logger->debug("GoldLoot's reward was calculated to 0");
        }
    }

    /**
     * @param FormExtensionEvent<GameSettingsDataType> $event
     * @return void
     */
    #[AsEventListener(event: GameSettings::FormExtensionEventName)]
    public function onGameSettingsFormExtension(FormExtensionEvent $event): void
    {
        $builder = $event->builder;

        $builder->add(
            $builder->create(
                "gold",
                GroupedFormType::class,
                options: [
                    "inherit_data" => true,
                ],
            )
            ->add(
                self::DefaultGoldGameSetting, IntegerType::class, [
                    "label" => "Default Gold a new character should own",
                    "help" => <<<TXT
                Whenever a character is created or reset after a dragon kill, this amount of gold
                is given to him.
                TXT,
                    "constraints" => [
                        new Range(min: 0),
                        new NotBlank(),
                    ],
                    "data" => 50,
                ]
            )
            ->add(
                self::StartGoldScalesWithDragonKillGameSetting, CheckboxType::class, [
                    "required" => false,
                    "label" => "Default Gold scales with the number of dragons killed",
                    "help" => <<<TXT
                Turn this on to scale the start gold of a character after he kills a dragon (dk*startGold)
                TXT,
                    "data" => true,
                ]
            )
            ->add(
                self::MaxStartGoldGameSetting, IntegerType::class, [
                    "label" => "Maximum gold a character receives after a dragon kill",
                    "help" => <<<TXT
                Whenever a character kills a dragon, the amount the character carries gets reset and the
                character receives dk*startGold (if activated above). With this setting, you can add a 
                limit on it.
                TXT,
                    "constraints" => [
                        new Range(min: -1),
                        new NotBlank(),
                    ],
                    "data" => 300,
                ]
            )
        );
    }

    #[AsEventListener(DragonTemplate::OnCharacterReset)]
    public function onCharacterReset(CharacterChangeEvent $event): void
    {
        $startGoldScales = (bool)($this->gameStateService->getSetting(self::StartGoldScalesWithDragonKillGameSetting) ?? true);
        $startGold = (int)($this->gameStateService->getSetting(self::DefaultGoldGameSetting) ?? 50);
        $maxStartGold = (int)($this->gameStateService->getSetting(self::MaxStartGoldGameSetting) ?? 300);
        $dragonKill = (int)($event->parameters[DragonTemplate::OnCharacterResetDragonCounterParameter] ?? 0);

        if ($startGoldScales) {
            $gold = min($maxStartGold, $dragonKill * $startGold);
        } else {
            $gold = $startGold;
        }

        $this->logger->debug("Set default gold after character reset.");
        $this->setGold($event->character, $gold);
    }

    /**
     * @param NewEntityEvent<Character> $event
     * @return void
     */
    #[AsEventListener(CharacterService::NewCharacterEventName)]
    public function onCharacterCreation(NewEntityEvent $event): void
    {
        $character = $event->entity;
        if (!($character instanceof Character)) {
            return;
        }

        $startGold = (int)($this->gameStateService->getSetting(self::DefaultGoldGameSetting) ?? 50);

        $this->logger->debug("Set default gold after character was created.");
        $this->setGold($character, $startGold);
    }
}
