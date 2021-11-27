<?php

/*-

    Plugin ID: whpi-polylang-admin
    Plugin Name: WPHeadless-Integration Polylang - Administrator
    Plugin URI: https://www.lapometa.com/headless
    Description: Integració de AdminPanel-ThemeSettings per a Multiidioma amb Polylang
    Version: 0.0.3
    Author: WPHeadless
    Text Domain: wpheadlessltd
    RequiresPHP: 7.4.2


-*/

define("WPHI_INTEGRATION_POLYLANGADMIN_ID","wphi-polylang-admin");


add_filter("wpheadless/admin/modules","wpheadless_themesettings_modules_vendor_polylang_admin");

function wpheadless_themesettings_modules_vendor_polylang_admin($modules) {

    //Afegir 'whpi-polylang-admin' a AdminModules
    $modules[WPHI_INTEGRATION_POLYLANGADMIN_ID]=array(
        "file"=>__FILE__,
        "class"=>"WPHeadlessPolylangAdmin"
    );

    return $modules;
}

add_filter("wpheadless/integrations/info","wpheadless_themesettings_modules_vendor_polylang_admin_info");
function wpheadless_themesettings_modules_vendor_polylang_admin_info($modules) {

    $info = get_plugin_data(__FILE__);

    $modules[WPHI_INTEGRATION_POLYLANGADMIN_ID] = array(
        "id"=>WPHI_INTEGRATION_POLYLANGADMIN_ID,
        "title"=>get_array_value($info,"Name",false),
        "version"=>get_array_value($info,"Version","0.0.1"),
        "description"=>get_array_value($info,"Description",""),
        "loaded"=>false,
    );
   
    return $modules;

}


class WPHeadlessPolylangAdmin extends Integration {

    function __construct() {

        $this->_id="wphi-polylang-admin";
        $this->_version="0.0.1";
        $this->_loaded = true;
        $this->_title = "Polylang - Admin";

    }

    public function init()
    {
        // Check if polylang is installed
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        if (!is_plugin_active('polylang/polylang.php')) {return;}

        // Afegir Wrapper Idioma per settings 
        add_filter("wpheadless/settings/input/html",array($this,"_input_wrapper"),2500,2);
        add_filter("wpheadless/settings/input/atts",array($this,"_input_atts_wrapper"),2500,2);
        add_action("wpheadless/settings/input/start",array($this,"_input_start_wrapper"),0);
        add_action("wpheadless/settings/input/end",array($this,"_input_end_wrapper"),2500);


        // Modificar sections per a multiidioma 
        add_filter("wpheadless/settings/tab/sections",array($this,"_settings_section"),2500);

    }
    function _input_start_wrapper($args=array()) {
        $lang = get_array_value(get_array_value($args,"field_data",array()),"lang",false);
        $html="";
        if ( $lang ) {
            $html ='<div class="lang-wrapper" rel="'.$lang.'">';
        }
        echo $html;
    }
    function _input_end_wrapper($args=array()) {
        $lang = get_array_value(get_array_value($args,"field_data",array()),"lang",false);
        $html="";
        if ( $lang ) {
            $html ='</div>';
        }
        echo $html;
    }
    function _input_wrapper($html,$args=array()) {
        return $html;
    }


    function _settings_section($sections) {
        $langs = pll_languages_list();
        $sec = array();
        foreach($langs as $lang_pos => $lang_id) {
            foreach($sections as $section_id => $section_data) {
                $fields = get_array_value($section_data,"fields",array());
                if (!isset($sec[$section_id])){
                    $sec[$section_id]=$section_data;
                    $sec[$section_id]["fields"]=array();
                }
                

                foreach($fields as $field_id => $field_data) {
                    $field_lang = $field_id.":".$lang_id;
                    $field_data["lang"]=$lang_id;
                    if (!isset($sec[$section_id]["fields"][$field_lang])){$sec[$section_id]["fields"][$field_lang]=array();}
                    $sec[$section_id]["fields"][$field_lang]=$field_data;
                }
            }
        }
        $sections = $sec;

        return $sections;
    }

    function _input_atts_wrapper($atts,$args=array()) {

        $lang = get_array_value(get_array_value($args,"field_data",array()),"lang",false);
        if ( $lang ) {
            if (!get_array_value($atts,"class","")){$atts["class"]="";}
            $atts["class"].=(get_array_value($atts,"class","")?" ":"")."lang-".$lang;
            if ( $lang != pll_default_language()) {
               $atts["class"].=(get_array_value($atts,"class","")?" ":"")."hidden";
            }
        }

        return $atts;
    }
}