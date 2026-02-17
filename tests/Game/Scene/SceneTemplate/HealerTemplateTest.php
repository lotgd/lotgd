<?php
declare(strict_types=1);

namespace LotGD2\Tests\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\ActionGroup;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Scene\SceneTemplate\HealerTemplate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[CoversClass(HealerTemplate::class)]
#[UsesClass(Action::class)]
#[UsesClass(ActionGroup::class)]
#[UsesClass(DiceBag::class)]
class HealerTemplateTest extends TestCase
{
    private HealerTemplate $healerTemplate;
    private LoggerInterface|MockObject $logger;
    private Security|MockObject $security;
    private Health|MockObject $health;
    private Gold|MockObject $gold;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->health = $this->createMock(Health::class);
        $this->gold = $this->createMock(Gold::class);

        $this->healerTemplate = new HealerTemplate(
            $this->logger,
            $this->security,
            $this->health,
            $this->gold
        );
    }

    private function getDefaultTemplateConfig(): array
    {
        return [
            "stealHealth" => true,
            "actionGroupPotionTitle" => "Potions",
            "actionCompleteHealingTitle" => "Complete Healing",
            "text" => [
                "onEntryAndDamaged" => "You need healing. Price is {{ price }} gold.",
                "onEntryAndHealthy" => "You are healthy already.",
                "onEntryAndOverhealed" => "You are overhealed!",
                "onHealEnoughGold" => "You have been healed for {{ amount }} points!",
                "onHealNotEnoughGold" => "Not enough gold! Need {{ price }} gold."
            ]
        ];
    }

    public function testOnSceneChangeWithDefaultAction(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createMock(Character::class);

        $action->expects($this->once())
            ->method('getParameter')
            ->with('op')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $this->health->expects($this->atLeastOnce())
            ->method('getHealth')
            ->willReturn(50);

        $this->health->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $this->gold->expects($this->never())
            ->method("getGold");

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("level"))
            ->willReturn(5);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $stage->expects($this->once())
            ->method('addDescription')
            ->with('You need healing. Price is {{ price }} gold.');

        $stage->expects($this->once())
            ->method('addContext')
            ->with('price', $this->isInt());

        $stage->expects($this->once())
            ->method('addActionGroup');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->healerTemplate->onSceneChange($stage, $action, $scene);
    }

    public function testOnSceneChangeWithHealAction(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createMock(Character::class);

        $action->expects($this->exactly(3))
            ->method('getParameter')
            ->willReturnMap([
                ['op', 'heal'],
                ['amount', 50],
                ['price', 100]
            ]);

        $this->logger->expects($this->exactly(2))
            ->method('debug')
            ->willReturnMap([
                ['Called HealerTemplate::defaultAction'],
                ['123: Healed by 50 for 100 gold.'],
            ]);

        $character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("id"))
            ->willReturn(123);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $this->gold->expects($this->once())
            ->method('getGold')
            ->willReturn(150);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $stage->expects($this->once())
            ->method(PropertyHook::set("description"))
            ->with('You have been healed for {{ amount }} points!');

        $stage->expects($this->exactly(2))
            ->method('addContext')
            ->willReturnCallback(function (string $context, mixed $value) use ($stage) {
                if ($context === "price") {
                    $this->assertIsInt($value);

                    return match($value) {
                        100 => $stage,
                        default => throw new \AssertionError("100 expected, got {$value}")
                    };
                } elseif ($context === "amount") {
                    $this->assertIsInt($value);

                    return match($value) {
                        50 => $stage,
                        default => throw new \AssertionError("50 expected, got {$value}")
                    };
                } else {
                    throw new \AssertionError("Unknown context {$context}");
                }
            });

        $this->health->expects($this->once())
            ->method('heal')
            ->with(50);

        $this->gold->expects($this->once())
            ->method('addGold')
            ->with(-100);

        $this->healerTemplate->onSceneChange($stage, $action, $scene);
    }

    public function testOnSceneChangeWithCheatAction(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);

        $action->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['op', 'cheat'],
                ['what', 'heal']
            ]);

        $this->health->expects($this->once())
            ->method('heal')
            ->with();

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $this->health->expects($this->exactly(2))
            ->method('getHealth')
            ->willReturn(100);

        $this->health->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $stage->expects($this->once())
            ->method('addDescription')
            ->with('You are healthy already.');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->healerTemplate->onSceneChange($stage, $action, $scene);
    }

    public function testDefaultActionWhenDead(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(false);

        $stage->expects($this->once())
            ->method('addDescription')
            ->with("You are dead and cannot get healing from the Healer's hut. Try waiting for a new day to continue playing.");

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->healerTemplate->defaultAction($stage, $action, $scene);
    }

    public function testDefaultActionWhenHealthy(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createStub(Action::class);
        $scene = $this->createMock(Scene::class);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $this->health->expects($this->atLeastOnce())
            ->method('getHealth')
            ->willReturn(100);

        $this->health->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $this->gold->expects($this->never())
            ->method('getGold');

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $stage->expects($this->once())
            ->method('addDescription')
            ->with('You are healthy already.');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->healerTemplate->defaultAction($stage, $action, $scene);
    }

    public function testDefaultActionWhenDamaged(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createStub(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createMock(Character::class);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $this->health->expects($this->atLeast(1))
            ->method('getHealth')
            ->willReturn(50);

        $this->health->expects($this->atLeast(1))
            ->method('getMaxHealth')
            ->willReturn(100);

        $this->gold->expects($this->never())
            ->method('getGold');

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("level"))
            ->willReturn(3);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $stage->expects($this->once())
            ->method('addDescription')
            ->with('You need healing. Price is {{ price }} gold.');

        $stage->expects($this->once())
            ->method('addContext')
            ->with('price', $this->isInt());

        $stage->expects($this->once())
            ->method('addActionGroup');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->healerTemplate->defaultAction($stage, $action, $scene);
    }

    public function testDefaultActionWhenOverhealed(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createStub(Action::class);
        $scene = $this->createMock(Scene::class);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $this->health->expects($this->exactly(2))
            ->method('getHealth')
            ->willReturn(150);

        $this->health->expects($this->exactly(2))
            ->method('getMaxHealth')
            ->willReturn(100);

        $this->gold->expects($this->never())
            ->method('getGold');

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $stage->expects($this->once())
            ->method('addDescription')
            ->with('You are overhealed!');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->healerTemplate->defaultAction($stage, $action, $scene);
    }

    public function testDefaultActionWhenOverhealedButStealHealthDisabled(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createStub(Action::class);
        $scene = $this->createMock(Scene::class);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $this->health->expects($this->once())
            ->method('isAlive')
            ->willReturn(true);

        $this->health->expects($this->atLeastOnce())
            ->method('getHealth')
            ->willReturn(150);

        $this->health->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $this->gold->expects($this->never())
            ->method('getGold');

        $templateConfig = $this->getDefaultTemplateConfig();
        $templateConfig["stealHealth"] = false;

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($templateConfig);

        $stage->expects($this->once())
            ->method('addDescription')
            ->with('You are healthy already.');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(false);

        $this->healerTemplate->defaultAction($stage, $action, $scene);
    }

    public function testDefaultActionWithCheats(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createStub(Action::class);
        $scene = $this->createMock(Scene::class);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $this->health->expects($this->atLeastOnce())
            ->method('isAlive')
            ->willReturn(true);

        $this->health->expects($this->atLeastOnce())
            ->method('getHealth')
            ->willReturn(100);

        $this->health->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $this->gold->expects($this->never())
            ->method('getGold');

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $stage->expects($this->once())
            ->method('addDescription')
            ->with('You are healthy already.');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_CHEATS_ENABLED')
            ->willReturn(true);

        $stage->expects($this->once())
            ->method('addActionGroup')
            ->with($this->callback(function (ActionGroup $actionGroup) {
                return $actionGroup->getId() === "lotgd2.actionGroup.healerTemplate.cheats"
                    && $actionGroup->getTitle() === "Cheats"
                    && count($actionGroup->getActions()) === 1;
            }));

        $this->healerTemplate->defaultAction($stage, $action, $scene);
    }

    public function testHealActionWithEnoughGold(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createMock(Character::class);

        $this->logger->expects($this->exactly(2))
            ->method('debug')
            ->willReturnMap([
                ['456: Healed by 30 for 50 gold.'],
                ['Called HealerTemplate::defaultAction']
            ]);

        $this->security->expects($this->never())
            ->method('isGranted');

        $action->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['amount', 30],
                ['price', 50]
            ]);

        $character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("id"))
            ->willReturn(456);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $this->gold->expects($this->once())
            ->method('getGold')
            ->willReturn(100);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $stage->expects($this->once())
            ->method(PropertyHook::set("description"))
            ->with('You have been healed for {{ amount }} points!');

        $stage->expects($this->exactly(2))
            ->method('addContext')
            ->willReturnCallback(function (string $context, mixed $value) use ($stage) {
                if ($context === "price") {
                    if ($value === 50) {
                        return $stage;
                    }
                } elseif ($context === "amount") {
                    if ($value === 30) {
                        return $stage;
                    }
                }
                throw new \AssertionError("Unexpected values [$context,$value] in addContext");
            });

        $this->health->expects($this->once())
            ->method('heal')
            ->with(30);

        $this->gold->expects($this->once())
            ->method('addGold')
            ->with(-50);

        $this->healerTemplate->healAction($stage, $action, $scene);
    }

    public function testHealActionWithNotEnoughGold(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createMock(Character::class);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Called HealerTemplate::defaultAction');

        $action->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['amount', 30],
                ['price', 50]
            ]);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $this->gold->expects($this->once())
            ->method('getGold')
            ->willReturn(20);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $stage->expects($this->once())
            ->method(PropertyHook::set("description"))
            ->with('Not enough gold! Need {{ price }} gold.');

        $stage->expects($this->once())
            ->method('addContext')
            ->with('price', 50);

        $this->health->expects($this->never())
            ->method('heal');

        $this->gold->expects($this->never())
            ->method('addGold');

        $this->healerTemplate->healAction($stage, $action, $scene);
    }

    public function testHealActionWithFreeHealing(): void
    {
        $stage = $this->createMock(Stage::class);
        $action = $this->createMock(Action::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createMock(Character::class);

        $this->logger->expects($this->exactly(2))
            ->method('debug')
            ->willReturnMap([
                ['Called HealerTemplate::defaultAction'],
                ['789: Healed by 30 for 0 gold.'],
            ]);

        $this->security->expects($this->never())->method('isGranted');

        $action->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['amount', 30],
                ['price', 0]
            ]);

        $character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("id"))
            ->willReturn(789);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $stage->expects($this->once())
            ->method(PropertyHook::set("description"))
            ->with('You have been healed for {{ amount }} points!');

        $stage->expects($this->exactly(2))
            ->method('addContext')
            ->willReturnMap([
                ['price', 0, $stage],
                ['amount', 30, $stage],
            ]);

        $this->health->expects($this->once())
            ->method('heal')
            ->with(30);

        $this->gold->expects($this->once())
            ->method('addGold')
            ->with(0);

        $this->healerTemplate->healAction($stage, $action, $scene);
    }

    public function testGetPriceCalculation(): void
    {
        $character = $this->createMock(Character::class);

        $this->logger->expects($this->never())->method('debug');
        $this->security->expects($this->never())->method('isGranted');
        $this->gold->expects($this->never())->method('getGold');

        $character->expects($this->once())
            ->method(PropertyHook::get("level"))
            ->willReturn(5);

        $this->health->expects($this->once())
            ->method('getMaxHealth')
            ->willReturn(100);

        $this->health->expects($this->once())
            ->method('getHealth')
            ->willReturn(60);

        $price = $this->healerTemplate->getPrice($character);

        $this->assertIsInt($price);
        $this->assertGreaterThanOrEqual(0, $price);

        // Price should be calculated as: log(level) * (maxHealth - currentHealth + 10)
        $expectedPrice = (int)round(log(5) * (100 - 60 + 10));
        $this->assertEquals($expectedPrice, $price);
    }

    public function testGetPriceWithLevelZero(): void
    {
        $character = $this->createMock(Character::class);

        $this->logger->expects($this->never())->method('debug');
        $this->security->expects($this->never())->method('isGranted');
        $this->gold->expects($this->never())->method('getGold');

        $character->expects($this->once())
            ->method(PropertyHook::get("level"))
            ->willReturn(0);

        $this->health->expects($this->once())
            ->method('getMaxHealth')
            ->willReturn(100);

        $this->health->expects($this->once())
            ->method('getHealth')
            ->willReturn(50);

        $price = $this->healerTemplate->getPrice($character);

        // With level 0, it should be treated as level 1
        $expectedPrice = (int)round(log(1) * (100 - 50 + 10));
        $this->assertEquals($expectedPrice, $price);
        $this->assertEquals(0, $price); // log(1) = 0
    }

    public function testAddPotionActionsWithFreeHealing(): void
    {
        $stage = $this->createMock(Stage::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createMock(Character::class);

        $this->logger->expects($this->never())->method('debug');
        $this->security->expects($this->never())->method('isGranted');
        $this->gold->expects($this->never())->method('getGold');

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("level"))
            ->willReturn(1);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $this->health->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $this->health->expects($this->exactly(2))
            ->method('getHealth')
            ->willReturn(50);

        // For level 1, price should be 0 (free healing)
        $stage->expects($this->once())
            ->method('addActionGroup')
            ->with($this->callback(function (ActionGroup $actionGroup) {
                $actions = $actionGroup->getActions();
                // Should only have complete healing action (no partial options for free healing)
                return count($actions) === 1 
                    && $actionGroup->getId() === HealerTemplate::ActionGroupPotions
                    && $actionGroup->getTitle() === 'Potions';
            }));

        $this->healerTemplate->addPotionActions($stage, $scene);
    }

    public function testAddPotionActionsWithPaidHealing(): void
    {
        $stage = $this->createMock(Stage::class);
        $scene = $this->createMock(Scene::class);
        $character = $this->createMock(Character::class);

        $this->logger->expects($this->never())->method('debug');
        $this->security->expects($this->never())->method('isGranted');
        $this->gold->expects($this->never())->method('getGold');

        $scene->expects($this->atLeastOnce())
            ->method(PropertyHook::get("templateConfig"))
            ->willReturn($this->getDefaultTemplateConfig());

        $character->expects($this->atLeastOnce())
            ->method(PropertyHook::get("level"))
            ->willReturn(5);

        $stage->expects($this->atLeastOnce())
            ->method(PropertyHook::get("owner"))
            ->willReturn($character);

        $this->health->expects($this->atLeastOnce())
            ->method('getMaxHealth')
            ->willReturn(100);

        $this->health->expects($this->exactly(2))
            ->method('getHealth')
            ->willReturn(20);

        // For level 5, price should be > 0
        $stage->expects($this->once())
            ->method('addActionGroup')
            ->with($this->callback(function (ActionGroup $actionGroup) {
                $actions = $actionGroup->getActions();
                // Should have complete healing + partial healing options
                return count($actions) > 1 
                    && $actionGroup->getId() === HealerTemplate::ActionGroupPotions
                    && $actionGroup->getTitle() === 'Potions';
            }));

        $this->healerTemplate->addPotionActions($stage, $scene);
    }

    public function testConstants(): void
    {
        $this->assertEquals("lotgd2.actionGroup.healerTemplate.potions", HealerTemplate::ActionGroupPotions);
        $this->assertEquals("lotgd2.action.healerTemplate.complete", HealerTemplate::ActionCompleteHealing);
        $this->assertEquals("lotgd2.action.healerTemplate.partial", HealerTemplate::ActionPartialHealing);
    }
}