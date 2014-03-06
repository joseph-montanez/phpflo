<?php
namespace Component;

use Codeception\Util\Stub;

class QueuTest extends \Codeception\TestCase\Test
{
   /**
    * @var \CodeGuy
    */
    protected $codeGuy;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testNoMessageIsForwardedWhileQueuSizeIsNotReached()
    {
        $this->markTestIncomplete();
    }

    public function testAllMessagesAreForwardedWhenQueuSizeIsReached()
    {
        $this->markTestIncomplete();
    }

    public function testQueuResize()
    {
        $this->markTestIncomplete();
    }

    public function testErrorIsSendWhenIncorrectResizeMessageIsReceived()
    {
        $this->markTestIncomplete();
    }

    public function testMessagesAreForwardeWhenQueuIsResizedBelowCurrentMessageCount()
    {
        $this->markTestIncomplete();
    }

    public function testMessagesAreForwaredWhenIncomingStreamIsDetached()
    {
        $this->markTestIncomplete();
    }

}