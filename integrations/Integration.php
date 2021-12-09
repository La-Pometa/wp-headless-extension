<?php
require_once "IntegrationInterface.php";

abstract class Integration implements IntegrationInterface
{
    public bool $debug = true;

    private string $_id;
    public $_version = "0.0.0";
    public $_title = "NoTitleIntegration";
    public $_loaded = false;
    


    public function __construct()
    {

        $this->_id = "id-not-defined";
        $this->_version="0.0.0";
        $this->_title = "Integration::NoTitle";
        $this->_loaded = false;

        $this->integration_id = $integration_id;
        $this->init();


    }

    public function console($string)
    {
        if ($this->debug) {
            echo $string;
        }
    }

    public function info($modules) {

        $modules[$this->_id]=array(
            "id"=>$this->_id,
            "version"=>$this->_version,
            "title"=>$this->_title,
            "loaded"=>$this->loaded
        );

        return $modules;

    }

    public function is_extension_admin_active($extension_id) {
            return apply_filters("wpheadless/integration/admin/active",false,$extension_id);
    }



    
}