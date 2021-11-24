<?php


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
        $output["rendered"] = strip_tags(do_shortcode($post->post_content));

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




add_filter("get_the_excerpt", "wpheadless_vendor_js_composer_filter_get_the_excerpt", 50, 2);

function wpheadless_vendor_js_composer_filter_get_the_excerpt($content, $post)
{


    if (!is_plugin_active('js_composer/js_composer.php')) {
        return;
    }

    WPBMap::addAllMappedShortcodes();

    $content =  do_shortcode($post->post_content);;

    return $content;
}
