<?php

/**
 * @package PhpFlo
 */

namespace PhpFlo;

use Evenement\EventEmitter;

class Graph extends EventEmitter {

	public $name = '';
	public $properties = [];
	public $nodes = [];
	public $edges = [];
	public $initializers = [];
	public $exports = [];
	public $inports = [];
	public $outports = [];
	public $groups = [];
	public $transaction;
    public $baseDir;
    public $componentLoader;

// constructor: (@name = '') ->
	public function __construct($name = '') {
		$this->name = $name;
// @properties = {}
		$this->properties = [];
// @nodes = []
		$this->nodes = [];
// @edges = []
		$this->edges = [];
// @initializers = []
		$this->initializers = [];
// @exports = []
		$this->exports = [];
// @inports = {}
		$this->inports = [];
// @outports = {}
		$this->outports = [];
// @groups = []
		$this->groups = [];
// @transaction =
		$this->transaction = [
// id: null
			'id' => null,
			// depth: 0
			'depth' => 0
		];
	}

// startTransaction: (id, metadata) ->
	public function startTransaction($id, $metadata = null) {
// if @transaction.id
		if (isset($this->tranaction['id'])) {
// throw Error("Nested transactions not supported")
			throw new \RuntimeException('Nested transactions not supported');
		}

// @transaction.id = id
		$this->transaction['id'] = $id;
// @transaction.depth = 1
		$this->transaction['depth'] = 1;
//  @emit 'startTransaction', id, metadata
		$this->emit('startTransaction', array($id, $metadata));
	}

// endTransaction: (id, metadata) ->
	public function endTransaction($id, $metadata = null) {
// if not @transaction.id
		if (!isset($this->tranaction['id'])) {
// throw Error("Nested transactions not supported")
			throw new \RuntimeException('Attempted to end non-existing transaction');
		}

// @transaction.id = null
		$this->transaction['id'] = null;
// @transaction.depth = 0
		$this->transaction['depth'] = 0;
// @emit 'endTransaction', id, metadata
		$this->emit('endTransaction', array($id, $metadata));
	}

// checkTransactionStart: () ->
	public function checkTransactionStart() {
// if not @transaction.id
		if (!isset($this->transaction['id'])) {
// @startTransaction 'implicit'
			$this->startTransaction('implicit');
		}
// else if @transaction.id == 'implicit'
		else if ($this->transaction['id'] === 'implicit') {
// @transaction.depth += 1
			$this->transaction['depth'] += 1;
		}
	}

// checkTransactionEnd: () ->
	public function checkTransactionEnd() {
// if @transaction.id == 'implicit'
		if ($this->transaction['id'] === 'implicit') {
// @transaction.depth -= 1
			$this->transaction['id'] -= 1;
		}
// if @transaction.depth == 0
		if ($this->transaction['depth'] === 0) {
// @endTransaction 'implicit'
			$this->endTransaction('implicit');
		}
	}

// setProperties: (properties) ->
	public function setProperties($properties) {
// @checkTransactionStart()
		$this->checkTransactionStart();
// before = clone @properties
		$before = clone $properties;
// for item, val of properties
		foreach ($properties as $item => $val) {
// @properties[item] = val
			$this->properties[$item] = $val;
		}
// @emit 'changeProperties', @properties, before
		$this->emit('changeProperties', array($this->properties, $before));
// @checkTransactionEnd()
		$this->checkTransactionEnd();
	}

// addExport: (publicPort, nodeKey, portKey, metadata = {x:0,y:0}) ->
	public function addExport($publicPort, $nodeKey, $portKey, $metadata = ['x' => 0, 'y' => 0]) {
// return unless @getNode nodeKey
		if (!$this->getNode($nodeKey)) {
			return;
		}

// @checkTransactionStart()
		$this->checkTransactionStart();
// exported =
		$exported = [
// public: publicPort
			'public' => $publicPort,
			// process: nodeKey
			'process' => $nodeKey,
			// port: portKey
			'port' => $portKey,
			// metadata: metadata
			'metadata' => $metadata,
		];
// @exports.push exported
		$this->exports [] = $exported;
// @emit 'addExport', exported
		$this->emit('addExport', array($exported));
// @checkTransactionEnd()
		$this->checkTransactionEnd();
	}

