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

//        $this->graph->on('addNode', array($this, 'addNode'));
//        $this->graph->on('removeNode', array($this, 'removeNode'));
//        $this->graph->on('addEdge', array($this, 'addEdge'));
//        $this->graph->on('removeEdge', array($this, 'removeEdge'));
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
        $this->load($node['component'], $node['metadata'], function ($instance) use ($process, $node, $callback) {
//          instance.nodeId = node.id
            $instance->nodeId = $node['id'];
//          process.component = instance
            $process['component'] = $instance;
//
//          # Inform the ports of the node name
//          for name, port of process.component.inPorts
            foreach ($process['component']->inPorts as $name => &$port) {
//            continue if not port or typeof port is 'function' or not port.canAttach
                if (!$port || is_callable($port) || !$port->canAttach) {
                    continue;
                }
//            port.node = node.id
                $port->setNode($node['id']);
//            port.nodeInstance = instance
                $port->nodeInstance = $instance;
//            port.name = name
                $port->setName($name);
            }
            unset($port);
//          for name, port of process.component.outPorts
            foreach ($process['component']->outPorts as $name => &$port) {
//          continue if not port or typeof port is 'function' or not port.canAttach
                if (!$port || is_callable($port) || !$port->canAttach) {
                    continue;
                }
//            port.node = node.id
                $port->setNode($node['id']);
//            port.nodeInstance = instance
                $port->nodeInstance = $instance;
//            port.name = name
                $port->setName($name);
            }
            unset($port);
//          @subscribeSubgraph process if instance.isSubgraph()
            if ($instance->isSubgraph()) {
                $this->subscribeSubgraph($process);
            }
//          @subscribeNode process
            $this->subscribeNode($process);
//
//          # Store and return the process instance
//          @processes[process.id] = process
            $this->processes[$process['id']] = $process;
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
        // TODO why are there no processes here?
        if (!isset($this->processes[$id])) {
            return null;
        }

        return $this->processes[$id];
    }
	
	public function connect($done = null) {
		if ($done === null) {
			$done = function () {};
		}
		
		// # Wrap the future which will be called when done in a function and return
		// # it
		// serialize = (next, add) =>
		$serialize = function ($next, $add) {
			// (type) =>
			return function ($type) use ($next, $add) {
				// # Add either a Node, an Initial, or an Edge and move on to the next one
				// # when done
				// this["add#{type}"] add, ->
				//-- jm: brain melted...
				return $this->{'add' . $type}($add, function () use ($next, $type) {
					// next type
					return $next($type);
				});
			};
		};
		//# Subscribe to graph changes when everything else is done
		//subscribeGraph = =>
        $subscribeGraph = function () use ($done) {
		//  @subscribeGraph()
            $this->subscribeGraph();
		//  done()
            $done();
		//
        };
		//# Serialize initializers then call callback when done
		//initializers = _.reduceRight @graph.initializers, serialize, subscribeGraph
        $array = new \__;
        $initializers = $array->reduceRight($this->graph->initializers, $serialize, $subscribeGraph);
		//# Serialize edge creators then call the initializers
		//edges = _.reduceRight @graph.edges, serialize, -> initializers "Initial"
        $edges = $array->reduceRight($this->graph->edges, $serialize, function () use ($initializers) {
            $initializers('Initial');
        });
		//# Serialize node creators then call the edge creators
		//nodes = _.reduceRight @graph.nodes, serialize, -> edges "Edge"
        $nodes = $array->reduceRight($this->graph->nodes, $serialize, function () use ($edges) {
            $edges('Edge');
        });
		//# Start with node creators
        //nodes "Node"
        if (is_callable($nodes)) {
            $nodes('Node');
        }
	}
	
	public function connectPort(InternalSocket $socket, $process, $port, $inbound = null) {
		// if inbound
		if ($inbound === true) {
			// socket.to =
			$socket->to = [
				// process: process
				'process' => $process,
				// port: port
				'port' => $port
			];
			
			// unless process.component.inPorts and process.component.inPorts[port]
			if (!(isset($process['component']->inPorts) && isset($process['component']->inPorts[$port]))) {
				// throw new Error "No inport '#{port}' defined in process #{process.id} (#{socket.getId()})"
				throw new \RuntimeException(sprintf('No inport \'%s\' defined in process %s (%s)', $port, $process['id'], $socket->getId()));
				// return
				return;
			}
			//  return process.component.inPorts[port].attach socket
			return $process['component']->inPorts[$port]->attach($socket);
		}
		
		// socket.from =
		$socket->from = [
			// process: process
			'process' => $process,
			// port: port
			'port' => $port
		];
			
		// unless process.component.inPorts and process.component.inPorts[port]
		if (!(isset($process['component']->outPorts) && isset($process['component']->outPorts[$port]))) {
			// throw new Error "No inport '#{port}' defined in process #{process.id} (#{socket.getId()})"
			throw new \RuntimeException(sprintf('No outport \'%s\' defined in process %s (%s)', $port, $process['id'], $socket->getId()));
			// return
			return;
		}
		
		// process.component.outPorts[port].attach socket
		return $process['component']->outPorts[$port]->attach($socket);
	}

    // subscribeGraph: ->
    public function subscribeGraph() {
    //   # A NoFlo graph may change after network initialization.
    //   # For this, the network subscribes to the change events from
    //   # the graph.
    //   #
    //   # In graph we talk about nodes and edges. Nodes correspond
    //   # to NoFlo processes, and edges to connections between them.
    //   graphOps = []
        $graphOps = [];
    //   processing = false
        $processing = false;
    //   registerOp = (op, details) ->
        $registerOp = function ($op, $details) use ($graphOps) {
    //     graphOps.push
            $graphOps []= [
    //       op: op
                'op' => $op,
    //       details: details
                'details' => $details
            ];
        };
    //   processOps = =>
        $processOps = null;
        $processOps = function () use ($processing, $graphOps, &$processOps) {
    //     unless graphOps.length
            if (!count($graphOps)) {
    //       processing = false
                $processing = false;
    //       return
                return;
            }
    //     processing = true
            $processing = true;
    //     op = graphOps.shift()
            $op = array_shift($graphOps);
    //     cb = processOps
            $cb = $processOps;
    //     switch op.op
            switch ($op['op']) {
    //       when 'renameNode'
                case 'renameNode':
    //         @renameNode op.details.from, op.details.to, cb
                    $this->renameNode($op['details']['from'], $op['details']['to'], $cb);
    //       else
                default:
    //         @[op.op] op.details, cb
                    $this->{$op['op']}($op['details'], $cb);
            }
        };

    //   @graph.on 'addNode', (node) =>
        $this->graph->on('addNode', function ($node) use($registerOp, $processOps, $processing) {
    //     registerOp 'addNode', node
            $registerOp('addNode', $node);
    //     do processOps unless processing
            if (!$processing) {
                $processOps();
            }
        });
    //   @graph.on 'removeNode', (node) =>
        $this->graph->on('removeNode', function ($node) use ($registerOp, $processOps, $processing) {
    //     registerOp 'removeNode', node
            $registerOp('removeNode', $node);
    //     do processOps unless processing
            if (!$processing) {
                $processOps();
            }
        });
    //   @graph.on 'renameNode', (oldId, newId) =>
        $this->graph->on('renameNode', function ($oldId, $newId) use ($registerOp, $processOps, $processing) {
    //     registerOp 'renameNode',
            $registerOp('renameNode', [
    //       from: oldId
                'from' => $oldId,
    //       to: newId
                'to' => $newId
            ]);
    //     do processOps unless processing
            if (!$processing) {
                $processOps();
            }
        });

        //   @graph.on 'addEdge', (edge) =>
        $this->graph->on('addEdge', function ($edge) use ($registerOp, $processOps, $processing) {
            //     registerOp 'addEdge', edge
            $registerOp('addEdge', $edge);
            //     do processOps unless processing
            if (!$processing) {
                $processOps();
            }
        });
        //   @graph.on 'removeEdge', (edge) =>
        $this->graph->on('removeEdge', function ($edge) use ($registerOp, $processOps, $processing) {
            //     registerOp 'removeEdge', edge
            $registerOp('removeEdge', $edge);
            //     do processOps unless processing
            if (!$processing) {
                $processOps();
            }
        });
        //   @graph.on 'addInitial', (iip) =>
        $this->graph->on('addInitial', function ($iip) use ($registerOp, $processOps, $processing) {
            //     registerOp 'addInitial', iip
            $registerOp('addInitial', $iip);
            //     do processOps unless processing
            if (!$processing) {
                $processOps();
            }
        });
        //   @graph.on 'removeInitial', (iip) =>
        $this->graph->on('removeInitial', function ($iip) use ($registerOp, $processOps, $processing) {
            //     registerOp 'removeInitial', iip
            $registerOp('removeInitial', $iip);
            //     do processOps unless processing
            if (!$processing) {
                $processOps();
            }
        });
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


    // # Subscribe to events from all connected sockets and re-emit them
    // subscribeSocket: (socket) ->
    public function subscribeSocket(InternalSocket $socket) {
    //   socket.on 'connect', =>
        $socket->on('connect', function () use($socket) {
    //     do @increaseConnections
            $this->increaseConnections();
    //     @emit 'connect',
            $this->emit('connect', ['id' => $socket->getId(), 'socket' => $socket]);
    //       id: socket.getId()
    //       socket: socket
        });

    //   socket.on 'begingroup', (group) =>
        $socket->on('begingroup', function ($group) use($socket) {
    //     @emit 'begingroup',
            $this->emit('begingroup', ['id' => $socket->getId(), 'socket' => $socket, 'group' => $group]);
    //       id: socket.getId()
    //       socket: socket
    //       group: group
        });
    //   socket.on 'data', (data) =>
        $socket->on('data', function ($data) use($socket) {
    //     @emit 'data',
            $this->emit('data', ['id' => $socket->getId(), 'socket' => $socket, 'data' => $data]);
    //       id: socket.getId()
    //       socket: socket
    //       data: data
        });
    //   socket.on 'endgroup', (group) =>
        $socket->on('endgroup', function ($group) use($socket) {
    //     @emit 'endgroup',
            $this->emit('endgroup', ['id' => $socket->getId(), 'socket' => $socket, 'group' => $group]);
    //       id: socket.getId()
    //       socket: socket
    //       group: group
        });
    //   socket.on 'disconnect', =>
        $socket->on('disconnect', function () use($socket) {
    //     do @decreaseConnections
            $this->decreaseConnections();
    //     @emit 'disconnect',
            $this->emit('disconnect', ['id' => $socket->getId(), 'socket' => $socket]);
    //       id: socket.getId()
    //       socket: socket
        });
    }

    // subscribeNode: (node) ->
    public function subscribeNode($node) {
    //   return unless node.component.getIcon
        if (!$node['component']->getIcon()) {
            return;
        }
        //   node.component.on 'icon', =>
        $node['component']->on('icon', function () use ($node) {
    //     @emit 'icon',
            $this->emit('icon', ['id' => $node['id'], 'icon' => $node['component']->getIcon()]);
    //       id: node.id
    //       icon: node.component.getIcon()
        });
    }


    // addEdge: (edge, callback) ->
    public function addEdge($edge, $callback) {
    //   socket = internalSocket.createSocket()
        $socket = InternalSocket::createSocket();

    //   from = @getNode edge.from.node
        $from = $this->getNode($edge['from']['node']);
    //   unless from
        if (!$from) {
    //     throw new Error "No process defined for outbound node #{edge.from.node}"
            throw new \RuntimeException(sprintf('No process defined for outbound node %s', $edge['from']['node']));
        }
    //   unless from.component
        if (!$from['component']) {
    //     throw new Error "No component defined for outbound node #{edge.from.node}"
            throw new \RuntimeException(sprintf('No component defined for outbound node %s', $edge['from']['node']));
        }
    //   unless from.component.isReady()
        if (!$from['component']->isReady()) {
    //     from.component.once "ready", =>
            $from['component']->once('ready', function () use ($edge, $callback) {
    //       @addEdge edge, callback
                $this->addEdge($edge, $callback);
            });

    //     return
            return;
        }


        //   to = @getNode edge.to.node
        $to = $this->getNode($edge['to']['node']);
        //   unless to
        if (!$to) {
            //     throw new Error "No process defined for inbound node #{edge.in.node}"
            throw new \RuntimeException(sprintf('No process defined for inbound node %s', $edge['to']['node']));
        }
        //   unless to.component
        if (!$to['component']) {
            //     throw new Error "No component defined for inbound node #{edge.in.node}"
            throw new \RuntimeException(sprintf('No component defined for inbound node %s', $edge['to']['node']));
        }
        //   unless to.component.isReady()
        if (!$to['component']->isReady()) {
            //     to.component.once "ready", =>
            $to['component']->once('ready', function () use ($edge, $callback) {
                //       @addEdge edge, callback
                $this->addEdge($edge, $callback);
            });

            //     return
            return;
        }

    //   @connectPort socket, to, edge.to.port, true
        $this->connectPort($socket, $to, $edge['to']['port'], true);
    //   @connectPort socket, from, edge.from.port, false
        $this->connectPort($socket, $from, $edge['from']['port'], false);

    //   # Subscribe to events from the socket
    //   @subscribeSocket socket
        $this->subscribeSocket($socket);

    //   @connections.push socket
        $this->connections []= $socket;
    //   callback() if callback
        if (is_callable($callback)) {
            $callback();
        }
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

//    public function addInitial(array $initializer)
//    {
//        var_dump('addInitial', $initializer);
//        $socket = new InternalSocket();
//        $to = $this->getNode($initializer['to']['node']);
//        if (!$to) {
//            throw new \InvalidArgumentException("No process defined for inbound node {$initializer['to']['node']}");
//        }
//
//        $this->connectInboundPort($socket, $to, $initializer['to']['port']);
//        $socket->connect();
//        $socket->send($initializer['from']['data']);
//        $socket->disconnect();
//
//        $this->connections[] = $socket;
//    }

    // addInitial: (initializer, callback) ->
    public function addInitial($initializer, $callback) {
    //   socket = internalSocket.createSocket()
        $socket = InternalSocket::createSocket();

    //   # Subscribe to events from the socket
    //   @subscribeSocket socket
        $this->subscribeSocket($socket);

    //   to = @getNode initializer.to.node
        $to = $this->getNode($initializer['to']['node']);
    //   unless to
        if (!$to) {
    //     throw new Error "No process defined for inbound node #{initializer.to.node}"
            throw new \InvalidArgumentException("No process defined for inbound node {$initializer['to']['node']}");
        }

    //   unless to.component.isReady() or to.component.inPorts[initializer.to.port]
        if (!($to['component']->isReady() || $to['component']->inPorts[$initializer['to']['port']])) {
    //     to.component.setMaxListeners 0
            $to['component']->setMaxListeners(0);
    //     to.component.once "ready", =>
            $to['component']->once('ready', function () use ($initializer, $callback) {
    //       @addInitial initializer, callback
                $this->addInitial($initializer, $callback);
            });
    //     return
            return;
        }

    //   @connectPort socket, to, initializer.to.port, true
        $this->connectPort($socket, $to, $initializer['to']['port'], true);

    //   @connections.push socket
        $this->connections []= $socket;

    //   @initials.push
        $this->initials []= ['socket' => $socket, 'data' => $initializer['from']['data']];
    //     socket: socket
    //     data: initializer.from.data

    //   callback() if callback
        if (is_callable($callback)) {
            $callback();
        }
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
	
	/**
	 * 
	 * @return ComponentLoader
	 */
	public function getLoader() {
		return $this->loader;
	}


    // sendInitial: (initial) ->
    public function sendInitial($initial) {
    //   initial.socket.connect()
        $initial->socket->connect();
    //   initial.socket.send initial.data
        $initial->socket->send($initial->data);
    //   initial.socket.disconnect()
        $initial->socket->diconnect();
    }


    // sendInitials: ->
    public function sendInitials() {
    //   send = =>
        $send = function () {
    //     @sendInitial initial for initial in @initials
            foreach ($this->initials as $initial) {
                $this->sendInitial($initial);
            }
    //     @initials = []
            $this->initials = [];
        };

    //   if typeof process isnt 'undefined' and process.execPath and process.execPath.indexOf('node') isnt -1
    //     # nextTick is faster on Node.js
    //     process.nextTick send
    //   else
    //     setTimeout send, 0
        //-- TODO support ReactPHP
        $send();
    }


    // start: ->
    public function start() {
    //   @sendInitials()
        $this->sendInitials();
    }

    // stop: ->
    public function stop() {
    //   # Disconnect all connections
    //   for connection in @connections
        foreach ($this->connections as $connection) {
    //     continue unless connection.isConnected()
            if (!$connection->isConnected()) {
                continue;
            }
    //     connection.disconnect()
            $connection->disconnect();
        }
    //   # Tell processes to shut down
    //   for id, process of @processes
        foreach ($this->processes as $id => $process) {
    //     process.component.shutdown()
            $process->component->shutdown();
        }
    }
}