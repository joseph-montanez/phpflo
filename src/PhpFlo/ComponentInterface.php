<?php
namespace PhpFlo;

interface ComponentInterface
{
    public function getDescription();
    public function isReady();
    public function isSubgraph();
    public function setIcon($icon);
    public function getIcon();
    public function error();
    public function shutdown();
}
