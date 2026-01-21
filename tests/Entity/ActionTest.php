<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Action::class)]
#[UsesClass(DiceBag::class)]
class ActionTest extends TestCase
{
    public function testConstructorWithoutParameters(): void
    {
        $action = new Action();

        $this->assertNotNull($action->getId());
        $this->assertSame(8, strlen($action->getId()));
        $this->assertNull($action->getTitle());
        $this->assertSame([], $action->getParameters());
        $this->assertNull($action->getSceneId());
    }

    public function testConstructorWithTitle(): void
    {
        $action = new Action(title: 'Attack');

        $this->assertNotNull($action->getId());
        $this->assertSame('Attack', $action->getTitle());
        $this->assertNull($action->getSceneId());
    }

    public function testConstructorWithParameters(): void
    {
        $parameters = ['damage' => 10, 'type' => 'physical'];
        $action = new Action(parameters: $parameters);

        $this->assertSame($parameters, $action->getParameters());
    }

    public function testConstructorWithScene(): void
    {
        $scene = $this->createMock(Scene::class);
        $scene->method('getId')->willReturn(5);

        $action = new Action(scene: $scene);

        $this->assertSame(5, $action->getSceneId());
    }

    public function testConstructorWithCustomDiceBag(): void
    {
        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->method('getRandomString')->with(8)->willReturn('testid12');

        $action = new Action(diceBag: $diceBag);

        $this->assertSame('testid12', $action->getId());
    }

    public function testConstructorWithAllParameters(): void
    {
        $scene = $this->createMock(Scene::class);
        $scene->method('getId')->willReturn(42);
        
        $parameters = ['level' => 3, 'name' => 'Fireball'];
        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->method('getRandomString')->with(8)->willReturn('customid1');

        $action = new Action(
            scene: $scene,
            title: 'Magic Spell',
            parameters: $parameters,
            diceBag: $diceBag
        );

        $this->assertSame('customid1', $action->getId());
        $this->assertSame('Magic Spell', $action->getTitle());
        $this->assertSame(42, $action->getSceneId());
        $this->assertSame($parameters, $action->getParameters());
    }

    public function testSetIdAndGetId(): void
    {
        $action = new Action();
        $action->setId('newid123');

        $this->assertSame('newid123', $action->getId());
    }

    public function testSetIdReturnsInstance(): void
    {
        $action = new Action();
        $result = $action->setId('testid');

        $this->assertSame($action, $result);
    }

    public function testSetNullId(): void
    {
        $action = new Action();
        $action->setId(null);

        $this->assertNull($action->id);
    }

    public function testSetTitleAndGetTitle(): void
    {
        $action = new Action();
        $action->setTitle('Defend');

        $this->assertSame('Defend', $action->getTitle());
    }

    public function testSetTitleReturnsInstance(): void
    {
        $action = new Action();
        $result = $action->setTitle('Test Action');

        $this->assertSame($action, $result);
    }

    public function testSetTitleNull(): void
    {
        $action = new Action(title: 'Original');
        $action->setTitle(null);

        $this->assertNull($action->title);
    }

    public function testSetSceneIdAndGetSceneId(): void
    {
        $action = new Action();
        $action->setSceneId(99);

        $this->assertSame(99, $action->getSceneId());
    }

    public function testSetSceneIdReturnsInstance(): void
    {
        $action = new Action();
        $result = $action->setSceneId(10);

        $this->assertSame($action, $result);
    }

    public function testSetSceneIdNull(): void
    {
        $action = new Action();
        $action->setSceneId(5);
        $action->setSceneId(null);

        $this->assertNull($action->getSceneId());
    }

    public function testSetParametersAndGetParameters(): void
    {
        $action = new Action();
        $parameters = ['power' => 50, 'effect' => 'stun'];
        $action->setParameters($parameters);

        $this->assertSame($parameters, $action->getParameters());
    }

