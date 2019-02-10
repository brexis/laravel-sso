<?php

namespace Brexis\LaravelSSO\Test;

use Brexis\LaravelSSO\Encription;

class EncriptionTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->encription = new Encription;
    }

    public function testShouldFailVerifyAttachChecksum()
    {
        $checksum = $this->encription->generateChecksum('attach', 'b', 'c');

        $this->assertFalse($this->encription->verifyAttachChecksum('b', 'd', $checksum));
    }

    public function testShouldVerifyAttachChecksum()
    {
        $checksum = $this->encription->generateChecksum('attach', 'b', 'c');

        $this->assertTrue($this->encription->verifyAttachChecksum('b', 'c', $checksum));
    }
    
    public function testShouldGenerateRandonToken()
    {
        $this->assertNotEquals($this->encription->randomToken(), $this->encription->randomToken());
    }
}
