<?php


require_once("admin-panel.php");
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
	private $module_name = "modules";
	var $debug = false;

	public function __construct($module_name = "")
	{
		add_action('plugins_loaded', array($this, 'init'), 0);
		$this->module_name = $module_name;
	}

	function init()
	{
	}

	function load()
	{
		$this->register_modules();
		$this->register_shortcodes();
	}
	function register_modules()
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
				$this->console("REGISTER MODULE ERROR (CLASS NOT FOUND) [" . $module . "] {" . $module_class . "}", $this->debug);
			} else {
				$this->console("REGISTER MODULE[" . $module . "] {" . $module_class . "}", $this->debug);
				$this->modules[$module] = new $module_class();
				$this->modules[$module]->start($module, $module_class);
				$this->modules[$module]->debug = $this->debug;
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
	public function start($module_name, $module_class)
	{
		$this->module_name = $module_name;
		$this->module_class = $module_class;
		if ($this->debug) {
			$this->console("START module '" . $this->module_class . "::" . $this->module_name . "'", $this->debug);
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
