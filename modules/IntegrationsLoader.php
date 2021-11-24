<?php


class IntegrationsLoader
{

    private string | bool $vendors_path;
    private array $integrations;


    public function __construct()
    {
        $this->vendors_path = false;
        $this->integrations = array();
    }

    public function load()
    {
        $path = $this->get_path();
        $search = $path . "*.php";

        $files = glob($search);
        if ($files and is_array($files)) {
            foreach ($files as $file) {
                $integration = str_replace(array($this->get_path(), ".php"), array("", ""), $file);
                $this->integrations[$integration] = $file;
                require_once($file);
            }
        }
    }

    public function get_path(): string
    {
        if ($this->vendors_path == false) {
            $this->vendors_path = dirname(__FILE__) . "/../integrations/";
        }
        return $this->vendors_path;
    }

}