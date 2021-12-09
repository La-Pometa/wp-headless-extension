<?php

/*-

    Plugin ID: whpi-wpbakery
    Plugin Name: WPHeadless-Integration WPBakery/Visual Composer
    Plugin URI: https://www.lapometa.com/headless
    Description: Integració WPBakery/Visual Composer amb API REST
    Version: 0.0.1
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


class WPHeadlessJSComposer extends WPHeadlessModules
{


    function _construct()
    {

        add_action("init", array($this, "init"));

        add_action("wpheadless/content/init", array($this, "register_api_field"));
    }

    function init()
    {
        add_action("wpheadless/content/init", array($this, "_content_init"));
    }


    function _content_init($cpt)
    {


        if (!is_plugin_active('js_composer/js_composer.php')) {
            return;
        }


        $this->console("Loading CPT [" . $cpt . "][content_render]");

        register_rest_field(
            $cpt,
            'content',
            array(
                'get_callback'    => array($this, "content_render"),
                'update_callback' => null,
                'schema'          => null,
            )
        );

        $this->console("Loading CPT [" . $cpt . "][content_render_excerpt]");

        register_rest_field(
            $cpt,
            'excerpt',
            array(
                'get_callback'    => array($this, "content_render_excerpt"),
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }



    function content_render_excerpt($object, $field_name, $request)
    {
        $output = array("rendered" => "", "protected" => false);


        $excerpt = get_post_field('post_excerpt', $object['id']);
        $post = get_post($object['id']);

        if (is_plugin_active('js_composer/js_composer.php') && preg_match('/vc_row/', $post->post_content)) {
            WPBMap::addAllMappedShortcodes();
        }


        $content =  do_shortcode($post->post_content);
        $content_st = strip_tags($content);
        $content = wp_trim_words($content_st,25);
        $content = str_replace(array("\n","\t"),array("",""),$content);
        
        $output["rendered"] = $content;

        return $output;
    }

    function content_render($object, $field_name, $request)
    {
        $post = get_post($object['id']);
        $output = array("rendered" => "", "protected" => false);

        global $post;
        $post = get_post($object['id']);

        if (is_plugin_active('js_composer/js_composer.php') && preg_match('/vc_row/', $post->post_content)) {
            WPBMap::addAllMappedShortcodes();
        }
        //$output["rendered"] =  apply_filters('the_content', $post->post_content);
        $output["rendered"] =  do_shortcode($post->post_content);

        return $output;
    }
}




