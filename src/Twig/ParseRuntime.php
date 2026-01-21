<?php
declare(strict_types=1);

namespace LotGD2\Twig;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Parser;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;

class ParseRuntime implements RuntimeExtensionInterface
{
    private Environment $twig;

    public function __construct(
        private LoggerInterface $logger,
    ) {
        $this->twig = new Environment(new ArrayLoader([]), ['cache' => false]);
        $this->twig->addExtension(new SandboxExtension(new SecurityPolicy(
            allowedTags: ["if"],
            allowedFilters: ["abs", "round"],
            allowedProperties: [
                "Character" => ["name", "level"],
                "Master" => ["name", "weapon"],
                "Creature" => ["name", "weapon"],
            ],
        )));
    }

    /**
     * @param string $text
     * @param array<string, mixed> $context
     * @return string
     */
    public function parse(
        string $text,
        array $context = [],
        bool $useParagraphs = true,
    ): string {
        $text = $this->normalizeLineBreaks($text);

        $textParts = explode("\n", $text);
        $parsedText = "";

        $emptyParagraph = false;
        foreach ($textParts as $part) {
            // Remove spaces and tabs from the beginning and end of lines
            $part = trim($part);

            if (strlen($part) === 0) {
                $emptyParagraph = true;
                continue;
            }

            // Add a space
            $part = $part . " ";

            if ($emptyParagraph) {
                $parsedText .= $useParagraphs ? "</p>\n<p>" : "";
                $emptyParagraph = false;
            }

            $parsedText .= $this->parsePart($part, $context);
        }

        if ($useParagraphs) {
            $parsedText = "<p>$parsedText</p>";
        }

        try {
            $parsedText = $this->twig->createTemplate($parsedText)->render($context);
            $parsedText = $this->twig->createTemplate($parsedText)->render($context);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            $this->logger->warning("Issues parsing scene: {$e->getMessage()}");
        }

        return "<div class='lotgd-parsed'>$parsedText</div>";
    }

    /**
     * @param string $text
     * @param array<string, mixed> $context
     * @return string
     */
    public function parsePart(
        string $text,
        array $context,
    ): string {
        $replacements = [
            "<<" => "«",
            ">>" => "»",
            "<." => "‹",
            ".>" => "›",
        ];

        $text = str_replace(array_keys($replacements), array_values($replacements), $text);

        return $text;
    }

    public function normalizeLineBreaks(string $text): string
    {
        // Normalize line breaks
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);

        return $text;
    }
}