    public function testSetParametersReturnsInstance(): void
    {
        $action = new Action();
        $result = $action->setParameters(['key' => 'value']);

        $this->assertSame($action, $result);
    }

    public function testSetEmptyParameters(): void
    {
        $action = new Action(parameters: ['initial' => 'value']);
        $action->setParameters([]);

        $this->assertSame([], $action->getParameters());
    }

    public function testGetParameterWithExistingKey(): void
    {
        $action = new Action(parameters: ['damage' => 25, 'range' => 5]);

        $this->assertSame(25, $action->getParameter('damage'));
        $this->assertSame(5, $action->getParameter('range'));
    }

    public function testGetParameterWithNonExistentKeyAndDefault(): void
    {
        $action = new Action();

        $this->assertNull($action->getParameter('nonexistent'));
        $this->assertSame('default_value', $action->getParameter('nonexistent', 'default_value'));
        $this->assertSame(0, $action->getParameter('missing', 0));
    }

    public function testSetParameterAndRetrieve(): void
    {
        $action = new Action();
        $action->setParameter('speed', 10);

        $this->assertSame(10, $action->getParameter('speed'));
    }

    public function testSetParameterReturnsInstance(): void
    {
        $action = new Action();
        $result = $action->setParameter('test', 'value');

        $this->assertSame($action, $result);
    }

    public function testSetParameterOverwritesExisting(): void
    {
        $action = new Action(parameters: ['count' => 5]);
        $action->setParameter('count', 10);

        $this->assertSame(10, $action->getParameter('count'));
    }

    public function testMultipleSetParameterCalls(): void
    {
        $action = new Action();
        $action->setParameter('param1', 'value1');
        $action->setParameter('param2', 'value2');
        $action->setParameter('param3', 'value3');

        $this->assertSame('value1', $action->getParameter('param1'));
        $this->assertSame('value2', $action->getParameter('param2'));
        $this->assertSame('value3', $action->getParameter('param3'));
    }

    public function testFluentInterfaceChaining(): void
    {
        $action = new Action();
        $result = $action
            ->setId('fluent_id')
            ->setTitle('Chained Action')
            ->setSceneId(7)
            ->setParameter('key1', 'val1')
            ->setParameter('key2', 'val2');

        $this->assertSame($action, $result);
        $this->assertSame('fluent_id', $action->getId());
        $this->assertSame('Chained Action', $action->getTitle());
        $this->assertSame(7, $action->getSceneId());
        $this->assertSame('val1', $action->getParameter('key1'));
        $this->assertSame('val2', $action->getParameter('key2'));
    }

    public function testParameterWithVariousScalarTypes(): void
    {
        $action = new Action();
        $action->setParameter('int_param', 42);
        $action->setParameter('string_param', 'text');
        $action->setParameter('float_param', 3.14);
        $action->setParameter('bool_param', true);

        $this->assertSame(42, $action->getParameter('int_param'));
        $this->assertSame('text', $action->getParameter('string_param'));
        $this->assertSame(3.14, $action->getParameter('float_param'));
        $this->assertTrue($action->getParameter('bool_param'));
    }

    public function testPublicPropertyAccess(): void
    {
        $action = new Action();
        
        $action->id = 'direct_id';
        $this->assertSame('direct_id', $action->id);
        
        $action->title = 'Direct Title';
        $this->assertSame('Direct Title', $action->title);
        
        $action->sceneId = 15;
        $this->assertSame(15, $action->sceneId);
        
        $action->parameters = ['direct' => 'param'];
        $this->assertSame(['direct' => 'param'], $action->parameters);
    }

    public function testGeneratedIdIsUnique(): void
    {
        $action1 = new Action();
        $action2 = new Action();

        $this->assertNotEquals($action1->getId(), $action2->getId());
    }

    public function testIdGenerationUsesCorrectLength(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $action = new Action();
            $this->assertSame(8, strlen($action->getId()));
        }
    }
}