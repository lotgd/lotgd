<?php
declare(strict_types=1);

namespace LotGD2\Twig;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;

class ParseRuntime implements RuntimeExtensionInterface
{
    /**
     * @param string $text
     * @param array<string, mixed> $context
     * @return string
     */
    public function parse(
        string $text,
        array $context = [],
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
                $parsedText .= "</p>\n<p>";
                $emptyParagraph = false;
            }

            $parsedText .= $this->parsePart($part, $context);
        }

        $parsedText = "<p>$parsedText</p>";

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

        $text = preg_replace_callback("#\{(.*?)\}#", function ($matches) use ($context) {
            [$pattern, $expression] = $matches;
            $expressionLanguage = new ExpressionLanguage();

            try {
                $expressionLanguage->lint($pattern, $context);
                return $expressionLanguage->evaluate($expression, $context);
            } catch (SyntaxError) {
                return $pattern;
            }
        }, $text);

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