<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Random\DiceBagInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;

#[CoversClass(Action::class)]
#[UsesClass(DiceBag::class)]
class ActionTest extends TestCase
{
    public function testConstructorWithoutParameters(): void
    {
        $action = new Action();

        $this->assertNotNull($action->id);
        $this->assertSame(8, strlen($action->id));
        $this->assertSame([], $action->getParameters());
        $this->assertNull($action->sceneId);
    }

    public function testConstructorWithTitle(): void
    {
        $action = new Action(title: 'Attack');

        $this->assertNotNull($action->id);
        $this->assertSame('Attack', $action->title);
        $this->assertNull($action->sceneId);
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
        $scene->method(PropertyHook::get("id"))->willReturn(5);

        $action = new Action(scene: $scene);

        $this->assertSame(5, $action->sceneId);
    }

    public function testConstructorWithCustomDiceBag(): void
    {
        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->method('getRandomString')->with(8)->willReturn('testid12');

        $action = new Action(diceBag: $diceBag);

        $this->assertSame('testid12', $action->id);
    }

    public function testConstructorWithAllParameters(): void
    {
        $scene = $this->createMock(Scene::class);
        $scene->method(PropertyHook::get("id"))->willReturn(42);
        
        $parameters = ['level' => 3, 'name' => 'Fireball'];
        $diceBag = $this->createMock(DiceBag::class);
        $diceBag->method('getRandomString')->with(8)->willReturn('customid1');

        $action = new Action(
            scene: $scene,
            title: 'Magic Spell',
            parameters: $parameters,
            diceBag: $diceBag
        );

        $this->assertSame('customid1', $action->id);
        $this->assertSame('Magic Spell', $action->title);
        $this->assertSame(42, $action->sceneId);
        $this->assertSame($parameters, $action->getParameters());
    }

    public function testSetIdAndGetId(): void
    {
        $action = new Action();
        $action->id = 'newid123';

        $this->assertSame('newid123', $action->id);
    }

    public function testSetTitleAndGetTitle(): void
    {
        $action = new Action();
        $action->title = 'Defend';

        $this->assertSame('Defend', $action->title);
    }

    public function testSetTitleNull(): void
    {
        $action = new Action(title: 'Original');
        $action->title = null;

        $this->assertNull($action->title);
    }

    public function testSetSceneIdAndGetSceneId(): void
    {
        $action = new Action();
        $action->sceneId = 99;

        $this->assertSame(99, $action->sceneId);
    }

    public function testSetSceneIdWithScene(): void
    {
        $scene = $this->createStub(Scene::class);
        $scene->method(PropertyHook::get("id"))->willReturn(99);

        $action = new Action();
        $action->sceneId = $scene;

        $this->assertSame(99, $action->sceneId);
    }

    public function testSetSceneIdNull(): void
    {
        $action = new Action();
        $action->sceneId = 5;
        $action->sceneId = null;

        $this->assertNull($action->sceneId);
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

        $this->assertNotEquals($action1->id, $action2->id);
    }

    public function testIdGenerationUsesCorrectLength(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $action = new Action();
            $this->assertSame(8, strlen($action->id));
        }
    }
}