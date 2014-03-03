<?php
namespace PhpFlo;

use DateTime;

class Network
{
    private $processes = array();
    private $connections = array();
    private $initials = [];
    private $graph = null;
    private $startupDate = null;
    private $portBuffer = [];
    private $baseDir;
    private $loader;
    private $connectionCount = 0;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;

        $this->baseDir = $graph->baseDir ? : getcwd();

        $this->startupDate = $this->createDateTimeWithMilliseconds();

        # Initialize a Component Loader for the network
        if ($graph->componentLoader) {
            $this->loader = $graph->componentLoader;
        } else {
            $this->loader = new ComponentLoader($this->baseDir);
        }

        $this->graph->on('addNode', array($this, 'addNode'));
        $this->graph->on('removeNode', array($this, 'removeNode'));
        $this->graph->on('addEdge', array($this, 'addEdge'));
        $this->graph->on('removeEdge', array($this, 'removeEdge'));
    }

    public function uptime()
    {
        return $this->startupDate->diff($this->createDateTimeWithMilliseconds());
    }


    # Emit a 'start' event on the first connection, and 'end' event when
    # last connection has been closed
    public function increaseConnections()
    {
        // if @connectionCount is 0
        if ($this->connectionCount === 0) {
            # First connection opened, execution has now started
            $this->emit('start', [['start' => $this->startupDate]]);
        }
        $this->connectionCount++;
    }

    public function decreaseConnections()
    {
        $this->connectionCount--;
        # Last connection closed, execution has now ended
        if ($this->connectionCount === 0) {
            //-- TODO: This is not possible without reactphp?
            /*
            $ender = _.debounce =>
            return if @connectionCount
            @emit 'end',
            start: @startupDate
            end: new Date
            uptime: @uptime()
            , 10
            do ender
            */
            $this->emit('end', [[
                'start' => $this->startupDatem,
                'end' => $this->createDateTimeWithMilliseconds(),
                'uptime' => $this->uptime()
            ]]);
        }
    }

    # ## Loading components
    #
    # Components can be passed to the NoFlo network in two ways:
    #
    # * As direct, instantiated JavaScript objects
    # * As filenames
    public function load($component, $metadata, $callback)
    {
        $this->loader->load($component, $callback, false, $metadata);
    }

    public function addNode(array $node, $callback = null)
    {
        if (isset($this->processes[$node['id']])) {
            if ($callback !== null && is_callable($callback)) {
                $callback($this->processes[$node['id']]);
            }
            return;
        }

        $process = array();
        $process['id'] = $node['id'];

        if (!isset($node['component'])) {
            $this->processes[$process['id']] = $process;
            if ($callback !== null && is_callable($callback)) {
                $callback($process);
            }
            return;
        }

        # Load the component for the process.
        $this->load($node['component'], $node['metadata'], function ($instance) use ($node, $callback) {
//          instance.nodeId = node.id
            $instance['nodeId'] = $node['id'];
//          process.component = instance
            $process['component'] = $instance;
//
//          # Inform the ports of the node name
//          for name, port of process.component.inPorts
            foreach ($process['component']['inPorts'] as $name => $port) {
//            continue if not port or typeof port is 'function' or not port.canAttach
                if (!$port || is_callable($port) || !$port->canAttach) {
                    continue;
                }
//            port.node = node.id
                $port->node = $node['id'];
//            port.nodeInstance = instance
                $port->nodeInstance = $instance;
//            port.name = name
                $port->name = $name;
            }
//          for name, port of process.component.outPorts
            foreach ($process['component']['outPorts'] as $name => $port) {
//          continue if not port or typeof port is 'function' or not port.canAttach
                if (!$port || is_callable($port) || !$port->canAttach) {
                    continue;
                }
//            port.node = node.id
                $port->node = $node['id'];
//            port.nodeInstance = instance
                $port->nodeInstance = $instance;
//            port.name = name
                $port->name = $name;
//
            }
//          @subscribeSubgraph process if instance.isSubgraph()
            if ($instance->isSubgraph()) {
                $this->subscribeSubgraph($process);
            }
//          @subscribeNode process
            $this->subscribeNode($process);
//
//          # Store and return the process instance
//          @processes[process.id] = process
            $this->process[$process['id']] = $process;
//          callback process if callback
            if ($callback !== null && is_callable($callback)) {
                $callback($process);
            }
        });
    }

    public function removeNode(array $node, $callback = null)
    {
//        return unless @processes[node.id]
        if (!isset($this->processes[$node['id']])) {
            return;
        }
//        @processes[node.id].component.shutdown()
        $this->processes[$node['id']]['component']->shutdown();
//        delete @processes[node.id]
        unset($this->processes[$node['id']]);
//        do callback if callback
        if ($callback !== null && is_callable($callback)) {
            callback();
        }
    }

