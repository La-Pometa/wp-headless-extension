<?php
/*
Plugin Name: Pometa Wordpress Headless API Extension
Plugin URI: https://lapometa.com
Description: A simple wordpress plugin to make your wordpress more headless capable
Version: 0.0.7
Author: La Pometa
Author URI: https://github.com/La-Pometa
GitHub Plugin URI: La-Pometa/pometa-wp-headless-api-extension
Primary Branch: main
License: GPL2
*/
/*
Copyright 2021  Jordi Gómez  (email : gnugomez@gmail.com)
Copyright 2021  Jordi Fonfreda  (email : suport@glapometa.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 5152 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once("includes/common.php");

if (!class_exists('WP_Headless')) {
    class WP_Headless
    {
        /**
         * @var IntegrationsLoader
         */
        private IntegrationsLoader $Integrations;

        /**
         * @var WPHeadlessModules
         */
        private WPHeadlessModules $Modules;

        private string $request_type = "";
        private bool $debug = false;
        private array $debug_buffer = array();
        private float $debug_start=0;
        private $request= false;
        private $params = array();
        private $is_api_request = false;
        private $clean_endpoints = false;
        /**
         * Construct the plugin object
         */
        public function __construct()
        {

            $this->params = $_GET;
            $this->is_api_request="undefined";
            // Debug info
            $this->debug_start = $this->microtime(true);
            if (get_array_value($this->params, "debug", false) !== false) {
                $this->debug=true;
            }
            if (get_array_value($this->params, "clean", false) !== false) {
                $this->clean_endpoints=true;
            }

            // Registrar crides a post_types: per a determinar el tipus de request
            add_filter('rest_post_dispatch',array($this,'_post_dispatch'),2000,3);

            // Registrar Mòduls
            $this->console("boot","Registrar mòduls->inici");
            $this->require("/modules/module.php");
            $this->Modules = new WPHeadlessModules($this);
            $this->console("boot","Registrar mòduls->final");

            // Registrar Integracions
            $this->console("boot","Registrar Integracions->inici");
            $this->require("/modules/IntegrationsLoader.php");
            $this->Integrations = new IntegrationsLoader();
            $this->console("boot","Registrar Integracions->final");

            // Carregar Integracions
            $this->console("boot","Carregar Integracions->inici");
            $this->Integrations->load();
            $this->console("boot","Carregar Integracions->final");

            // Carregar Mòduls + Vendor Mòduls
            $this->console("boot","Carregar Mòduls->inici");
            $this->Modules->load($this);
            $this->console("boot","Carregar Mòduls->final");


            $this->is_rest();
            $this->_get_request_info();
            

            if ( $this->clean_endpoints ) {

                
            }

            // Carregar apartat administrador, si estic a l'administrador
            $this->IntegrationsAdmin=false;
            if ( is_admin()) {

                // Menu 'Plugins'; afegir 'Settings'
                $this->console("boot","Registrar settings");
                $plugin = plugin_basename(__FILE__);
                add_filter("plugin_action_links_$plugin", array($this, 'plugin_settings_link'));


                $this->console("boot","Registrar Administrador");
                // Estic a l'administrador? Carregar ThemeSettings 
                $this->Integrations->loadAdmin();
                $this->IntegrationsAdmin = true;

            }

        } // END public function __construct

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            // Do nothing
        } // END public static function activate

        /**
         * Deactivate the plugin
         */
        public static function deactivate()
        {
            // Do nothing
        } // END public static function deactivate

        // Add the settings link to the plugins page
        function plugin_settings_link($links)
        {
            $settings_link = '<a href="options-general.php?page=wp_headless_settings">Settings</a>';
            array_unshift($links, $settings_link);
            return $links;
        }
        function _filter_content_init($post_type) {
            add_filter('rest_' . $post_type . '_query', array($this, '_filter_content_type'), 5, 2);
        }
        function require($file) {
            $this->console("root"," * require_file($file)->inici");
            require_once(sprintf("%s/".$file, dirname(__FILE__)));
            $this->console("root"," * require_file($file)->final");

        }
  
        function remove_default_endpoints( $endpoints ) {
            return array( );
        }

        // Setter request_type
        function set_request_type($type) : void {
            $this->request_type=$type;
        }
        function console_enable() {
            $this->debug = true;
            return $this->debug;
        }
        function console_disable() {
            $this->debug = false;
            return $this->debug;
        }
        function get_params() {
            return $this->params;
        }
        function is_rest() {
            if ( $this->is_api_request == "undefined" ) {
                $this->is_api_request=false;
                if ( defined("REST_REQUEST") || !is_admin() ) {
                    $this->is_api_request=true;
                }

            }
            return $this->is_api_request;
        }
        // Getter request_type
        function get_request_type() : string {
            return $this->request_type;
        }

        function microtime() {
            return round(microtime(true)*1000,2);
        }

        function _get_request_info() {
            $this->console("root","wpheadless/request/type/action->inici");

            $rest_call = get_array_value($_SERVER,"REDIRECT_URL","");
            $pre = site_url()."/wp-json/wp/v2/";
            $pre2 = parse_url($pre);
            $pre = get_array_value($pre2,"path","");
            $call = str_replace($pre,"",$rest_call);
            $call = ((substr($call,strlen($call)-1,1)=="/") ? substr($call,0,strlen($call)-1) : $call);

            $this->console("root","wpheadless/request/call ".$call);

            $this->call = explode("/",$call);
            $type = "single";
            if ( count($this->call) == 1 ) {
                $type = "archive";
            }
            $this->set_request_type(apply_filters("wpheadless/request/type/filter",$type,$call));
            do_action("wpheadless/request/type/action",$this->get_request_type());
            $this->console("root","wpheadless/request/type/action '".$this->get_request_type()."'");

            $this->console("root","wpheadless/request/type/action->final");
    }


        // Mostrar informació de Debug
        // TODO: Integrair dins de la WP_Rest_Respnse
        function console($module_name,$string) {
            if ($this->debug) {
                $time = $this->microtime(true) - $this->debug_start;
                $time = sprintf("% 5d",$time);
                $string = apply_filters("wpheadless/console/string",$string,$module_name,$string);
                $module = sprintf("%s",str_pad($module_name,25," "));
                $string = sprintf(__("[%sms] PometaHeadless::%s %s","wpheadlesltd"),$time,$module,$string);
                $this->debug_buffer[]=$string;
                //echo "\n".$string;
            }
        }


        // Acció abans d'enviar les dades (debug, cache, ...)
        function _post_dispatch($object, $server, $request ) {

                $this->console("root","Dispatch Start");
           // echo "<br> OBJECT:<pre>".print_r($object,true)."</pre>";
                $object = apply_filters("wpheadless/dispatch",$object,$this);
                $this->console("root","Dispatch End");
                if ( count($this->debug_buffer)) {
                    $object->data["debug"]=$this->debug_buffer;
                }
                return $object;

        }


    } // END class WP_Plugin_Template
} // END if(!class_exists('WP_Plugin_Template'))



add_action("wpheadless/request/type/action","wpheadless_archive_show_type_info");
function wpheadless_archive_show_type_info($type) {
   // echo "<br> SET REQUEST TYPE['".$type."']";
}

add_action("wpheadless/request/type/action","wpheadless_archive_set_request_fields");
function wpheadless_archive_set_request_fields($type) {
        if ( $type == "archive" ) {
           $_GET["_fields"]=array("slug","content","excerpt","title","categories","featured_media","featured_source");
        }
}

add_filter("wpheadless/console/string","wpheadless_console_string",50,3);
function wpheadless_console_string($output,$module_name,$string) {

    if ( $module_name == "boot" ) {
        $output="### [".$output."] ###";
    }
    else if ( $module_name == "modules") {
        $output="--> ".$output;
    }
    else {
        $piece = substr($string,0,strpos($string," ",0));
        if ( $piece == strtoupper($piece)) {
            $output="    @ ".$output;
        }
        else {
            $output="    @ -> ".$output;

        }
    }
    return $output;
}


if (class_exists('WP_Headless')) {
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_Headless', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_Headless', 'deactivate'));

    // instantiate the plugin class
    $wp_plugin_template = new WP_Headless();
}
