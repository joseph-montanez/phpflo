<?php
namespace PhpFlo;

class PhpFlo
{
//exports.createNetwork = (graph, callback, delay) ->
    public static function createNetwork(Graph $graph, $callback = null, $delay = null)
    {
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
            var_dump('stat');
            return $network->start();
        };
//
//  # Empty network, no need to connect it up
//  if graph.nodes.length is 0
        if (count($graph->nodes) === 0) {
//    setTimeout ->
//      networkReady network
//    , 0
            //-- TODO: support zero timeout? - ReactPHP
            $networkReady($network);
//    return network
            return $network;
        }
//
//  # Ensure components are loaded before continuing
//  network.loader.listComponents ->
        $network->getLoader()->listComponents(function () use ($delay, $callback, $network, $networkReady) {
//    # In case of delayed execution we don't wire it up
//    if delay
            if (isset($delay)) {
//      callback network if callback?
                if (is_callable($callback)) {
                    $callback($network);
                }
//      return
                return;
            }
//    # Wire the network up and start execution
//    network.connect -> networkReady network
            return $network->connect(function () use ($network, $networkReady) {
                return $networkReady($network);
            });
        });
//
//  network
        return $network;
    }
}
