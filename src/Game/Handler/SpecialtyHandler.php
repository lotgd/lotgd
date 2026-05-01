<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Battle\Buff;
use LotGD2\Entity\Character\CharacterSpecialty;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Specialty;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\BattleNavigationChangeEvent;
use LotGD2\Event\BattleSkillActivationEvent;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\Battle\Battle;
use LotGD2\Game\GameTime\NewDay;
use LotGD2\Game\Random\DiceBagAwareInterface;
use LotGD2\Game\Random\DiceBagAwareTrait;
use LotGD2\Game\Scene\SceneTemplate\FightTemplate;
use LotGD2\Repository\SpecialtyRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Stopwatch\Stopwatch;
use function LotGD2\array_filter_class;

class SpecialtyHandler implements DiceBagAwareInterface
{
    use DiceBagAwareTrait;

    const string ActionGodMode = "lotgd2.action.SpecialtyHandler.godMode";
    const string SpecialtyProperty = "specialties";
    const string MainSpecialtyProperty = "main_specialty";

    public function __construct(
        private LoggerInterface $logger,
        private Security $security,
        private readonly Stopwatch $stopWatch,
        private readonly SpecialtyRepository $specialtyRepository,
    ) {

    }

    /**
     * Returns the Character's CharacterSpecialty with levels and uses
     * @param Character $character
     * @return CharacterSpecialty|null
     */
    public function getMainCharacterSpecialty(Character $character): ?CharacterSpecialty
    {
        $id = $character->getProperty(self::MainSpecialtyProperty, null);

        if ($id === null) {
            return null;
        }

        $specialties = $this->getSpecialties($character);

        if (!isset($specialties[$id])) {
            return null;
        }

        $characterSpecialty = $specialties[$id];

        if ($characterSpecialty instanceof CharacterSpecialty) {
            return $characterSpecialty;
        } else {
            return null;
        }
    }

    /**
     * Returns the Character's selected Specialty.
     * @param Character $character
     * @return Specialty|null
     */
    public function getMainSpecialty(Character $character): ?Specialty
    {
        $id = $character->getProperty(self::MainSpecialtyProperty, null);

        if ($id === null) {
            return null;
        }

        $specialtyEntity = $this->specialtyRepository->find($id);

        if ($specialtyEntity === null) {
            return null;
        }

        return $specialtyEntity;
    }

    /**
     * Sets the Character's main Specialty
     * @param Character $character
     * @param Specialty $specialty
     * @return void
     */
    public function setMainSpecialty(Character $character, Specialty $specialty): void
    {
        $specialties = $this->getSpecialties($character);

        if (!isset($specialties[$specialty->id])) {
            $specialties[$specialty->id] = new CharacterSpecialty($specialty);
        }

        $character->setProperty(self::MainSpecialtyProperty, $specialty->id);
        $character->setProperty(self::SpecialtyProperty, $specialties);
    }

    /**
     * Returns true of the character has a main Specialty.
     *
     * @param Character $character
     * @param Specialty|null $specialty
     * @return bool
     */
    public function hasMainSpecialty(Character $character, ?Specialty $specialty = null): bool
    {
        $id = $character->getProperty(self::MainSpecialtyProperty, null);

        if ($specialty === null) {
            if ($id === null) {
                return false;
            }

            $specialtyEntity = $this->specialtyRepository->find($id);
            if ($specialtyEntity === null) {
                $this->logger->warning("{$character} has a Specialty with id {$id} that cannot be found in the database.");
                return false;
            } else {
                return true;
            }
        } else {
            return $id !== null && $id === $specialty->id;
        }
    }

    /**
     * Returns a given specialty for a character or null if he doesn't have it yet.
     * @param Character $character
     * @param Specialty|null $specialty
     * @return CharacterSpecialty|null
     */
    public function getSpecialty(Character $character, ?Specialty $specialty = null): ?CharacterSpecialty
    {
        if ($specialty === null) {
            return null;
        }

        $specialties = $this->getSpecialties($character);
        if (!isset($specialties[$specialty->id])) {
            return null;
        } else {
            return $specialties[$specialty->id];
        }
    }

    /**
     * @param Character $character
     * @return list<CharacterSpecialty>
     */
    public function getSpecialties(Character $character): array
    {
        $specialties = $character->getProperty(self::SpecialtyProperty, []);
        return array_filter_class($specialties, CharacterSpecialty::class);
    }

