<?php


class IntegrationsLoader
{

    private string $vendors_path;
    private array $integrations;
    private array $integrationsAdmin;


    public function __construct()
    {
        $this->vendors_path = false;
        $this->integrations = array();
        $this->integrationsAdmin = array();
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

    /*- Carregar les integracions a la secció de l'administrador -*/

    public function loadAdmin() {

        $this->integrationsAdmin = apply_filters("wpheadless/admin/modules",array());
        if ( !is_array($this->integrationsAdmin)) {
            $this->integrationsAdmin=array();
        }

        foreach($this->integrationsAdmin as $integration_id => $integration_class) {
            $this->integrationsAdmin[$integration_id]=new $integration_class();
        }

    }
    public function loadAdminFilters() {
        foreach($this->integrationsAdmin as $integration_id => $integration_class) {
            $this->integrationsAdmin[$integration_id]->init();
        }
    }


}