	public function removeExport($publicPort) {
		$publicPort = strtolower($publicPort);
		$found = null;
		foreach ($this->exports as $idx => $exported) {
			if ($exported['public'] === $publicPort) {
				$found = $exported;
			}
		}

		if (!$found) {
			return;
		}
		$this->checkTransactionStart();
		unset($this->exports[array_search($found, $this->exports)]);
		$this->exports = array_values($this->exports);
		$this->emit('removeExport', [$found]);
		$this->checkTransactionEnd();
	}

	public function addInport($publicPort, $nodeKey, $portKey, $metadata) {
# Check that node exists
		if (!$this->getNode($nodeKey)) {
			return;
		}

		$this->checkTransactionStart();
		$this->inports[publicPort] = [
			'process' => $nodeKey,
			'port' => $portKey,
			'metadata' => $metadata
		];
		$this->emit('addInport', [$publicPort, $this->inports[$publicPort]]);
		$this->checkTransactionEnd();
	}

	public function removeInport($publicPort) {
		$publicPort = strtolower($publicPort);
		if (!$this->inports[$publicPort]) {
			return;
		}

		$this->checkTransactionStart();
		$port = $this->inports[$publicPort];
		$this->setInportMetadata($publicPort, []);
		unset($this->inports[$publicPort]);
		$this->emit('removeInport', [$publicPort, $port]);
		$this->checkTransactionEnd();
	}

	public function renameInport($oldPort, $newPort) {
		if (!$this->inports[$oldPort]) {
			return;
		}

		$this->checkTransactionStart();
		$this->inports[$newPort] = $this->inports[$oldPort];
		unset($this->inports[$oldPort]);
		$this->emit('renameInport', [$oldPort, $newPort]);
		$this->checkTransactionEnd();
	}

	public function setInportMetadata($publicPort, $metadata) {
		if (!$this->inports[$publicPort]) {
			return;
		}

		$this->checkTransactionStart();
		$before = clone $this->inports[$publicPort]->metadata;
		$this->inports[$publicPort]->metadata = $this->inports[$publicPort]->metadata ? : [];
		foreach ($metadata as $item => $val) {
			if ($val) {
				$this->inports[$publicPort]->metadata[$item] = $val;
			} else {
				unset($this->inports[$publicPort]->metadata[$item]);
			}
		}
		$this->emit('changeInport', [$publicPort, $this->inports[$publicPort], $before]);
		$this->checkTransactionEnd();
	}

	public function addOutport($publicPort, $nodeKey, $portKey, $metadata) {
# Check that node exists
		if (!$this->getNode($nodeKey)) {
			return;
		}

		$this->checkTransactionStart();

		$this->outports[publicPort] = [
			'process' => $nodeKey,
			'port' => $portKey,
			'metadata' => $metadata
		];
		$this->emit('addOutport', [$publicPort, $this->outports[$publicPort]]);

		$this->checkTransactionEnd();
	}

	public function removeOutport($publicPort) {
		$publicPort = strtolower($publicPort);
		if (!$this->outports[$publicPort]) {
			return;
		}

		$this->checkTransactionStart();

		$port = $this->outports[$publicPort];
		$this->setOutportMetadata($publicPort, []);
		unset($this->outports[$publicPort]);
		$this->emit('removeOutport', [$publicPort, $port]);
		$this->checkTransactionEnd();
	}

	public function renameOutport($oldPort, $newPort) {
		if (!$this->outports[$oldPort]) {
			return;
		}

		$this->checkTransactionStart();
		$this->outports[$newPort] = $this->outports[$oldPort];
		unset($this->outports[$oldPort]);
		$this->emit('renameOutport', [$oldPort, $newPort]);
		$this->checkTransactionEnd();
	}

