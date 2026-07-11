<?php
declare(strict_types=1);

namespace LotGD2\Service;

use LotGD2\Attribute\TemplateType;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Form\AbstractType;
use ValueError;

/**
 * Using reflections, this service locates the form type from a given class that has a TemplateType attribute.
 *
 * Only returns form types extending (eventually) from AbstractType.
 */
readonly class TemplateTypeFinder
{
    public function __construct(
        private LoggerInterface $logger,
    ) {

    }

    /**
     * @param class-string $templateClass
     * @return null|class-string<AbstractType<mixed>>
     * @throws ValueError if class does not exist.
     */
    public function find(string $templateClass): ?string
    {
        if ($templateClass === "") {
            return null;
        }

        if (!class_exists($templateClass)) {
            throw new ValueError("Template class '$templateClass' does not exist");
        }

        $reflection = new ReflectionClass($templateClass);
        $attributes = $reflection->getAttributes(TemplateType::class);

        if (count($attributes) === 0) {
            return null;
        }

        try {
            /** @var TemplateType $attribute */
            $attribute = $attributes[0]->newInstance();
        } catch (\InvalidArgumentException $e) {
            $this->logger->error("TemplateTypeFinder: Error while trying to find template type. ".$e->getMessage());
            return null;
        }

        return $attribute->type;
    }
}