<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity;

use LotGD2\Entity\Paragraph;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Paragraph::class)]
class ParagraphTest extends TestCase
{
    public function testConstructorWithRequiredArguments(): void
    {
        $paragraph = new Paragraph(
            id: 'para_1',
            text: 'A wandering traveler appears.',
        );

        $this->assertSame('para_1', $paragraph->id);
        $this->assertSame('A wandering traveler appears.', $paragraph->text);
        $this->assertSame([], $paragraph->context);
        $this->assertFalse($paragraph->translated);
        $this->assertSame([], $paragraph->kwargs);
    }

    public function testConstructorWithAllArguments(): void
    {
        $context = ['character' => 'Hero', 'level' => 5];
        $kwargs = ['style' => 'bold', 'alignment' => 'center'];

        $paragraph = new Paragraph(
            id: 'para_2',
            text: 'Greetings, {{ character }}!',
            context: $context,
            translated: true,
            kwargs: $kwargs,
        );

        $this->assertSame('para_2', $paragraph->id);
        $this->assertSame('Greetings, {{ character }}!', $paragraph->text);
        $this->assertSame($context, $paragraph->context);
        $this->assertTrue($paragraph->translated);
        $this->assertSame($kwargs, $paragraph->kwargs);
    }

    public function testSetContext(): void
    {
        $paragraph = new Paragraph(
            id: 'para_3',
            text: 'Sample text',
        );

        $this->assertSame([], $paragraph->context);

        $newContext = ['key1' => 'value1', 'key2' => 42];
        $paragraph->context = $newContext;

        $this->assertSame($newContext, $paragraph->context);
    }

    public function testAddContext(): void
    {
        $paragraph = new Paragraph(
            id: 'para_4',
            text: 'Sample text',
            context: ['initial' => 'data'],
        );

        $result = $paragraph->addContext('character', 'Hero');

        // Paragraph->addContext must be fluid
        $this->assertSame($paragraph, $result);

        // Context should always be added, and same key should be overwritten
        $this->assertSame([
            'initial' => 'data',
            'character' => 'Hero',
        ], $paragraph->context);

        $paragraph->addContext('character', 'Champion');
        $this->assertSame([
            'initial' => 'data',
            'character' => 'Champion',
        ], $paragraph->context);

        $paragraph->addContext('count', 10);
        $this->assertSame([
            'initial' => 'data',
            'character' => 'Champion',
            'count' => 10,
        ], $paragraph->context);
    }
}
