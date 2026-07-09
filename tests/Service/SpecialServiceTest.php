<?php
declare(strict_types=1);

namespace LotGD2\Tests\Service;

use LotGD2\Entity\Mapped\Scene;
use LotGD2\Game\Error\SpecialNotFoundError;
use LotGD2\Game\Random\DiceBag;
use LotGD2\Game\Scene\SceneRenderer;
use LotGD2\Game\Scene\SpecialService;
use LotGD2\Repository\SceneRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(SpecialService::class)]
class SpecialServiceTest extends TestCase
{
    public function testGetRandomSpecialThrowsSpecialNotFoundErrorIfNoSpecialWasFound(): void
    {
        $sceneRepository = $this->createMock(SceneRepository::class);
        $diceBag = $this->createMock(DiceBag::class);
        $renderer = $this->createStub(SceneRenderer::class);
        $logger = $this->createStub(LoggerInterface::class);

        // Set up expectations
        $sceneRepository->expects($this->once())
            ->method('findByTag')
            ->willReturn([]);

        $diceBag->expects($this->never())
            ->method("pick");

        // What we test
        $service = new SpecialService(
            $sceneRepository,
            $diceBag,
            $renderer,
            $logger,
        );

        $this->expectException(SpecialNotFoundError::class);

        $service->getRandomSpecial();
    }

    public function testGetRandomSpecial(): void
    {
        $sceneRepository = $this->createMock(SceneRepository::class);
        $diceBag = $this->createMock(DiceBag::class);
        $scene = $this->createStub(Scene::class);
        $renderer = $this->createStub(SceneRenderer::class);
        $logger = $this->createStub(LoggerInterface::class);

        // Set up expectations
        $sceneRepository->expects($this->once())
            ->method('findByTag')
            ->willReturn([$scene]);

        $diceBag->expects($this->once())
            ->method("pick")
            ->with([$scene])
            ->willReturn([$scene]);

        // What we test
        $service = new SpecialService(
            $sceneRepository,
            $diceBag,
            $renderer,
            $logger,
        );

        $returnedScene = $service->getRandomSpecial();

        $this->assertSame($scene, $returnedScene);
    }
}
