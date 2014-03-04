<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PhpFlo;

class InPort extends BasePort {

	//put your code here

	public function __construct($options, $process = null) {
		$this->process = null;

		if (!process && is_callable($options)) {
			$process = $options;
			$options = [];
		}

		if ($options && !isset($options['buffered'])) {
			$options['buffered'] = false;
		}

		if ($process) {
			if (!is_callable($process)) {
				throw new \RuntimeException('process must be a function');
			}
			$this->process = $process;
		}
		parent::__construct($options);

        $this->sendDefault();
	}

	public function attachSocket($socket, $localId = null) {
		$socket->on('connect', function () use ($socket, $localId) {
			$this->handleSocketEvent('connect', $socket, $localId);
		});
		$socket->on('begingroup', function ($group) use ($localId) {
			$this->handleSocketEvent('begingroup', $group, $localId);
		});
		$socket->on('data', function ($data) use ($localId) {
			$this->handleSocketEvent('data', $data, $localId);
		});
		$socket->on('endgroup', function ($group) use ($localId) {
			$this->handleSocketEvent('endgroup', $group, $localId);
		});
		$socket->on('disconnect', function () use ($socket, $localId) {
			$this->handleSocketEvent('disconnect', $socket, $localId);
		});
	}

	public function handleSocketEvent($event, $payload, $id) {
        # Handle buffering
        // if @isBuffered()
        if ($this->isBuffered()) {
            // @buffer.push
            $this->buffer []= [
                // event: event
                'event' => $event,
                // payload: payload
                'payload' => $payload,
                // id: id
                'id' => $id,
            ];

            # Notify receiver
            // if @isAddressable()
            if ($this->isAddressable()) {
                if ($this->process) {
                    $this->process($event, $id, $this->nodeInstance);
                }
                // @emit event, id
                $this->emit($event, $id);
            } else {
                // @process event, @nodeInstance if @process
                $this->process($event, $id, $this->nodeInstance);
                // @emit event
                $this->emit($event);
            }
            // return
            return;
        }

		# Call the processing function
		if ($this->process) {
			if ($this->isAddressable()) {
				$this->process($event, $payload, $id, $this->nodeInstance);
			} else {
				$this->process($event, $payload, $this->nodeInstance);
			}
		}

		# Emit port event
		if ($this->isAddressable()) {
			return $this->emit($event, [$payload, $id]);
		}

		$this->emit($event, [$payload]);
	}

    // sendDefault: ->
    public function sendDefault() {
        // return if @options.default is undefined
        if (!isset($this->options)) {
            return;
        }
        // setTimeout =>
        // for socket, idx in @sockets
        foreach ($this->sockets as $idx => $socket) {
            // @handleSocketEvent 'data', @options.default, idx
            $this->handleSocketEvent('data', $this->options['default'], $idx);
        }
        // , 0
    }


    // validateData: (data) ->
    public function validateData($data) {
    //   return unless @options.values
    //   if @options.values.indexOf(data) is -1
    //     throw new Error 'Invalid data received'
    }

    // # Returns the next packet in the buffer
    // receive: ->
    public function receive() {
        if (!$this->isBuffered()) {
    //   unless @isBuffered()
    //     throw new Error 'Receive is only possible on buffered ports'
            throw new \RuntimeException('Receive is only possible on buffered ports');
        }
    //   @buffer.shift()
        array_shift($this->buffer);
    }
}
