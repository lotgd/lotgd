<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene;

use LotGD2\Entity\Mapped\Scene;
use LotGD2\Game\Scene\SceneTemplate\SceneTemplateInterface;
use LotGD2\Game\Scene\SceneTemplate\TaggedSceneInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

readonly class SceneService
{
    private ContainerInterface $container;

    public function __construct(
        KernelInterface $kernel,
        private LoggerInterface $logger,
    ) {
        $this->container = $kernel->getContainer();
    }

    /**
     * @param Scene $scene
     * @return SceneTemplateInterface<array<string, mixed>>|null
     */
    public function getTemplate(Scene $scene): ?SceneTemplateInterface
    {
        /** @var null|SceneTemplateInterface<array<string, mixed>> $templateClass */
        $templateClass = $this->container->get($scene->templateClass, ContainerInterface::NULL_ON_INVALID_REFERENCE);

        if ($scene->templateClass !== null and $templateClass === null) {
            $this->logger->warning(
                "Template class '{$scene->templateClass}' was not found as a service. Make sure you tagged the service as public.",
                [
                    "scene" => $scene,
                ]
            );
        }

        return $templateClass;
    }

    /**
     * @param Scene $scene
     * @return void
     */
    public function addTags(Scene $scene): void
    {
        // Add the tags given by the tagged scene interface
        if (is_subclass_of($scene->templateClass, TaggedSceneInterface::class, true)) {
            /** @var SceneTemplateInterface&TaggedSceneInterface $templateClass */
            $templateClass = $this->getTemplate($scene);

            $scene->tags = [$templateClass->getTag()];
        } else {
            // Remove all tags if no tagged scene interface was given.
            $scene->tags = [];
        }
    }
}