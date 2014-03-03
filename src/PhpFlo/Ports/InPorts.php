<?php

namespace PhpFlo\Ports;

class InPorts extends Ports {

	public function on($name, $event, $callback) {
		if (!isset($this->ports[$name])) {
			throw new \RuntimeException(sprintf('Port %s not available', $name));
		}
		$this->ports[$name]->on($event, $callback);
	}

	public function once($name, $event, $callback) {
		if (!isset($this->ports[$name])) {
			throw new \RuntimeException(sprintf('Port %s not available', $name));
		}
		$this->ports[$name]->once($event, $callback);
	}

}