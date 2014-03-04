<?php
namespace PhpFlo\Component\MySQLi;

use PhpFlo\Component;
use PhpFlo\Port;

class Connect extends Component
{
	/**
	 * If there was a successful database server connection
	 * @var bool
	 */
	public $connected;
    public function __construct()
    {
        $this->inPorts['in'] = new Port();
        $this->inPorts['in']->on('data', array($this, 'connect'));

        $this->outPorts['out'] = new Port();
        $this->outPorts['error'] = new Port();
    }

    public function connect($data)
    {
    	\mysqli_report(MYSQLI_REPORT_STRICT);
    	$mysqli = new \mysqli();

		try {
    		$this->connected = $mysqli->real_connect($data['host'], $data['username'], $data['passwd'], $data['dbname'], $data['port'], $data['socket']);
		} catch (\Exception $error) {
			$this->connected = false;
		}
		
        if ($this->connected === false) {
            $this->outPorts['error']->send(
            	'Connection Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error
        	);
        } else {
	        $this->outPorts['out']->send($mysqli);
	        $this->outPorts['out']->disconnect();
        }
    }
}