	public function setOutportMetadata($publicPort, $metadata) {
		if (!$this->outports[$publicPort]) {
			return;
		}

		$this->checkTransactionStart();
		$before = clone $this->outports[$publicPort]->metadata;
		$this->outports[$publicPort]->metadata = $this->outports[$publicPort]->metadata ? : [];
		foreach ($metadata as $item => $val) {
			if (val) {
				$this->outports[$publicPort]->metadata[$item] = $val;
			} else {
				unset($this->outports[$publicPort]->metadata[$item]);
			}
		}
		$this->emit('changeOutport', [$publicPort, $this->outports[$publicPort], $before]);
		$this->checkTransactionEnd();
	}

# ## Grouping nodes in a graph
#

	public function addGroup($group, $nodes, $metadata) {
		$this->checkTransactionStart();

		$g = [
			'name' => $group,
			'nodes' => $nodes,
			'metadata' => $metadata
		];
		$this->groups [] = ($g);
		$this->emit('addGroup', [$g]);

		$this->checkTransactionEnd();
	}

    // renameGroup: (oldName, newName) ->
    public function renameGroup($oldName, $newName) {
    //   @checkTransactionStart()
        $this->checkTransactionStart();
    //   for group in @groups
        foreach ($this->groups as &$group) {
    //     continue unless group
            if (!$group) {
                continue;
            }
    //     continue unless group.name is oldName
            if (!($group['name'] === $oldName)) {
                continue;
            }
    //     group.name = newName
            $group['name'] = $newName;
    //     @emit 'renameGroup', oldName, newName
            $this->emit('renameGroup', $oldName, $newName);
        }
        unset($group);
    //   @checkTransactionEnd()
        $this->checkTransactionEnd();
    }

	public function removeGroup($groupName) {
		$this->checkTransactionStart();

		foreach ($this->groups as $group) {
			if (!$group) {
				continue;
			}
			if (!$group->name === $groupName) {
				continue;
			}
			$this->setGroupMetadata($group->name, []);
			unset($this->groups[array_search($group, $this->groups)]);
			$this->groups = array_values($this->groups);
			$this->emit('removeGroup', [$group]);
		}

		$this->checkTransactionEnd();
	}

	public function setGroupMetadata($groupName, $metadata) {
		$this->checkTransactionStart();
		foreach ($this->groups as $group) {
			if (!$group) {
				continue;
			}
			if (!$group->name === $groupName) {
				continue;
			}
			$before = clone $group->metadata;
			foreach ($metadata as $item => $val) {
				if ($val) {
					$group->metadata[$item] = $val;
				} else {
					unset($group->metadata[$item]);
				}
			}
			$this->emit('changeGroup', [$group, $before]);
		}
		$this->checkTransactionEnd();
	}

# ## Adding a node to the graph
#
# Nodes are identified by an ID unique to the graph-> Additionally,
# a node may contain information on what NoFlo component it===and
# possible display coordinates->
#
# For example:
#
#     myGraph->addNod('Read, 'ReadFile',
#       x: 91
#       y: 154
#
# Addition of a node will emit the `addNode` event{

	// addNode: (id, component, metadata) ->
	public function addNode($id, $component, $metadata = null) {
		// @checkTransactionStart()
		$this->checkTransactionStart();

		// metadata = {} unless metadata
		if (!isset($metadata)) {
			$metadata = [];
		}
		// node =
		$node = [
			// id: id
			'id' => $id,
			// component: component
			'component' => $component,
			// metadata: metadata
			'metadata' => $metadata
		];
		// @nodes.push node
		$this->nodes [] = $node;
		// @emit 'addNode', node
		$this->emit('addNode', [$node]);

		// @checkTransactionEnd()
		$this->checkTransactionEnd();
		// node
		return $node;
	}

# ## Removing a node from the graph
#
# Existing nodes can be removed from a graph by their ID-> This
# will remove the node && also remove all edges connected to it->
#
#     myGraph->removeNod('Read'
#
# Once the node has been removed, the `removeNode` event will be
# emitted->

