<?php


add_filter("wpheadless/modules/load", "wpheadless_settings_load_module");
function wpheadless_settings_load_module($modules)
{
    $modules["settings"] = "WPHeadlessSettings";
    return $modules;
}

class WPHeadlessSettings extends WPHeadlessModule
{


    public function __construct()
    {
        parent::__construct();

        add_action("wpheadless/routes/new", array($this, "init_routes"));

    }

    function init_routes()
    {


        $this->console("Loading Route [settings]");
        register_rest_route('wp/v2', '/settings/', array(
            'methods' => 'GET',
            'callback' => array($this, "get_settings_response"),
            'permission_callback' => '__return_true',
            'args' => array(
                'slug' => array(
                    'required' => false
                )
            )
        ));
    }


    function get_settings_response(WP_REST_Request $request)
    {
        $_req = array();
        $_req["settings"] = array();
        $_req["settings"]["ca"] = array();
        $_req["settings"]["es"] = array();

        $_req["settings"]["ca"]["logo"] = array();
        $_req["settings"]["ca"]["logo"]["alt"] = "Logo LaPometa Agència";
        $_req["settings"]["ca"]["logo"]["title"] = "LaPometa Agència";
        $_req["settings"]["ca"]["logo"]["width"] = 100;
        $_req["settings"]["ca"]["logo"]["height"] = 60;
        $_req["settings"]["ca"]["logo"]["dark"] = array("id" => 1, "src" => "", "width" => 60, "height" => 60);
        $_req["settings"]["ca"]["logo"]["light"] = array("id" => 1, "src" => "", "width" => 60, "height" => 60);
        $_req["settings"]["ca"]["logo"]["dark"] = array("id" => 1, "src" => "", "width" => 60, "height" => 60);
        $_req["settings"]["ca"]["logo"]["mini-light"] = array("id" => 1, "src" => "", "width" => 60, "height" => 60);
        $_req["settings"]["ca"]["logo"]["mini-dark"] = array("id" => 1, "src" => "", "width" => 60, "height" => 60);

        $_req["settings"]["es"]["logo"] = array();
        $_req["settings"]["es"]["logo"]["alt"] = "Logo LaPometa Agencia";
        $_req["settings"]["es"]["logo"]["title"] = "LaPometa Agencia";
        $_req["settings"]["es"]["logo"]["width"] = 100;
        $_req["settings"]["es"]["logo"]["height"] = 60;
        $_req["settings"]["es"]["logo"]["dark"] = array("id" => 1, "src" => "", "width" => 60, "height" => 60);
        $_req["settings"]["es"]["logo"]["light"] = array("id" => 1, "src" => "", "width" => 60, "height" => 60);
        $_req["settings"]["es"]["logo"]["light-mini"] = array("id" => 1, "src" => "", "width" => 60, "height" => 60);
        $_req["settings"]["es"]["logo"]["dark-mini"] = array("id" => 1, "src" => "", "width" => 60, "height" => 60);
        return $_req;
    }

}



