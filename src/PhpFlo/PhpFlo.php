<?php
namespace PhpFlo;

class PhpFlo {
//exports.createNetwork = (graph, callback, delay) ->
	public static function createNetwork(Graph $graph, $callback, $delay) {
//  network = new exports.Network graph
		$network = new Network($graph);
//  networkReady = (network) ->
		$networkReady = function (Network $network) use ($callback) {
//    callback network if callback?
			if (is_callable($callback)) {
				callback($network);
			}
//    # Send IIPs
//    network.start()
			$network->start();
		};
//
//  # Empty network, no need to connect it up
//  if graph.nodes.length is 0
		if (count($graph->nodes) === 0) {
//    setTimeout ->
//      networkReady network
//    , 0
			//-- TODO: support zero timeout? - ReactPHP
			call_user_func_array($callback, [$network]);
//    return network
			return $network;
		}
//
//  # Ensure components are loaded before continuing
//  network.loader.listComponents ->
		$network->loader->listComponents = function () use ($delay, $callback, $network, $networkReady) {
//    # In case of delayed execution we don't wire it up
//    if delay
			if (isset($delay)) {
//      callback network if callback?
				if (is_callable($callback)) {
					call_user_func_array($callback, [$network]);
				}
//      return
				return;
			}
//    # Wire the network up and start execution
//    network.connect -> networkReady network
			$network->connect = function () use ($network, $networkReady) {
				call_user_func_array($networkReady, [$network]);
			};
		};
//
//  network
		return $network;
	}
}
