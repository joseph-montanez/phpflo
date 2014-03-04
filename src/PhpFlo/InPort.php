<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PhpFlo;

class InPort extends BasePort {

	//put your code here

	public function __construct($options, $process) {
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
	}

	public function attachSocket($socket, $localId = null) {
		$socket->on('connect', function () use ($this, $socket, $localId) {
			$this->handleSocketEvent('connect', $socket, $localId);
		});
		$socket->on('begingroup', function ($group) use ($this, $localId) {
			$this->handleSocketEvent('begingroup', $group, $localId);
		});
		$socket->on('data', function ($data) use ($this, $localId) {
			$this->handleSocketEvent('data', $data, $localId);
		});
		$socket->on('endgroup', function ($group) use ($this, $localId) {
			$this->handleSocketEvent('endgroup', $group, $localId);
		});
		$socket->on('disconnect', function () use ($this, $socket, $localId) {
			$this->handleSocketEvent('disconnect', $socket, $localId);
		});
	}

	public function handleSocketEvent($event, $payload, $id) {
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
}