	public function removeNode($id) {
		$node = $this->getNode($id);
		if (!$node) {
			return;
		}

		$this->checkTransactionStart();

		$toRemove = [];
		foreach ($this->edges as $edge) {
			if ($edge->from->node === $node->id || $edge->to->node === $node->id) {
				$toRemove [] = $edge;
			}
		}
		foreach ($toRemove as $edge) {
			$this->removeEdge($edge->from->node, $edge->from->port, $edge->to->node, $edge->to->port);
		}
		$toRemove = [];
		foreach ($this->initializers as $initializer) {
			if ($initializer->to->node === $node->id) {
				$toRemove [] = $initializer;
			}
		}
		foreach ($toRemove as $initializer) {
			$this->removeInitial($initializer->to->node, $initializer->to->port);
		}
		$toRemove = [];
		foreach ($this->exports as $exported) {
			if (strtolower($id) === $exported->process) {
				$toRemove [] = $exported;
			}
		}
		foreach ($toRemove as $exported) {
			$this->removeExports($exported->public);
		}
		$toRemove = [];
		foreach ($this->inports as $pub => $priv) {
			if ($priv->process === $id) {
				$toRemove [] = $pub;
			}
		}
		foreach ($toRemove as $pub) {
			$this->removeInport($pub);
		}

		$toRemove = [];
		foreach ($this->outports as $pub => $priv) {
			if ($priv->process === $id) {
				$toRemove [] = $pub;
			}
		}
		foreach ($toRemove as $pub) {
			$this->removeOutport($pub);
		}

		foreach ($this->groups as $group) {
			if (!group) {
				continue;
			}
			$index = array_search($id, $group->nodes);
			if ($index === false) {
				continue;
			}
			unset($group->nodes[$index]);
		}

		$this->setNodeMetadata($id, []);

		if (array_search($node, $this->nodes) !== false) {
			unset($this->nodes[array_search($node, $this->nodes)]);
			$this->nodes = array_values($this->nodes);
		}

		$this->emit('removeNode', [$node]);

		$this->checkTransactionEnd();
	}

# ## Getting a node
#
# Nodes objects can be retrieved from the graph by their ID:
#
#     myNode = myGraph->getNod('Read'
	// getNode: (id) ->
	public function getNode($id) {
		// for node in @nodes
		foreach ($this->nodes as $node) {
			// continue unless node
			if (!$node) {
				continue;
			}
			// return node if node.id is id
			if ($node['id'] === $id) {
				return $node;
			}
		}
		// return null
		return null;
	}

# ## Renaming a node
#
# Nodes IDs can be changed by calling this method->

	public function renameNode($oldId, $newId) {
		$this->checkTransactionStart();

		$node = $this->getNode($oldId);
		if (!node) {
			return;
		}
		$node->id = $newId;

		foreach ($this->edges as $edge) {
			if (!edge) {
				continue;
			}
			if ($edge->from->node === $oldId) {
				$edge->from->node = $newId;
			}
			if ($edge->to->node === $oldId) {
				$edge->to->node = $newId;
			}
		}

		foreach ($this->initializers as $iip) {
			if (!iip) {
				continue;
			}
			if ($iip->to->node === $oldId) {
				$iip->to->node = $newId;
			}
		}
		foreach ($this->inports as $pub => $priv) {
			if ($priv->process === $oldId) {
				$priv->process = $newId;
			}
		}
		foreach ($this->outports as $pub => $priv) {
			if ($priv->process === $oldId) {
				$priv->process = $newId;
			}
		}
		foreach ($this->exports as $exported) {
			if ($exported->process === $oldId) {
				$exported->process = $newId;
			}
		}

		foreach ($this->groups as &$group) {
			if (!$group) {
				continue;
			}
			$index = array_search($oldId, $group->nodes);
			if ($index === false) {
				continue;
			}
			$group->nodes[$index] = $newId;
		}
        unset($group);
		$this->emit('renameNode', [$oldId, $newId]);
		$this->checkTransactionEnd();
	}

# ## Changing a node's metadata
#
	# Node metadata can be set or changed by calling this method->

