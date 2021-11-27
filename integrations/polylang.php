<?php
require_once "Integration.php";

class WPHeadlessPolyLang extends Integration
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


/*-

Filtres per a Administrador 

-*/



add_filter("wpheadless/admin/modules","wpheadless_themesettings_modules_vendor_polylang_admin");

function wpheadless_themesettings_modules_vendor_polylang_admin($modules) {

    $modules["polylang"]="WPHeadlessPolylangAdmin";
    return $modules;
}


class WPHeadlessPolylangAdmin extends Integration {

    function __construct() {

    }

    public function init()
    {
        // Check if polylang is installed
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        if (!is_plugin_active('polylang/polylang.php')) {
            return;
        }

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