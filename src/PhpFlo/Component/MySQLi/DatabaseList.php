<?php
namespace PhpFlo\Component\MySQLi;

use PhpFlo\Component;
use PhpFlo\Port;

class DatabaseList extends Component
{
	public function __construct()
	{
		$this->inPorts['in'] = new Port();
		$this->inPorts['in']->on('data', array($this, 'listDatabases'));

		$this->outPorts['out'] = new Port();
		$this->outPorts['error'] = new Port();

	}

	public function listDatabases(\mysqli $mysqli)
	{
		$result = $mysqli->query('SHOW DATABASES');
		if ($result === false) {
			$this->outPorts['error']->send('SQL Error (' . $mysqli->error . ')');
		} else {
			$databases = $result->fetch_all(MYSQLI_ASSOC);
			$result->close();
			foreach ($databases as $database) {
			   $this->outPorts['out']->send($database['Database']);
			}
			$this->outPorts['out']->disconnect();
		}
	}
}