    /**
     * Listens to the new day and refreshes specialty uses of all specialties given to a user.
     * @param StageChangeEvent $event
     * @return void
     */
    #[AsEventListener(event: NewDay::OnNewDayAfter, priority: -10)]
    public function onNewDayAfterEvent(StageChangeEvent $event): void
    {
        $character = $event->character;
        $specialties = $this->getSpecialties($character);
        $mainSpecialty = $this->getMainCharacterSpecialty($character);

        // Add somewhere a setting for these
        $usesPerLevel = 3;
        $extraUsesOnMain = 1;

        foreach ($specialties as $specialty) {
            $isMainSpecialty = $specialty === $mainSpecialty;

            // Reset first to 0
            $specialty->uses = 0;

            // Add uses-per-level
            $uses = intdiv($specialty->level, $usesPerLevel);
            $specialty->uses += $uses;

            if ($isMainSpecialty) {
                $specialty->uses += $extraUsesOnMain;
            }

            $this->logger->debug("{$character} receives {$specialty->uses} ({$uses} for the level)", context: [
                "specialty" => $specialty,
                "isMainSpecialty" => $isMainSpecialty,
                "usesPerLevel" => $uses,
                "extraUsesOnMain" => $extraUsesOnMain,
            ]);

            if ($isMainSpecialty) {
                $event->stage->addParagraph(new Paragraph(
                    "lotgd2.paragraph.StandardRace.onNewDay",
                    text: <<<TXT
                    For being interested in {{ specialty }}, you receive {{ extraUsesOnMain }} extra use for today.
                    TXT,
                    context: [
                        "specialty" => $specialty->name,
                        "extraUsesOnMain" => $extraUsesOnMain,
                    ],
                ));
            }

            // No message for side specialties
        }
    }

    /**
     * Listens to the OnNewDayBefore event and permits the character to select a specialty
     * @param StageChangeEvent $event
     * @return void
     */
    #[AsEventListener(event: NewDay::OnNewDayBefore, priority: 80)]
    public function onNewDayEvent(StageChangeEvent $event): void
    {
        $this->stopWatch->start("lotgd2.Specialty.onNewDay", "lotgd");

        $character = $event->character;
        $stop = false;

        if (isset($event->action->parameters["specialty"])) {
            $stop = $this->doSpecialtySelection($event);
        }

        if ($this->hasMainSpecialty($character) === false) {
            $stop = $this->showSpecialtySelection($event);
        }

        if ($stop) {
            $event->setStopRender();
        }

        $this->stopWatch->stop("lotgd2.Specialty.onNewDay");
    }

    /**
     * Selects the specialty and sets the character's property to it.
     *
     * If the specialty 'disappears' between selections, it will return false and the specialty selection screen is displayed
     *  again.
     * @param StageChangeEvent $event
     * @return bool
     */
    public function doSpecialtySelection(StageChangeEvent $event): bool
    {
        $specialtyId = $event->action->parameters["specialty"];
        $specialty = $this->specialtyRepository->find($specialtyId);

        if ($specialty instanceof Specialty === false) {
            // Specialty disappeared, or the selection was faulty, let continue to the character screen
            return false;
        }

        $event->stage->title = "Childhood memories";
        $event->stage->clearParagraphs();
        $event->stage->addParagraph(new Paragraph(
            id: "lotdg2.paragraph.Specialty.onSpecialtySelection",
            text: $specialty->selectionText,
        ));

        $this->setMainSpecialty($event->character, $specialty);
        $event->addAction(ActionGroup::EMPTY, new Action(title: "Continue"));

        return true;
    }

    /**
     * Adds all specialties with a short description to the stage's description and adds for each one an action to let the
     *  character choose.
     *
     * If there are no specialties available, the event will be skipped.
     * @param StageChangeEvent $event
     * @return bool
     */
    public function showSpecialtySelection(StageChangeEvent $event): bool
    {
        $specialties = $this->specialtyRepository->findAll();

        if (count($specialties) === 0) {
            // If there are no specialties to choose from, continue and skip this event
            $this->logger->info("There are no specialties available. Skipping specialty selection.");
            return false;
        }

        $event->stage->clear();
        $event->stage->title = "Childhood memories";
        $event->stage->addParagraph(new Paragraph(
                id: "lotdg2.paragraph.Specialty.generic",
                text: "Growing up as a child, you remember ...")
        );

        $actionGroup = new ActionGroup(
            id: "lotgd2.actionGroup.Specialty",
            title: "Pick one",
        );

        $event->stage->addActionGroup($actionGroup);

        foreach ($specialties as $specialty) {
            $event->stage->addParagraph(new Paragraph(
                id: "lotgd2.paragraph.Specialty.entry{$specialty->id}",
                text: $specialty->description
            ));

            $event->addAction($actionGroup, new Action(
                title: $specialty->name,
                parameters: [
                    "specialty" => $specialty->id,
                ],
                reference: "lotgd2.action.Specialty.entry{$specialty->id}"
            ));
        }

        return true;
    }

