<?php


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
    var $request_type = false;
    private $instance = false;

    public function __construct($instance = "")
    {
        global $request;
        $this->module_name = "modules";
        $this->instance = $instance;
    }

    function load(WP_Headless $headless)
    {
        $this->register_modules($headless);
        $this->register_shortcodes();
    }
    function get_instance() {
        return $this->instance;
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
                $this->console("modules","REGISTER MODULE ERROR (CLASS NOT FOUND) [" . $module . "] {" . $module_class . "}");
            } else {
                $this->console("modules","REGISTER MODULE[" . $module . "] {" . $module_class . "}");
                $this->modules[$module] = new $module_class();
                $this->modules[$module]->setInstance($headless);
                $this->modules[$module]->integration_id = $module;
                $this->modules[$module]->start($module, $module_class);
            }
        }
    }  

    public function console($module_name,$string)
    {
        $this->get_instance()->console($module_name,$string);
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

    public function console($string)
    {
        if ( !$this->get_instance() ) {
            //$this->console("Error console() => INSTANCE WP_Headless = NULL");
            return false;
        }
        return $this->get_instance()->console("module(".$this->module_name.")",$string);

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
    public function get_instance() {
        return $this->instance;
    }
    function set_request_type($type="") {
        if ( !$this->get_instance() ) {
            $this->console("modules","Error SET_REQUEST_TYPE(".$type.") INSTANCE WP_Headless = NULL");
            return;
        }
		$this->get_instance()->set_request_type($type);
	}
	function is_post_single() {
        if ( !$this->get_instance()) {
            $this->console("Error GET_REQUEST_TYPE(is_single) INSTANCE WP_Headless = NULL");
            return false;
        }
        return ($this->get_instance()->get_request_type() == "single");
    }
    function is_post_archive() {
        if ( !$this->get_instance() ) {
            $this->console("modules","Error GET_REQUEST_TYPE(is_archive) INSTANCE WP_Headless = NULL");
            return false;
        }
        return ($this->get_instance()->get_request_type() == "archive");
    }
    function is_debug($param="") {
        if ( !$param) {
            $param = "debug";
        }

        //echo "<br> PARAMS:<pre>".print_r($this->get_instance(),true)."</pre>";
        if ( get_array_value($this->get_instance()->get_params(),$param,false) === false) {
            return false;
        }
        return true;
    }
    function is_embed() {

        if ( get_array_value($_GET,"_embed",false) === false) {
            return false;
        }

        return true;

    }
    function console_enable() {
        if ( !$this->get_instance() ) {
            $this->console("modules","Error CONSOLE_ENABLE INSTANCE WP_Headless = NULL");
            return false;
        }
        return $this->get_instance()->console_enable();

    }
    function console_disable() {
        if ( !$this->get_instance() ) {
            $this->console("modules","Error CONSOLE_DISABLE INSTANCE WP_Headless = NULL");
            return false;
        }
        return $this->get_instance()->console_disable();
        
    }

}