//renameNode: (oldId, newId, callback) ->
    public function renameNode($oldId, $newId, $callback = null) {
//    process = @getNode oldId
        $process = $this->getNode($oldId);
//    return unless process
        if (!$process) {
            return;
        }
//
//        # Inform the process of its ID
//    process.id = newId
        $process['id'] = $newId;
//
//        # Inform the ports of the node name
//    for name, port of process.component.inPorts
        foreach ($process['component']['inPorts'] as $name => &$port) {
//    port.node = newId
            $port['node'] = $newId;
        }
        unset($port);
//    for name, port of process.component.outPorts
        foreach ($process['component']['outPorts'] as $name => &$port) {
//    port.node = newId
            $port['node'] = $newId;
        }
        unset($port);
//
//    @processes[newId] = process
        $this->processes[$newId] = $process;
//    delete @processes[oldId]
        unset($this->processes[$oldId]);
//    do callback if callback
        if ($callback !== null && is_callable($callback)) {
            callback();
        }
    }

    public function getNode($id)
    {
        if (!isset($this->processes[$id])) {
            return null;
        }

        return $this->processes[$id];
    }

    public function getGraph()
    {
        return $this->graph;
    }

    private function connectInboundPort(SocketInterface $socket, array $process, $port)
    {
        $socket->to = array(
            'process' => $process,
            'port' => $port,
        );

        if (!isset($process['component']->inPorts[$port])) {
            throw new \InvalidArgumentException("No inport {$port} defined for process {$process['id']}");
        }

        return $process['component']->inPorts[$port]->attach($socket);
    }

    private function connectOutgoingPort(SocketInterface $socket, array $process, $port)
    {
        $socket->from = array(
            'process' => $process,
            'port' => $port,
        );

        if (!isset($process['component']->outPorts[$port])) {
            throw new \InvalidArgumentException("No outport {$port} defined for process {$process['id']}");
        }

        return $process['component']->outPorts[$port]->attach($socket);
    }

    public function addEdge(array $edge)
    {
        if (!isset($edge['from']['node'])) {
            return $this->addInitial($edge);
        }
        $socket = new InternalSocket();

        $from = $this->getNode($edge['from']['node']);
        if (!$from) {
            throw new \InvalidArgumentException("No process defined for outbound node {$edge['from']['node']}");
        }

        $to = $this->getNode($edge['to']['node']);
        if (!$to) {
            throw new \InvalidArgumentException("No process defined for inbound node {$edge['to']['node']}");
        }

        $this->connectOutgoingPort($socket, $from, $edge['from']['port']);
        $this->connectInboundPort($socket, $to, $edge['to']['port']);

        $this->connections[] = $socket;
    }

    public function removeEdge(array $edge)
    {
        foreach ($this->connections as $index => $connection) {
            if ($edge['to']['node'] == $connection->to['process']['id'] && $edge['to']['port'] == $connection->to['process']['port']) {
                $connection->to['process']['component']->inPorts[$edge['to']['port']]->detach($connection);
                $this->connections = array_splice($this->connections, $index, 1);
            }

            if (isset($edge['from']['node'])) {
                if ($edge['from']['node'] == $connection->from['process']['id'] && $edge['from']['port'] == $connection->from['process']['port']) {
                    $connection->from['process']['component']->inPorts[$edge['from']['port']]->detach($connection);
                    $this->connections = array_splice($this->connections, $index, 1);
                }
            }
        }
    }

    public function addInitial(array $initializer)
    {
        $socket = new InternalSocket();
        $to = $this->getNode($initializer['to']['node']);
        if (!$to) {
            throw new \InvalidArgumentException("No process defined for inbound node {$initializer['to']['node']}");
        }

        $this->connectInboundPort($socket, $to, $initializer['to']['port']);
        $socket->connect();
        $socket->send($initializer['from']['data']);
        $socket->disconnect();

        $this->connections[] = $socket;
    }

    public static function create(Graph $graph)
    {
        $network = new Network($graph);

        foreach ($graph->nodes as $node) {
            $network->addNode($node);
        }

        foreach ($graph->edges as $edge) {
            $network->addEdge($edge);
        }

        foreach ($graph->initializers as $initializer) {
            $network->addInitial($initializer);
        }

        return $network;
    }

    public static function loadFile($file)
    {
        $graph = Graph::loadFile($file);

        return Network::create($graph);
    }

    private function createDateTimeWithMilliseconds()
    {
        return DateTime::createFromFormat('U.u', sprintf('%.6f', microtime(true)));
    }
}
