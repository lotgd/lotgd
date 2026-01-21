<?php
declare(strict_types=1);

namespace LotGD2\Twig;

use Twig\Extension\RuntimeExtensionInterface;

class ParseRuntime implements RuntimeExtensionInterface
{
    public function parse(
        $text,
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

            if ($emptyParagraph) {
                $parsedText .= "</p>\n<p>";
                $emptyParagraph = false;
            }

            $parsedText .= $this->parsePart($part);
        }

        $parsedText = "<p>$parsedText</p>";

        return "<div class='lotgd-parsed'>$parsedText</div>";
    }

    public function parsePart(
        string $text
    ): string {
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