    #[AsEventListener(Battle::OnAddFightActions)]
    public function onBattleNavigationAddSkills(BattleNavigationChangeEvent $event): void
    {
        $character = $event->character;
        $specialties = $this->getSpecialties($character);

        foreach ($specialties as $specialty) {
            if ($specialty->uses <= 0) {
                // Skil if there are no uses available
                continue;
            }

            // Separate query for each specialty is okay, because that is usually just 1
            $specialtyEntity = $this->specialtyRepository->find($specialty->id);
            $specialtySkills = $specialtyEntity->skills;

            $specialtyActionGroup = new ActionGroup(
                id: "lotgd2.actionGroup.Specialty.{$specialty->id}",
                title: "{$specialty->name} (Uses: {$specialty->uses})",
                weight: 100,
            );

            $addedSkillCount = 0;
            foreach ($specialtySkills as $key => $specialtySkill) {
                if ($specialtySkill->costs > $specialty->uses) {
                    // Do not add skill if there are not enough
                    continue;
                }

                $specialtyActionGroup->addAction($event->getAction(
                    title: $specialtySkill->name . " (Costs: {$specialtySkill->costs})",
                    reference: "lotgd2.action.Specialty.{$specialty->id}.{$key}",
                    parameters: [
                        "how" => "skill",
                        "skill" => "specialty",
                        "specialty" => $specialty->id,
                        "specialty_skill" => $key,
                    ]
                ));

                $addedSkillCount++;
            }

            if ($addedSkillCount > 0) {
                $event->addActionGroup($specialtyActionGroup);
            }
        }
    }

    #[AsEventListener(FightTemplate::OnSkillActivationEvent)]
    public function onSkillActivationEvent(BattleSkillActivationEvent $event): void
    {
        if (!$this->security->isGranted("ROLE_CHEATS_ENABLED")) return;

        if ($event->skillName === "specialty") {
            $specialtyId = $event->action->getParameter("specialty");
            $specialtySkillKey = $event->action->getParameter("specialty_skill");
            $specialtyEntity = $this->specialtyRepository->find($specialtyId);

            $specialtySkills = $specialtyEntity->skills;
            $specialtySkill = null;
            if (isset($specialtySkills[$specialtySkillKey])) {
                $specialtySkill = $specialtySkills[$specialtySkillKey];
            }

            $characterSpecialty = $this->getSpecialty($event->character, $specialtyEntity);

            if (
                $specialtyId === null || $specialtySkillKey === null || $specialtyEntity === null
                || $specialtySkill === null || $characterSpecialty === null
                || $characterSpecialty->uses < $specialtySkill->costs
            ) {
                $this->logger->warning("{$event->character}: Skill transfer was errornous", context: [
                    "parameterId" => $specialtyId,
                    "parameterSkillKey" => $specialtySkillKey,
                    "specialtyEntity" => $specialtyEntity,
                    "specialtySkill" => $specialtySkill,
                    "specialtySkills" => $specialtySkills,
                    "characterSpecialty" => $characterSpecialty,
                ]);

                $event->buff->addBuff($event->character, new Buff(
                    id: "lotgd2.buff.DefaultFightTrait.SkillNotFound",
                    name: "Skill Not Found",
                    activatesAt: Buff::ACTIVATES_ON_ROUNDSTART,
                    rounds: 1,
                    startMessage: "You try to attack {{ badGuy.name }} using complicated movements, but nothing happens.",
                ));

                $event->stopPropagation();
                return;
            }

            // Add proto buff to character
            $protoBuff = $specialtySkill->buff;
            $event->buff->addBuffFromPrototype($event->character, $protoBuff);

            // Substract the costs from the uses
            $characterSpecialty->uses -= $specialtySkill->costs;

            $event->stopPropagation();
        }
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
    public function onCheatSkillActivationEvent(BattleSkillActivationEvent $event): void
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