	public function setNodeMetadata($id, $metadata) {
		$node = $this->getNode($id);
		if (!$node) {
			return;
		}

		$this->checkTransactionStart();

		$before = clone $node->metadata;
		if (!$node->metadata) {
			$node->metadata = [];
		}

		foreach ($metadata as $item => $val) {
			if ($val) {
				$node->metadata[$item] = $val;
			} else {
				unset($node->metadata[$item]);
			}
		}

		$this->emit('changeNode', [$node, $before]);
		$this->checkTransactionEnd();
	}

# ## Connecting nodes
#
	# Nodes can be connected by adding edges between a node's outport
# && another node's inport:
#
	#     myGraph->addEdg('Read', 'out', 'Display', 'in'
#
	# Adding an edge will emit the `addEdge` event->

	public function addEdge($outNode, $outPort, $inNode, $inPort, $metadata = null) {
		foreach ($this->edges as $edge) {
# don't add a duplicate edge
			if ($edge['from']['node'] === $outNode && $edge['from']['port'] === $outPort && $edge['to']['node'] === $inNode && $edge['to']['port'] === $inPort) {
				return;
			}
		}
		if (!$this->getNode($outNode)) {
			return;
		}
		if (!$this->getNode($inNode)) {
			return;
		}
		if (!$metadata) {
			$metadata = [];
		}

		$this->checkTransactionStart();

		$edge = [
			'from' => [
				'node' => $outNode,
				'port' => $outPort,
			],
			'to' => [
				'node' => $inNode,
				'port' => $inPort,
			],
			'metadata' => $metadata
		];
		$this->edges [] = $edge;
		$this->emit('addEdge', [$edge]);

		$this->checkTransactionEnd();
		return $edge;
	}

# ## Disconnected nodes
#
	# Connections between nodes can be removed by providing the
# nodes && ports to disconnect->
#
	#     myGraph->removeEdg('Display', 'out', 'Foo', 'in'
#
	# Removing a connection will emit the `removeEdge` event->

	public function removeEdge($node, $port, $node2, $port2) {
		$this->checkTransactionStart();

		$toRemove = [];
		$toKeep = [];
		if ($node2 && $port2) {
			foreach ($this->edges as $edge => $index) {
				if ($edge->from->node === $node && $edge->from->port === $port && $edge->to->node === $node2 && $edge->to->port === $port2) {
					$this->setEdgeMetadata($edge->from->node, $edge->from->port, $edge->to->node, $edge->to->port, []);
					$toRemove [] = $edge;
				} else {
					$toKeep [] = $edge;
				}
			}
		} else {
			foreach ($this->edges as $edge => $index) {
				if (($edge->from->node === $node && $edge->from->port === $port) || ($edge->to->node === $node && $edge->to->port === $port)) {
					$this->setEdgeMetadata($edge->from->node, $edge->from->port, $edge->to->node, $edge->to->port, []);
					$toRemove [] = $edge;
				} else {
					$toKeep [] = $edge;
				}
			}
		}
		$this->edges = $toKeep;
		foreach ($toRemove as $edge) {
			$this->emit('removeEdge', [$edge]);
		}

		$this->checkTransactionEnd();
	}

# ## Getting an edge
#
	# Edge objects can be retrieved from the graph by the node && port IDs:
#
	#     myEdge = myGraph->getEdg('Read', 'out', 'Write', 'in'

	public function getEdge($node, $port, $node2, $port2) {
		foreach ($this->edges as $edge => $index) {
			if (!$edge) {
				continue;
			}
			if ($edge->from->node === $node && $edge->from->port === $port) {
				if ($edge->to->node === $node2 && $edge->to->port === $port2) {
					return $edge;
				}
			}
		}
		return null;
	}

# ## Changing an edge's metadata
#
	# Edge metadata can be set or changed by calling this method->

