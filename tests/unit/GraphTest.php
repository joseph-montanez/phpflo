<?php

use Codeception\Util\Stub;

class GraphTest extends \Codeception\TestCase\Test
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
    public function testMe()
    {
        \PhpFlo\Graph::loadFile(__DIR__.'/../../examples/linecount/count.json', function ($graph) {
            $readFile = $graph->getNode('ReadFile');
            $this->assertEquals('ReadFile', $readFile['id']);

            $this->assertEquals(4, count($graph->nodes));
        });
    }

}