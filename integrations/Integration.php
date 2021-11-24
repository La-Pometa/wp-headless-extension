<?php
require_once "IntegrationInterface.php";

abstract class Integration implements IntegrationInterface
{
    public bool $debug = false;

    public function __construct()
    {
        $this->init();
    }

    public function console($string)
    {
        if ($this->debug) {
            echo $string;
        }
    }
}