	public function setEdgeMetadata($node, $port, $node2, $port2, $metadata) {
		$edge = $this->getEdge($node, $port, $node2, $port2);
		if (!$edge) {
			return;
		}

		$this->checkTransactionStart();
		$before = clone $edge->metadata;
		if (!$edge->metadata) {
			$edge->metadata = [];
		}

		foreach ($metadata as $item => $val) {
			if (val) {
				$edge->metadata[$item] = $val;
			} else {
				unset($this->metadata[$item]);
			}
		}

		$this->emit('changeEdge', [$edge, $before]);
		$this->checkTransactionEnd();
	}

# ## Adding Initial Information Packets
#
	# Initial Information Packets (IIPs) can be used for sending data
# to specified node inports without a sending node instance->
#
	# IIPs are especially useful for sending configuration information
# to components at NoFlo network start-up time-> This could include
# filenames to read, or network ports to listen to->
#
	#     myGraph->addInitia('somefile->txt', 'Read', 'source'
#
	# Adding an IIP will emit a `addInitial` event->

	public function addInitial($data, $node, $port, $metadata = null) {
		if (!$this->getNode($node)) {
			return;
		}

		$this->checkTransactionStart();

		$initializer = [
			'from' => [
				'data' => $data
			],
			'to' => [
				'node' => $node,
				'port' => $port,
			],
			'metadata' => $metadata
		];
		$this->initializers [] = $initializer;
		$this->emit('addInitial', [$initializer]);

		$this->checkTransactionEnd();
		return $initializer;
	}

# ## Removing Initial Information Packets
#
	# IIPs can be removed by calling the `removeInitial` method->
#
	#     myGraph->removeInitia('Read', 'source'
#
	# Remove an IIP will emit a `removeInitial` event->

	public function removeInitial($node, $port) {
		$this->checkTransactionStart();

		$toRemove = [];
		$toKeep = [];
		foreach ($this->initializers as $edge => $index) {
			if ($edge->to->node === $node && $edge->to->port === $port) {
				$toRemove [] = $edge;
			} else {
				$toKeep [] = $edge;
			}
		}
		$this->initializers = $toKeep;
		foreach ($toRemove as $edge) {
			$this->emit('removeInitial', [$edge]);
		}
		$this->checkTransactionEnd();
	}

	public function toJSON() {
		$json = [
			'properties' => [],
			'inports' => [],
			'outports' => [],
			'groups' => [],
			'processes' => [],
			'connections' => []
		];

		if ($this->name) {
			$json['properties']['name'] = $this->name;
		}
		foreach ($this->properties as $property => $value) {
			$json['properties'][$property] = $value;
		}

		foreach ($this->inports as $pub => $priv) {
			$json['inports'][$pub] = $priv;
		}
		foreach ($this->outports as $pub => $priv) {
			$json['outports'][$pub] = $priv;
		}

		# Legacy exported ports
		foreach ($this->exports as $exported) {
			if (!$json['exports']) {
				$json['exports'] = [];
			}
			$json['exports'] [] = $exported;
		}

		foreach ($this->groups as $group) {
			$groupData = [
				'name' => $group->name,
				'nodes' => $group->nodes
			];
			if ($group->metadata) {
				$groupData->metadata = $group->metadata;
			}
			$json['groups'] [] = $groupData;
		}

		foreach ($this->nodes as $node) {
			$json['processes'][$node['id']] = [
				'component' => $node['component']
			];
			if ($node['metadata']) {
				$json['processes'][$node['id']]->metadata = $node['metadata'];
			}
		}

		foreach ($this->edges as $edge) {
			$connection = [
				'src' => [
					'process' => $edge['from']['node'],
					'port' => $edge['from']['port']
				],
				'tgt' => [
					'process' => $edge['to']['node'],
					'port' => $edge['to']['port']
				]
			];
			if (count($edge['metadata']) > 0) {
				$connection['metadata'] = $edge['metadata'];
			}
			$json['connections'] [] = $connection;
		}

		foreach ($this->initializers as $initializer) {
			$json['connections'] [] = [
				'data' => $initializer['from']['data'],
				'tgt' => [
					'process' => $initializer['to']['node'],
					'port' => $initializer['to']['port']
				]
			];
		}

		return json_encode($json, JSON_PRETTY_PRINT);
	}

    public static function createGraph($name) {
        return new Graph($name);
    }
}