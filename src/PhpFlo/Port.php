<?php
namespace PhpFlo;

use Evenement\EventEmitter;

class Port extends EventEmitter
{
	public $description = '';
	public $required = true;
	private $type;
    private $name;
	private $node;
	/**
	 *
	 * @var InternalSocket[]
	 */
	private $sockets;
    private $from;

	// constructor: (@type) ->
    public function __construct($type = null)
    {
		// @type = 'all' unless @type
        $this->type = $type ?: 'all';
		// @sockets = []
		$this->sockets = [];
		// @from = null
		$this->from = null;
		// @node = null
		$this->node = null;
		// @name = null
		$this->name = null;
		$this->required;
    }
	
	// getId: ->
	public function getId()
	{
		//  unless @node and @name
		if (!isset($this->node) && !isset($this->name)) {
			// return 'Port'
			return 'Port';
		} else {
			// "#{@node} #{@name.toUpperCase()}"
			return sprintf('%s %s', $this->node, strtoupper($this->name));
		}
	}

    public function getNode() {
        return $this->node;
    }

    public function setNode($node) {
        $this->node = $node;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }
	
	// getDataType: -> @type
	public function getDataType()
	{
		return $this->type;
	}
	
	// getDescription: -> @description
	public function getDescription()
	{
		return $this->description;
	}

	// attach: (socket) ->
	public function attach(SocketInterface $socket)
    {
		// @sockets.push socket
		$this->sockets []= $socket;
		// @attachSocket socket
        $this->attachSocket($socket);
    }

	// attachSocket: (socket, localId = null) ->
    protected function attachSocket(SocketInterface $socket, $localId = null)
    {
		// @emit "attach", socket
        $this->emit('attach', [$socket]);

		// @from = socket.from
        $this->from = $socket->from;
		
		// socket.setMaxListeners 0 if socket.setMaxListeners
		if (method_exists($socket, 'setMaxListeners') && is_callable($socket->setMaxListeners)) {
			$socket->setMaxListeners(0);
		}
		
		// socket.on "connect", =>
		$socket->on('connect', function () use ($socket, $localId) {
			// @emit "connect", socket, localId
			$this->emit('connect', [$socket, $localId]);
		});
		
		// socket.on "begingroup", (group) =>
		$socket->on('begingroup', function ($group) use ($localId) {
			// @emit "begingroup", group, localId
			$this->emit('begingroup', [$group, $localId]);
		});
		
		// socket.on "data", (data) =>
		$socket->on('data', function ($data) use ($localId) {
			// @emit "begingroup", group, localId
			$this->emit('data', [$data, $localId]);
		});
		
		// socket.on "endgroup", (group) =>
		$socket->on('endgroup', function ($group) use ($localId) {
			// @emit "begingroup", group, localId
			$this->emit('endgroup', [$group, $localId]);
		});
		
		// socket.on "disconnect", =>
		$socket->on('disconnect', function () use ($socket, $localId) {
			// @emit "disconnect", socket, localId
			$this->emit('data', [$socket, $localId]);
		});
    }
	
	// connect: ->
	public function connect()
	{
		// if @sockets.length is 0
		if (count($this->sockets) === 0) {
			// throw new Error "#{@getId()}: No connections available"
			throw new \RuntimeException(sprintf('%s: No connections available', $this->getId()));
		}
		// socket.connect() for socket in @sockets
		foreach ($this->sockets as $socket) {
			$socket->connect();
		}
	}
	
	// beginGroup: (group) ->
	public function beginGroup ($group) {
		// if @sockets.length is 0
		if (count($this->sockets) === 0) {
			// throw new Error "#{@getId()}: No connections available"
			throw new \RuntimeException(sprintf('%s: No connections available', $this->getId()));
		}
		
		// @sockets.forEach (socket) ->
		array_map (function (InternalSocket $socket) use ($group) {
			// return socket.beginGroup group if socket.isConnected()
			if ($socket->isConnected()) {
				return $socket->beginGroup($group);
			}
			// socket.once 'connect', ->
			$socket->once('connect', function () use ($socket, $group) {
				// socket.beginGroup group
				$socket->beginGroup($group);
			});
			// do socket.connect
			$socket->connect();
		}, $this->sockets);
	}
	
	// send: (data) ->
    public function send($data)
    {
		// if @sockets.length is 0
		if (count($this->sockets) === 0) {
			// throw new Error "#{@getId()}: No connections available"
			throw new \RuntimeException(sprintf('%s: No connections available', $this->getId()));
        }
		
		// @sockets.forEach (socket) ->
		array_map (function (InternalSocket $socket) use ($data) {
			// return socket.send data if socket.isConnected()
			if ($socket->isConnected()) {
				return $socket->send($data);
			}
			// socket.once 'connect', ->
			$socket->once('connect', function () use ($socket, $data) {
				// socket.send data
				$socket->send($data);
			});
			// do socket.connect
			$socket->connect();
		}, $this->sockets);
    }
	
	// endGroup: ->
	public function endGroup()
	{
		// if @sockets.length is 0
		if (count($this->sockets) === 0) {
			// throw new Error "#{@getId()}: No connections available"
			throw new \RuntimeException(sprintf('%s: No connections available', $this->getId()));
        }
		
		// socket.endGroup() for socket in @sockets
		foreach ($this->sockets as $socket) {
			$socket->endGroup();
		}
	}

	// disconnect: ->
    public function disconnect()
    {
		// if @sockets.length is 0
		if (count($this->sockets) === 0) {
			// throw new Error "#{@getId()}: No connections available"
			throw new \RuntimeException(sprintf('%s: No connections available', $this->getId()));
        }
		
		// socket.disconnect() for socket in @sockets
		foreach ($this->sockets as $socket) {
			$socket->disconnect();
		}
    }
	
	// detach: (socket) ->
	public function detach (InternalSocket $socket) {
		// return if @sockets.length is 0
		if (count($this->sockets) === 0) {
			return;
        }
		
		// socket = @sockets[0] unless socket
		if (!$socket) {
			$socket = $this->sockets[0];
		}
		// index = @sockets.indexOf socket
		$index = array_search($socket, $this->sockets);
		// return if index is -1
		if ($index === false) {
			return;
		}
		
		// @sockets.splice index, 1
		unset($this->sockets[$index]);
		$this->sockets = array_values($this->sockets);
		
		// @emit "detach", socket
		$this->emit('detach', [$socket]);
	}

	// isConnected: ->
    public function isConnected()
    {
		// connected = false
		$connected = false;
        if (!$this->socket) {
            return false;
        }
		
		// @sockets.forEach (socket) =>
		array_map (function (InternalSocket $socket) use ($connected) {
			// if socket.isConnected()
			if ($socket->isConnected()) {
				// connected = true
				$connected = true;
			}
		}, $this->sockets);

		// connected
        return $connected;
    }
	
	// isAddressable: -> false
	public function isAddressable()
	{
		return false;
	}
	
	// isRequired: -> @required
	public function isRequired()
	{
		// isRequired: -> 
		return $this->required;
	}
	
	// isAttached: ->
	public function isAttached()
	{
		// return if @sockets.length is 0
		if (count($this->sockets) > 0) {
			return true;
        }
		// false
		return false;
	}
	
	// canAttach: -> true
    public $canAttach = true;
//	public function canAttach()
//	{
//		return true;
//	}
}