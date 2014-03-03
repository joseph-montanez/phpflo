<?php

namespace PhpFlo;

class OutPort extends BasePort {

	public function connect($socketId = null) {
		$sockets = $this->getSockets(socketId);
		$this->checkRequired(sockets);
		foreach ($sockets as $socket) {
			$socket->connect();
		}
	}

	public function beginGroup($group, $socketId = null) {
		$sockets = $this->getSockets(socketId);
		$this->checkRequired(sockets);
		array_map(function ($socket) {
			if (socket . isConnected()) {
				return $socket->beginGroup(group);
			}
			$socket->once('connect', function () {
				$socket->beginGroup(group);
			});

			$socket . connect();
		}, $sockets);
	}

	public function send($data, $socketId = null) {
		$sockets = $this->getSockets(socketId);
		$this->checkRequired(sockets);

		array_map(function ($socket) use ($data) {
			if (socket . isConnected()) {
				return $socket->send(data);
			}
			$socket->once('connect', function () use ($data) {
				$socket->send(data);
			});

			$socket . connect();
		}, $sockets);
	}

	public function endGroup($socketId = null) {
		$sockets = $this->getSockets($socketId);
		$this->checkRequired(sockets);
		foreach ($sockets as $socket) {
			$socket->endGroup();
		}
	}

	public function disconnect($socketId = null) {
		$sockets = $this->getSockets($socketId);
		$this->checkRequired(sockets);
		foreach ($sockets as $socket) {
			$socket->disconnect();
		}
	}

	public function checkRequired($sockets) {
		if (count(sockets) === 0 && $this->isRequired()) {
			throw new \RuntimeError(sprintf('Error %: No connections available', $this->getId()));
		}
	}

	public function getSockets($socketId) {
		# Addressable sockets affect only one connection at time
		if ($this->isAddressable()) {
			if ($socketId === null) {
				throw new \RuntimeError(sprintf('% Socket ID required', $this->getId()));
			}
			if (!$this->sockets[$socketId]) {
				return [];
			}
			return [$this->sockets[$socketId]];
		}
		# Regular sockets affect all outbound connections
		return $this->sockets;
	}
}