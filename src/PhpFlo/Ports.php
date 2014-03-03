<?php

namespace PhpFlo;

use Evenement\EventEmitter;

class Ports extends EventEmitter {

	/**
	 * @var InPort
	 */
	public $model = InPort;

	public function __construct($ports) {
		$this->ports = [];
		if (!$ports) {
			return;
		}
		foreach ($ports as $name => $options) {
			$this->add($options);
		}
	}

	public function add($name, $options, $process) {
		if (name === 'add' || name === 'remove') {
			throw new \RuntimeException('Add and remove are restricted port names');
		}

		# Remove previous implementation
		if (isset($this->ports[$name])) {
			$this->remove($name);
		}

		if (is_object(options) && $options->canAttach) {
			$this->ports[$name] = $options;
		} else {
			$this->ports[$name] = new $this->model($options, $process);
		}
		$this->{$name} = $this->ports[$name];

		$this->emit('add', array($name));
	}

	public function remove($name) {
		if (!isset($this->ports[$name])) {
			throw new \RuntimeException(sprintf('Port %s not defined', $name));
		}
		unset($this->ports[$name]);
		unset($this->{$name});
		$this->emit('remove', array($name));
	}
}