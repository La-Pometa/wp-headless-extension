<?php


/*-

    Plugin ID: whpi-polylang
    Plugin Name: WPHeadless-Integration Polylang
    Plugin URI: https://www.lapometa.com/pometa-headless
    Description: Integració Polylang amb API REST per a Multiidioma
    Version: 0.0.1
    Author: WPHeadless
    Text Domain: wpheadlessltd
    RequiresPHP: 7.4.2


-*/

define("WPHI_INTEGRATION_POLYLANG_ID","wphi-polylang");

add_filter("wpheadless/integrations/info","wpheadless_rest_modules_vendor_polylang_admin_info");
function wpheadless_rest_modules_vendor_polylang_admin_info($modules) {

    $info = get_plugin_data(__FILE__);

    $modules[WPHI_INTEGRATION_POLYLANG_ID] = array(
        "id"=>WPHI_INTEGRATION_POLYLANG_ID,
        "title"=>get_array_value($info,"Name",false),
        "version"=>get_array_value($info,"Version","0.0.1"),
        "description"=>get_array_value($info,"Description",""),
        "type"=>"rest",
        "loaded"=>false,
    );
   
    return $modules;

}


add_filter("wpheadless/modules/load", "wpheadless_polylang_load_module");
function wpheadless_polylang_load_module($modules)
{
    $modules["polylang"] = "WPHeadlessPolyLang";
    return $modules;
}




class WPHeadlessPolyLang extends WPHeadlessModules
{

    static bool $instance = false;

    public function init()
    {

        // Check if polylang is installed
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        
        if (!is_plugin_active('polylang/polylang.php')) {
            return;
        }

        add_action('rest_api_init', array($this, 'rest_init'), 0);
        add_action('rest_api_init', array($this, '_change_rest_lang_server'), 100, 3);

        add_action("wpheadless/content/init", array($this, "register_api_field"));
    }


    public function rest_init()
    {
        global $polylang;

        if (isset($_GET['lang'])) {
            $default = pll_default_language();
            $langs = pll_languages_list();

            $cur_lang = $_GET['lang'];

            if (!in_array($cur_lang, $langs)) {
                $cur_lang = $default;
            }

            $polylang->curlang = $polylang->model->get_language($cur_lang);
            $GLOBALS['text_direction'] = $polylang->curlang->is_rtl ? 'rtl' : 'ltr';
        }


        $post_types = get_post_types(array('public' => true), 'names');
        $taxonomies = get_taxonomies(['show_in_rest' => true], 'names');

        foreach ($post_types as $post_type) {
            if (pll_is_translated_post_type($post_type)) {
                $this->register_api_field($post_type);
            }
        }
        foreach ($taxonomies as $taxonomy) {
            if (pll_is_translated_taxonomy($taxonomy)) {
                $this->register_api_taxonomy($taxonomy);
            }
        }
    }

    public function register_api_field($post_type)
    {

        $post_type_rest = $post_type;
        $this->console("Binding 'rest_" . $post_type_rest . "_query' ");
        add_filter('rest_' . $post_type_rest . '_query', array($this, '_change_rest_lang'), 10, 2);

        $this->console("Loading CPT [" . $post_type . "][current_lang]");

        register_rest_field(
            $post_type,
            "current_lang",
            array(
                "get_callback" => array($this, "get_current_lang"),
                "schema" => null,
            )
        );

        $this->console("Loading CPT [" . $post_type . "][get_translations]");

        register_rest_field(
            $post_type,
            "translations",
            array(
                "get_callback" => array($this, "get_translations"),
                "schema" => null,
            )
        );
    }


    public function register_api_taxonomy($taxonomy)
    {


        $this->console("Loading TAXONOMY [" . $taxonomy . "][get_current_taxonomy_lang]");

        register_rest_field(
            $taxonomy,
            "current_lang",
            array(
                "get_callback" => array($this, "get_current_taxonomy_lang"),
                "schema" => null,
            )
        );
    }

    public function get_current_lang($object): string
    {
        return pll_get_post_language($object['id']);
    }


    public function get_current_taxonomy_lang($object): string
    {
        return pll_get_term_language($object['id']);
    }

    public function get_translations($object)
    {
        $translations = pll_get_post_translations($object['id']);

        return array_reduce($translations, function ($carry, $translation) {

            $post = get_post($translation);
            $item = array(
                'locale' => pll_get_post_language($translation),
                'id' => $translation,
                'slug' => $post->post_name,
                'title' => get_the_title($translation),
                'url' => get_permalink($translation),
            );
            array_push($carry, $item);

            return $carry;
        }, array());
    }

    function _change_rest_lang_server()
    {
        register_rest_field("type", "test", ['get_callback' => function ($params) {
            return $params['slug'];
        }]);

        add_filter('wpseo_frontend_presentation', function ($presentation, $context) {

            return $presentation;
        }, 100, 2);
    }

    function _change_rest_lang($args, $request)
    {

        $params = $request->get_params();
        $max = max((int)$request->get_param('custom_per_page'), 200);
        $lang = get_array_value($params, "lang", false);
        if (!$lang) {
            if (function_exists("pll_default_language")) {
                $lang = pll_default_language();
            }
        }
        if ($lang) {
            $args['lang'] = $lang;
        }
        if (!isset($_GET["translate"])) {
            $_GET["translate"] = $lang;
        }

        return $args;
    }
}

/*-

Filtres per a REST API 

-*/


add_filter('rest_query_vars', 'wpheadless_vendor_polylang_replace_lang');

function wpheadless_vendor_polylang_replace_lang($vars)
{
    $translate = get_array_value($vars, "translate", false);
    $lang = get_array_value($vars, "lang", false);

    if ($translate && !$lang) {
        $_GET["lang"] = $translate;
    }

    $lang = get_array_value($vars, "lang", pll_default_language());

    echo "LANG!!!! [" . $lang . "] [" . get_array_value($vars, "lang", "novalue") . "]";
    return $vars;
}
