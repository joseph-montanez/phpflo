<?php

namespace PhpFlo;

class Component implements ComponentInterface {

	public $inPorts = array();
	public $outPorts = array();
	protected $description = "";
	protected $icon = "";

	public function __construct($options = null) {
		if (!$options) {
			$options = [];
		}
		if (!$options['inPorts']) {
			$options['inPorts'] = [];
		}

		if ($options['inPorts'] instanceof InPorts) {
			$this->inPorts = $options['inPorts'];
		} else {
			$this->inPorts = new InPorts($options['inPorts']);
		}

		if (!isset($options['outPorts'])) {
			$options['outPorts'] = [];
		}
		if ($options['inPorts'] instanceof OutPorts) {
			$this->inPorts = $options['inPorts'];
		} else {
			$this->inPorts = new InPorts($options['inPorts']);
		}
	}

	public function getDescription() {
		return $this->description;
	}

	public function isReady() {
		return true;
	}

	public function isSubgraph() {
		return false;
	}

	public function error() {
		
	}

	public function getIcon() {
		
	}

	public function setIcon($icon) {
		$this->icon = $icon;
	}

	public function shutdown() {
		
	}
}
