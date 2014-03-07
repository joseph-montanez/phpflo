<?php
namespace Network;

use Codeception\Util\Stub;

class WithEmptyGraphTest extends \Codeception\TestCase\Test
{
    /**
     * @var \CodeGuy
     */
    protected $codeGuy;
    /**
     * @var \PhpFlo\Graph
     */
    protected $g;
    /**
     * @var \PhpFlo\Network
     */
    protected $n;

    protected function _before()
    {
        $this->g = new \PhpFlo\Graph();
        $this->g->baseDir = realpath(__DIR__ . '/../../');
        $this->n = new \PhpFlo\Network($this->g);
        $this->n->connect();
    }

    protected function _after()
    {
    }

    // tests
    public function testShouldInitiallyHaveNoProcesses()
    {
        $this->assertEquals(0, count($this->n->getProcesses()));
    }
    public function testShouldInitiallyHaveNoConnects()
    {
        $this->assertEquals(0, count($this->n->getConnections()));
    }
    public function testShouldInitiallyHaveNoIIPs()
    {
        $this->assertEquals(0, count($this->n->getInitials()));
    }
    public function testShouldHaveReferenceToTheGraph()
    {
        $this->assertEquals($this->g, $this->n->getGraph());
    }

    public function testShouldKnowItsBaseDir()
    {
        $this->assertEquals($this->g->getBaseDir(), $this->n->getBaseDir());
    }

    public function testShouldHaveAComponentLoader()
    {
        $this->assertEquals(true, is_object($this->n->getLoader()));
        $this->assertEquals(true, $this->n->getLoader() instanceof \PhpFlo\ComponentLoader);
    }

    public function testShouldHaveTransmittedTheBaseDirToTheComponentLoader()
    {
        $this->assertEquals($this->g->getBaseDir(), $this->n->getLoader()->getBaseDir());
    }

    public function testShouldHaveAnUptime()
    {
        // Sleep at least 1 second, DateInterval does not support milli/micro seconds
        sleep(1);
        $this->assertGreaterThan(0, $this->dateIntervalToSeconds($this->n->uptime()));
    }

    public function dateIntervalToSeconds(\DateInterval $interval) {
        return $interval->days * 86400 +
            $interval->h * 3600 +
            $interval->i * 60 +
            $interval->s;
    }
}