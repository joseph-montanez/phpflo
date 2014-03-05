<?php
namespace PhpFlo;

use Evenement\EventEmitter;

class InternalSocket extends EventEmitter implements SocketInterface
{
    public $connected;
    public $groups;
    public $to;
    public $from;
	
	// constructor: ->
	public function __construct() {
		// @connected = false
		$this->connected = false;
		// @groups = []
		$this->groups = [];
	}

	// connect: ->
    public function connect()
    {
		// return if @connected
		if ($this->connected) {
			return;
		}
		//@connected = true
        $this->connected = true;
		
		//@emit 'disconnect', @
        $this->emit('connect', array($this));
    }

	// disconnect: ->
    public function disconnect()
    {
		// return unless @connected
		if (!$this->connected) {
			return;
		}
		// @connected = false
        $this->connected = false;
		// @emit 'disconnect', @
        $this->emit('disconnect', [$this]);
    }

	// isConnected: -> @connected
    public function isConnected()
    {
        return $this->connected;
    }

	// send: (data) ->
    public function send($data)
    {
		// @connect() unless @connected
		if (!$this->connected) {
			return;
		}
		// @emit 'data', data
        $this->emit('data', [$data]);
    }
	
	// beginGroup: (group) ->
	public function beginGroup($group)
	{
		// @groups.push group
		$this->groups []= $group;
		// @emit 'begingroup', group
		$this->emit('begingroup', [$group]);
	}
	
	// endGroup: ->
	public function endGroup()
	{
		// @emit 'endgroup', @groups.pop()
		$this->emit('endgroup', [array_pop($this->groups)]);
	}
	
	// getId: ->
    public function getId()
    {
		// fromStr = (from) ->
		$fromStr = function ($form) {
			// "#{from.process.id}() #{from.port.toUpperCase()}"
			return sprintf('%s() %s', $form->process.id, strtoupper($from->port));
		};
		// toStr = (to) ->
		$toStr = function ($to) {
			// "#{to.port.toUpperCase()} #{to.process.id}()"
			return sprintf('%s %s()', strtoupper($to->port), $to->process->id);
		};
		
		// return "UNDEFINED" unless @from or @to
		if (!isset($this->from) || !isset($this->to)) {
			return 'UNDEFINED';
		}
		
		// return "#{fromStr(@from)} -> ANON" if @from and not @to
        if ($this->from && !$this->to) {
            return sprintf('%s -> ANON', $fromStr($this->from));
        }
		// return "DATA -> #{toStr(@to)}" unless @from
        if (!$this->from) {
			return sprintf('DATA -> %s', $toStr($this->to));
        }
		
		// "#{fromStr(@from)} -> #{toStr(@to)}"
		return sprintf('%s -> %s', $fromStr($this->to), $toStr($this->to));
    }

    public static function createSocket() {
        return new InternalSocket();
    }
}
