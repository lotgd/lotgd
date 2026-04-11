<?php
declare(strict_types=1);

namespace LotGD2\Game\Character;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Game\GameTime\NewDay;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Stage\ActionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @phpstan-type DragonPointChoice array{
 *     choice: string,
 *     age?: int,
 * }&array<string, mixed>
 */
class DragonCounter
{
    const string CounterPropertyName = "dragonCounter";
    const string ChoicePropertyName = "dragonCounterChoice";

    public function __construct(
        readonly private ?LoggerInterface $logger,
        readonly private ?DiceBagInterface $diceBag,
        readonly private ?Stopwatch $stopWatch,
        #[Autowire(expression: "service('lotgd2.game_loop').getCharacter()")]
        readonly private Character $character,
        readonly private Health $health,
        readonly private Stats $stats, private readonly ActionService $actionService,
    ) {
    }

    public int $dragonCounter {
        get {
            return $this->character->getProperty(self::CounterPropertyName, 0) ?? 0;
        }
        set(int $value) {
            $this->logger->debug("{$this->character->id}: Set dragon counter value to {$value}.");
            $this->character->setProperty(self::CounterPropertyName, $value);
        }
    }

    /**
     * @var array<int, DragonPointChoice>
     */
    public array $choices {
        get {
            return $this->character->getProperty(self::ChoicePropertyName, []) ?? [];
        }
        set(array $value) {
            $this->character->setProperty(self::ChoicePropertyName, $value);
        }
    }

    /**
     * @param string $choice
     * @param array<string, mixed> $kwargs
     * @return $this
     */
    public function addChoice(string $choice, array $kwargs = []): self
    {
        $this->logger->debug("{$this->character->id}: Add DragonCounter choice {$choice}.", $kwargs);

        $choices = $this->choices;
        $choices[] = ["choice" => $choice, ... $kwargs];
        $this->choices = $choices;

        return $this;
    }

    #[AsEventListener(event: NewDay::OnNewDayBefore, priority: 100)]
    public function onNewDayEvent(StageChangeEvent $event): void
    {
        $character = $event->character;
        $this->stopWatch->start("lotgd2.DragonCounter.onNewDay");

        // Check if a dragon point was picked
        if (isset($event->action->parameters["dk"])) {
            $success = false;

            switch ($event->action->parameters["dk"]) {
                case "health":
                    $this->health->addMaxHealth(5, $character);
                    $success = true;
                    break;

                case "strength":
                    $this->stats->setAttack($this->stats->getAttack() + 1, $character);
                    $success = true;
                    break;

                case "defense":
                    $this->stats->setDefense($this->stats->getDefense() + 1, $character);
                    $success = true;
                    break;
            }

            // Add the choice to the list of choices
            if ($success) {
                $this->addChoice($event->action->parameters["dk"]);
            }
        }

        // Now calculate how much dragon points are still left
        $dragonPointsLeft = $this->dragonCounter - count($this->choices);

        // If more than 0, change scene
        if ($dragonPointsLeft > 0) {
            // Not enough dragon points - we stop and give choices.
            $stage = $event->stage;

            $stage->title ="Dragon points";

            $stage->paragraphs = [
                new Paragraph(
                    id: "lotgd.paragraph.DragonCounter.dragonPointsLeft",
                    text: <<<TXT
                        You earn one dragon point each time you slay the dragon. 
                        Advancements made by dragon points are permanent!
                        
                        You currently have {{ dragonPointsLeft }} unspent dragon points.
                        How do you wish to spend them?
                        TXT,
                    context: [
                        "dragonPointsLeft" => $dragonPointsLeft,
                    ]
                )
            ];

            $this->actionService->resetActionGroups($stage);

            $event->addAction(ActionGroup::EMPTY, new Action(
                title: "+5 Health",
                parameters: ["dk" => "health"],
                diceBag: $this->diceBag,
                reference: "lotgd2.action.DragonCounter.health",
            ));

            // @ToDo: Add options to add turns

            $event->addAction(ActionGroup::EMPTY, new Action(
                title: "+1 Strength",
                parameters: ["dk" => "strength"],
                diceBag: $this->diceBag,
                reference: "lotgd2.action.DragonCounter.strength",
            ));

            $event->addAction(ActionGroup::EMPTY, new Action(
                title: "+1 Defense",
                parameters: ["dk" => "defense"],
                diceBag: $this->diceBag,
                reference: "lotgd2.action.DragonCounter.defense",
            ));

            // Do _not_ continue to render the new scene.
            $event->setStopRender();
        }

        // All done, rendering can continue
        $this->stopWatch->stop("lotgd2.DragonCounter.onNewDay");
    }
}