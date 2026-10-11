<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Entity\Character\LootPosition;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\CharacterChangeEvent;
use LotGD2\Event\LootBagEvent;
use LotGD2\Game\Scene\SceneTemplate\DragonTemplate;
use LotGD2\Game\Scene\SceneTemplate\FightTemplate;
use LotGD2\Game\Scene\SceneTemplate\TrainingTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class StatsHandler
{
    const string ExperiencePropertyName = 'experience';
    const string LevelPropertyName = 'level';
    const string AttackPropertyName = 'attack';
    const string DefensePropertyName = 'defense';
    const string ExperienceLoot = "lotgd2.loot.Stats.experience";
    const string ExperienceLootClaimParagraph = "lotgd2.paragraph.Sstats.LootBagClaim";

    public function __construct(
        private ?LoggerInterface $logger,
        private EquipmentHandler $equipment,
    ) {
    }

    public function getExperience(Character $character): int
    {
        return $character->getProperty(self::ExperiencePropertyName, 0);
    }

    public function setExperience(Character $character, int $experience): static
    {
        $this->logger?->debug("{$character}: set experience to {$experience}");
        $character->setProperty(self::ExperiencePropertyName, $experience);
        return $this;
    }

    public function addExperience(Character $character, int $experience): static
    {
        $this->setExperience($character, $this->getExperience($character) + $experience);
        return $this;
    }

    public function getRequiredExperience(Character $character): ?int
    {
        $requiredExperience = [
            100,
            400,
            1002,
            1912,
            3140,
            4707,
            6641,
            8985,
            11795,
            15143,
            19121,
            23840,
            29437,
            36071,
            43930,
        ];

        return $requiredExperience[$character->level - 1] ?? null;
    }

    public function getLevel(Character $character): int
    {
        return $character->level;
    }

    public function getAttack(Character $character): int
    {
        return $character->getProperty(self::AttackPropertyName, 1);
    }

    public function getTotalAttack(Character $character): int
    {
        return $this->getAttack($character) + ($this->equipment->getItemInSlot($character, EquipmentHandler::WeaponSlot)?->getStrength() ?? 0);
    }

    public function setAttack(Character $character, int $attack): static
    {
        $this->logger?->debug("{$character}: attack set to {$attack} (was {$this->getAttack($character)}) before).");
        $character->setProperty(self::AttackPropertyName, $attack);
        return $this;
    }

    public function addAttack(Character $character, int $attack): static
    {
        $this->setAttack($character, $this->getAttack($character) + $attack);
        return $this;
    }

    public function getDefense(Character $character): int
    {
        return $character->getProperty(self::DefensePropertyName, 1);
    }

    public function setDefense(Character $character, int $defense): static
    {
        $this->logger?->debug("{$character}: defense set to {$defense} (was {$this->getDefense($character)}) before).");
        $character->setProperty(self::DefensePropertyName, $defense);
        return $this;
    }

    public function addDefense(Character $character, int $defense): static
    {
        $this->setDefense($character, $this->getDefense($character) + $defense);
        return $this;
    }

    public function getTotalDefense(Character $character): int
    {
        return $this->getDefense($character) + ($this->equipment->getItemInSlot($character, EquipmentHandler::ArmorSlot)?->getStrength() ?? 0);
    }

    #[AsEventListener(event: TrainingTemplate::OnCharacterLevelUp)]
    public function onCharacterLevelIncrease(CharacterChangeEvent $event): void
    {
        $deltaLevel = $event->character->level - $event->characterBefore->level;

        $this->addAttack($event->character, 1*$deltaLevel);
        $this->addDefense($event->character, 1*$deltaLevel);
    }

    #[AsEventListener(event: DragonTemplate::OnCharacterReset)]
    public function onCharacterReset(CharacterChangeEvent $event): void
    {
        $deltaLevel = $event->characterBefore->level - $event->character->level;

        if ($deltaLevel > 0) {
            $deltaIncrease = $deltaLevel;
            $this->addAttack($event->character, -$deltaIncrease);
            $this->addDefense($event->character, -$deltaIncrease);
        }

        $this->setExperience($event->character, 0);
    }

    #[AsEventListener(FightTemplate::OnLootBagFill)]
    public function onLootBagFill(LootBagEvent $event): void
    {
        $experience = $event->battleState->badGuy->kwargs["experience"] ?? 1;

        $event->lootBag->add(new LootPosition(self::ExperienceLoot, [
            "experience" => $experience,
            "experienceFlux" => 0.1,
            "experienceBonus" => 0.25,
        ]));
    }

    #[AsEventListener(FightTemplate::OnLootBagClaim)]
    public function onLootBagClaim(LootBagEvent $event): void
    {
        $lootBag = $event->lootBag;
        $position = $lootBag->get(self::ExperienceLoot);

        if ($position === null) {
            $this->logger?->debug("Impossible to claim experience loot: No experience loot exists.");
            return;
        }

        if (!isset($position->loot["experience"])) {
            $this->logger?->debug("There is no experience on ExperienceLoot position. It was probably removed accidentally. Set to 1.");
        }

        if (!isset($position->loot["experienceFlux"])) {
            $this->logger?->debug("There is no experienceFlux on ExperienceLoot position. It was probably removed accidentally. Set to 0.1");
        }

        if (!isset($position->loot["experienceBonus"])) {
            $this->logger?->debug("There is no experienceBonus on ExperienceLoot position. It was probably removed accidentally. Set to 0.25");
        }

        $experience = $position->loot["experience"] ?? 1;
        $experienceFlux = $position->loot["experienceFlux"] ?? 0.1;
        $experienceBonus = $position->loot["experienceBonus"] ?? 0.25;

        $experienceFlux = (int)round($experience * $experienceFlux);
        $experienceFlux = $event->lootBag->diceBag->pseudoBell(-$experienceFlux, $experienceFlux);

        // $deltaLevel is positive of BadGuy was stronger, and negative of BadGuy was weaker.
        $deltaLevel = $event->battleState->badGuy->level - $event->character->level;
        $experienceBonus = $experienceBonus * $experience * $deltaLevel;

        $totalExperience = (int)round($experience + $experienceFlux + $experienceBonus);

        $this->logger?->debug("Experience reward: {$totalExperience} total experience", [
            "experience" => $experience,
            "experienceFlux" => $experienceFlux,
            "experienceBonus" => $experienceBonus,
        ]);

        $this->addExperience($event->character, $totalExperience);

        $event->stage?->addParagraph(new Paragraph(
            self::ExperienceLootClaimParagraph,
            text: <<<TXT
                {% if bonusExperience < 0 %}
                    Due to how easy this fight was, you earn {{ bonusExperience|abs }} less experience points. In total, you earn {{ experience }} experience points!
                {% elseif bonusExperience > 0 %}
                    Due to how difficult this fight was, you earn additional {{ bonusExperience }} experience points. In total, you earn {{ experience }} experience points!
                {% else %}
                    You earn {{ experience }} experience points!
                {% endif %}
                TXT,
            context: [
                "bonusExperience" => $experienceBonus,
                "experience" => $totalExperience,
            ]
        ));
    }
}