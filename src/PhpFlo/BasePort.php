<?php

namespace PhpFlo;

use Evenement\EventEmitter;

class BasePort extends EventEmitter {
    public static $validTypes = [
        'all',
        'string',
        'number',
        'int',
        'object',
        'array',
        'boolean',
        'color',
        'date',
        'bang'
    ];

    public $options;
    public $sockets;
    public $node;
    public $name;
    public $buffer;

	public function __construct($options = []) {
        $this->handleOptions($options);

        $this->sockets = [];
        $this->buffer = [];
		$this->node = null;
		$this->name = null;
	}

    //+  handleOptions: (options) ->
    public function handleOptions ($options) {
    //  +    options = {} unless options
        $this->options = $options;
    //  +    options.datatype = 'all' unless options.datatype
        if (!isset($this->options['datatype'])) {
            $this->options['datatype'] = 'all';
        }
    //  +    options.required = true if options.required is undefined
        if (!isset($this->options['required'])) {
            $this->options['required'] = true;
        }
    //  +
    //  +    if validTypes.indexOf(options.datatype) is -1
        if (in_array($options['datatype'], self::$validTypes) === false) {
    //  +      throw new Error "Invalid port datatype '#{options.datatype}' specified, valid are #{validTypes.join(' ,')}"
            throw new \RuntimeException(sprintf('Invalid port datatype \'%s\' specified, valid are %s', $options['datatype'], implode(' ,', self::$validTypes)));
        }
    //  +
    //  +    if options.type and options.type.indexOf('/') is -1
        if ($options['type'] && strpos($options['type'], '/') === -1) {
    //  +      throw new Error "Invalid port type '#{options.type}' specified. Should be URL or MIME type"
    //  +
        }
    //  +    @options = options
        $this->options = $options;
    }

	public function getId() {
		if (!$this->node && $this->name) {
			return 'Port';
		}
		return sprintf('%s %s', $this->node, strtoupper($this->name));
	}

	public function getDataType() {
		return $this->options . datatype;
	}

	public function getDescription() {
		return $this->options . description;
	}

	public function attach($socket, $index = null) {
		if (!$this->isAddressable() || $index === null) {
			$index = count($this->sockets);
		}
		$this->sockets[$index] = $socket;
		$this->attachSocket($socket, $index);
		if ($this->isAddressable()) {
			$this->emit('attach', array($socket, $index));
			return;
		}
		$this->emit('attach', array($socket));
	}

	public function attachSocket($socket, $localId = null) {
		
	}

	public function detach($socket) {
		$index = array_search($socket, $this->sockets);
		if ($index === false) {
			return;
		}
		unset($this->sockets[$index]);
		$this->sockets = array_values($this->sockets);
		if ($this->isAddressable()) {
			$this->emit('detach', array($socket, $index));
			return;
		}
		$this->emit('detach', array($socket));
	}

	public function isAddressable() {
		if ($this->options['addressable']) {
			return true;
		}
		return false;
	}

	public function isBuffered() {
		if ($this->options['buffered']) {
			return true;
		}
		return false;
	}

	public function isRequired() {
		if ($this->options['required']) {
			return true;
		}
		return false;
	}

	public function isAttached($socketId = null) {
		if ($this->isAddressable() && $socketId !== null) {
			if ($this->sockets[$socketId]) {
				return true;
			}
			return false;
		}
		if (count($this->sockets) > 0) {
			return true;
		}
		return false;
	}

	public function isConnected($socketId = null) {
		if ($this->isAddressable()) {
			if ($socketId === null) {
				throw new \RuntimeException(sprintf('%s: Socket ID required', $this->getId()));
			}
			if (!isset($this->sockets[$socketId])) {
				throw new \RuntimeException(sprintf('%s: Socket %s not available', $this->getId(), $socketId));
			}
			return $this->sockets[$socketId] . isConnected();
		}

		$connected = false;
		foreach ($this->sockets as $socket) {
			if ($socket->isConnected()) {
				$connected = true;
			}
		}
		return $connected;
	}

	public function canAttach() {
		return true;
	}

}
