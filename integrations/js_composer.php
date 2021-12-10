<?php

/*-

    Plugin ID: whpi-wpbakery
    Plugin Name: WPHeadless-Integration WPBakery/Visual Composer
    Plugin URI: https://www.lapometa.com/headless
    Description: Integració WPBakery/Visual Composer amb API REST
    Version: 0.0.2
    Author: WPHeadless
    Text Domain: wpheadlessltd
    RequiresPHP: 7.4.2


-*/

define("WPHI_INTEGRATION_WPBAKERY_ID","wphi-polylang");

add_filter("wpheadless/integrations/info","wpheadless_rest_modules_vendor_wpbakery_admin_info");
function wpheadless_rest_modules_vendor_wpbakery_admin_info($modules) {

    $info = get_plugin_data(__FILE__);

    $modules[WPHI_INTEGRATION_WPBAKERY_ID] = array(
        "id"=>WPHI_INTEGRATION_WPBAKERY_ID,
        "title"=>get_array_value($info,"Name",false),
        "version"=>get_array_value($info,"Version","0.0.1"),
        "description"=>get_array_value($info,"Description",""),
        "type"=>"rest",
        "loaded"=>false,
    );
   
    return $modules;

}



add_filter("wpheadless/modules/load", "wpheadless_jscomposer_load_module");
function wpheadless_jscomposer_load_module($modules)
{
    $modules["js_composer"] = "WPHeadlessJSComposer";
    return $modules;
}


class WPHeadlessJSComposer extends WPHeadlessModule
{

    //Sobreescriure funció init amb els filtres per a la integració
    function init()
    {

        if (!is_plugin_active('js_composer/js_composer.php')) {
            return;
        }

        add_action("wpheadless/content/init", array($this, "_content_init"));
        $this->console("Register Action 'wpheadless/content/init::_content_init'");

        add_action("wpheadless/content", array($this, "_content_render"),20,2);
        $this->console("Register Action 'wpheadless/content::_content_render'");
        
        // add_action("wpheadless/content/excerpt", array($this, "_content_render_excerpt"),20,2);
        // $this->console("Register Action 'wpheadless/content/excerpt::_content_render_excerpt'");


        $this->engine = false;
    }

    function _content_init($cpt)
    {
    }

    private function _content_engine($post_content) {
        if ( $this->engine ) {
            return;
        }
        if (is_plugin_active('js_composer/js_composer.php') && preg_match('/vc_row/', $post_content)) {
            WPBMap::addAllMappedShortcodes();
            $this->engine=true;
        }
    }



    // function _content_render_excerpt($post_content , $object)
    // {
    //     //strip_shortcodes nomes elimina els shortcodes registrats
    //     $this->_content_engine($post_content);
    //     return $post_content;
    // }

    function _content_render($post_content,$object)
    {
        $this->_content_engine($post_content);
        return $post_content;
    }
}




