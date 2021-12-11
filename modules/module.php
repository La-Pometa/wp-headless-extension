<?php


require_once("admin/admin-panel.php");
require_once("settings.php");
require_once("sitemap.php");
require_once("image.php");
require_once("content.php");
require_once("path.php");
require_once("routes.php");
require_once("archive.php");


class WPHeadlessModules
{
    private $shortcodes = array();
    private $modules_path = false;
    private $modules = array();
    private $module_name = "modules" ;
    var $debug = false;
	var $request_type = false;
    private $WPHeadlessInstance=false;

    public function __construct($module_name = "")
    {
        global $request;
        add_action('plugins_loaded', array($this, 'init'), 0);
        $this->module_name = $module_name;
    }
 
    function setDebug($debug) {
        $this->debug = $debug;
    }
    function init()
    {
    }

    function load(WP_Headless $headless)
    {
        $this->register_modules($headless);
        $this->register_shortcodes();
    }

    function register_modules($headless)
    {
        $modules = array(
            "routes" => "WPHeadlessRoutes",
            "image" => "WPHeadlessImage",
            "content" => "WPHeadlessContent"
        );
        $modules = apply_filters("wpheadless/modules/load", $modules);

        foreach ($modules as $module => $module_class) {

            //Carregar mòdul
            if (!class_exists($module_class)) {
                $this->console("REGISTER MODULE ERROR (CLASS NOT FOUND) [" . $module . "] {" . $module_class . "}");
            } else {
                $this->console("REGISTER MODULE[" . $module . "] {" . $module_class . "}");
                $this->modules[$module] = new $module_class();
                $this->modules[$module]->setInstance($headless);
                $this->modules[$module]->integration_id = $module;
                $this->modules[$module]->start($module, $module_class);
            }
        }
    }

    public function console($string)
    {
        $debug = $this->debug;
        if ($debug) {
            echo "\n<br> [WPHeadless::" . ($this->module_name ? "Module::" . $this->module_name . "" : "Modules") . "] '" . $string . "'";
        }
    }


    public function register_shortcodes()
    {
        foreach ($this->shortcodes as $key => $callback) {
            add_shortcode($key, $callback);
        }
    }

    public function add_shortcode($key, $callback)
    {
        array_push($this->shortcodes, array($key => $callback));
    }


    function get_modules_path()
    {
        if ($this->modules_path == false) {
            $this->modules_path = "../modules/";
        }
        return $this->modules_path;
    }
}


class WPHeadlessModule {
    private $module_name = false;
    private $instance = false;
    function __construct() {
    
    }

    function init() {
        $this->console("Function 'init' must be override on final class");
    }
    public function getDebug() {
        if ( !$this->instance ) {
            $this->console("Error getDebug() => INSTANCE WP_Headless = NULL");
            return false;
        }
        return $this->instance->getDebug();
    }

    public function console($string)
    {

        if ($this->getDebug()) {
            echo "\n<br> [WPHeadless::" . ($this->module_name ? "Module::" . $this->module_name . "" : "Modules") . "] '" . $string . "'";
        }
    }

    function setInstance($instance) {
        $this->instance = $instance;
    }

    function setName($name) {
        $this->module_name = $name;
    }
    function getName() {
        $this->module_name;
    }

    public function start($module_name, $module_class)
    {
        $this->module_name = $module_name;
        $this->module_class = $module_class;
        $this->console("START module '" . $this->module_class . "::" . $this->module_name . "'");
        $this->init();
    }
    function set_request_type($type="") {
        if ( !$this->instance ) {
            $this->console("Error SET_REQUEST_TYPE(".$type.") INSTANCE WP_Headless = NULL");
            return;
        }
		$this->instance->set_request_type($type);
	}
	function is_post_single() {
        if ( !$this->instance ) {
            $this->console("Error GET_REQUEST_TYPE(is_single) INSTANCE WP_Headless = NULL");
            return false;
        }
        return ($this->instance->get_request_type() == "single");
    }
    function is_post_archive() {
        if ( !$this->instance ) {
            $this->console("Error GET_REQUEST_TYPE(is_archive) INSTANCE WP_Headless = NULL");
            return false;
        }
        return ($this->instance->get_request_type() == "archive");
    } 

}
