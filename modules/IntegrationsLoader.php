<?php


class IntegrationsLoader
{

    private string $vendors_path;
    private array $integrations;
    private array $integrationsFile;
    private array $integrationsAdmin;


    public function __construct()
    {
        $this->vendors_path = false;
        $this->integrations = array();
        $this->integrationsFile = array();
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
                $this->integrationsFile[$integration] = $file;
                $this->integrations[$integration] = $file;
                add_action('admin_init', function(){
                    require_once($file);
                });
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

        $integrationsAdmin = apply_filters("wpheadless/admin/modules",array());

        if ( !is_array($this->integrationsAdmin)) {$this->integrationsAdmin=array();}

        foreach($integrationsAdmin as $integration_id => $integration_data) {
            
            $integration_class=get_array_value($integration_data,"class",false);
            $integration_file=get_array_value($integration_data,"file",false);

            if ( $integration_class) {
            
                $this->integrationsAdmin[$integration_id]=new $integration_class();
                $this->integrationsFile[$integration_id] = $integration_file;
            
            }
        }

    }
    public function loadAdminFilters() {
        foreach($this->integrationsAdmin as $integration_id => $integration_class) {
            if ( property_exists($this->integrationsAdmin[$integration_id],"init") ) {
                $this->integrationsAdmin[$integration_id]->init();
            }
            // else {
            //     echo "<br> Integrations loadAdminFilters() [".$integration_id."] no has init()";
            // }
        }
    }


}