<?php
declare(strict_types=1);

namespace LotGD2\Tests\Entity\Mapped;

use LotGD2\Entity\Mapped\Attachment;
use LotGD2\Entity\Mapped\SceneConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ValueError;

#[CoversClass(Attachment::class)]
class AttachmentTest extends TestCase
{

    public function testEmptyConstructor()
    {
        $sceneConnection = new Attachment();

        $this->assertInstanceOf(Attachment::class, $sceneConnection);
    }

    public function testConstructorParameterNames()
    {
        $sceneConnection = new Attachment(
            name: "Attachment",
            attachmentClass: null,
        );

        $this->assertSame("Attachment", $sceneConnection->name);
        $this->assertNull($sceneConnection->attachmentClass);
    }

    public function testAttachmentConstructorThrowsValueErrorIfAttachmentClass()
    {
        $this->expectException(ValueError::class);

        $sceneConnection = new Attachment(
            attachmentClass: "not-a-class"
        );
    }
}
