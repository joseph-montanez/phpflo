<?php

use Codeception\Util\Stub;

class NetworkTest extends \Codeception\TestCase\Test
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
    public function testLoadFile()
    {
        \PhpFlo\Network::loadFile(__DIR__.'/../../examples/linecount/count.json', function ($network) {
            $readFile = $network->getNode('ReadFile');
            $this->assertEquals('ReadFile', $readFile['id']);
        });
    }

}