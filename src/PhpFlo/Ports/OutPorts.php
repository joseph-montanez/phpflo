<?php

namespace PhpFlo\Ports;

class OutPorts extends Ports {
	public $model = OutPort;
	
	public function connect($name, $socketId) {
		if (!isset($this->ports[$name])) {
			throw new \RuntimeException(sprintf('Port %s not available', $name));
		}
		$this->ports[$name]->connect($socketId);
	}
	
	public function beginGroup($name, $group, $socketId) {
		if (!isset($this->ports[$name])) {
			throw new \RuntimeException(sprintf('Port %s not available', $name));
		}
		$this->ports[$name]->beginGroup($group, $socketId);
	}
	
	public function send($name, $data, $socketId) {
		if (!isset($this->ports[$name])) {
			throw new \RuntimeException(sprintf('Port %s not available', $name));
		}
		$this->ports[$name]->send($data, $socketId);
	}
	
	public function endGroup($name, $socketId) {
		if (!isset($this->ports[$name])) {
			throw new \RuntimeException(sprintf('Port %s not available', $name));
		}
		$this->ports[$name]->endGroup($socketId);
	}
	
	public function disconnect($name, $socketId) {
		if (!isset($this->ports[$name])) {
			throw new \RuntimeException(sprintf('Port %s not available', $name));
		}
		$this->ports[$name]->disconnect($socketId);
	}
}