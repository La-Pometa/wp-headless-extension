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




class WPHeadlessPolyLang extends WPHeadlessModule
{

    static bool $instance = false;
    private $current_language;

    public function init()
    {
               
        // Modificar el idioma de la REST API
       // add_action('rest_api_init',[$this, 'rest_init'], 0);
       $this->current_language="noset";
        // Abans d'afegir els filtres, comprovar que el plugin estigui actiu; solament després de "init"
        add_action("init",[$this,"_init_filters"]);
    
        add_action("wpheadless/request/start",[$this,"rest_init"]);

        add_filter("wpheadless/content/link",[$this,"_get_permalink"],100,2);


        add_filter("pre_get_posts",[$this,"_pre_get_posts"],100,2);

        add_filter("wp_unique_post_slug",[$this,"_wp_unique_post_slug"],100,6);

        add_filter("wpheadless/path/query/likename",[$this,"_likename_array"],100,2);
        // fix polylang language segmentation
        add_action( 'rest_api_init' , array( $this, 'polylang_json_api_init') );
       // add_action( 'rest_api_init' , array( $this, 'polylangroute' ) );

        add_action('load_textdomain',array($this,'debug_load_textdomain'),50,2);

            

    }

    function _likename_array($data,$slug) {

        $lang = get_array_value($_GET, "lang", false);
        $languages = pll_languages_list(array("hide_empty"=>false,"fields"=>"slug"));
         
         $data[]=$slug;
         foreach($languages as $lang_pos => $lang_slug) {
             if ( $lang == $lang_slug ) {
                $data[]=$slug."-".$lang_slug;
             }
         }

        return $data;
    }



    function _wp_unique_post_slug($slug,$post_id,$post_status,$post_type,$post_parent,$original_slug) {

        if ( parent::get_settings_allow_duplicate_slugs() ) {
            if ( $original_slug != $slug ) {
                $lang = pll_get_post_language($post_id);
                $slug = $original_slug."-".$lang;
            }
        }

        return $slug;
    }
    
    function _get_permalink($url,$post_id) {

        if ( function_exists("pll_languages_list")) {
			$languages = pll_languages_list(array("hide_empty"=>false,"fields"=>"slug"));
		   // echo "<br> Languages <pre>".print_r($languages,true)."</pre>";
			$langs = array();
			foreach($languages as $lang_pos => $lang_slug) {
					$langs[] ="/".$lang_slug."/";
			}
			$dx = 1; //sStrlen - last '/'
			foreach($langs as $lang_text) {
				if ( substr($url,0,strlen($lang_text)) == $lang_text) {
					$url = substr($url,strlen($lang_text)-$dx,(strlen($url)-strlen($lang_text))+$dx);
				}
			}
	    }
        return $url;
    }

    function _init_filters() {

        // Check if polylang is installed
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        if (!is_plugin_active('polylang/polylang.php')) {
            return;
        }

        add_action("wpheadless/content/init", [$this, "register_api_field"] , 2000);
    }

    public function rest_init()
    {


        // if ( !is_admin() ) {
        //         return;
        // }




        global $polylang;

        $cur_lang = false;

        $translate = get_array_value($_GET,"translate","");
        if ( $translate ) {
            $_GET["lang"]=$translate;
        }


      //  echo "<br> SERVER: <pre>".print_r($_SERVER,true)."</pre>";
        $polylang->curlang=get_array_value($_GET,"lang","nolang");
        // echo "<br> POLYLANG: <pre>".print_r($polylang,true)."</pre>";
        $this->current_language=$polylang->curlang;
        if (isset($_GET['lang'])) {
            $default = pll_default_language();
           // $langs = pll_languages_list();
            $langs = array("es","ca");
            $cur_lang = $_GET['lang'];

            if (!in_array($cur_lang, $langs)) {
                $cur_lang = $default;
            }
            $this->console("Language URL (isset): $cur_lang");
            
            $polylang->curlang = $polylang->model->get_language($cur_lang);
            $GLOBALS['text_direction'] = $polylang->curlang->is_rtl ? 'rtl' : 'ltr';
        }
        $this->console("Language URL: $cur_lang");

        
        $post_types = get_post_types(array('public' => true), 'names');
        $taxonomies = get_taxonomies(['show_in_rest' => true], 'names');

        $post_types = apply_filters("wpheadless/rest/post-types", $post_types);


        foreach ($post_types as $post_type) {
            if (pll_is_translated_post_type($post_type)) {
                $this->register_api_field($post_type);
            }
        }
        foreach ($taxonomies as $taxonomy) {
            if (pll_is_translated_taxonomy($taxonomy)) {
                $this->console("Bind Taxonomy: $taxonomy");
                $this->register_api_taxonomy($taxonomy);
            }
        }
    }
    public function _pre_get_posts($query) {

        if ( is_admin()) {

                return $query;
        }

        $query->set("lang",$this->current_language);


        return $query;

    }
    public function register_api_field($post_type)
    {

        $this->console("Binding 'rest_" . $post_type . "_query' ");
        add_filter('rest_' . $post_type . '_query', [$this, '_change_rest_lang'], 10, 2);

        $this->console("Loading CPT [" . $post_type . "][current_lang]");
        register_rest_field(
            $post_type,
            "current_lang",
            array(
                "get_callback" => [$this, "get_current_lang"],
                "schema" => null,
            )
        );

        $this->console("Loading CPT [" . $post_type . "][get_translations]");
        register_rest_field(
            $post_type,
            "translation",
            array(
                "get_callback" => [$this, "get_translations"],
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
                // 'id' => $translation,
                'slug' => $post->post_name,
                'title' => get_the_title($translation),
                'url' => get_permalink($translation),
            );
            array_push($carry, $item);

            return apply_filters("wpheadless/rest/translations",$carry);
        }, array());
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
        if (!isset($_GET["lang"])) {
            $_GET["lang"] = $lang;
        }
        return $args;
    }



        public function polylang_json_api_init(){
            global $polylang;
            $default = pll_default_language();
            $langs = pll_languages_list();
            $cur_lang = $_GET['lang'];
            if (!in_array($cur_lang, $langs)) {
                $cur_lang = $default;
            }
            $this->console("Language URL (polylang_json_api_init): $cur_lang");

            $polylang->curlang = $polylang->model->get_language($cur_lang);
            $GLOBALS['text_direction'] = $polylang->curlang->is_rtl ? 'rtl' : 'ltr';
        }
    
        public function polylang_json_api_languages(){
            return pll_languages_list();
        }


        function debug_load_textdomain( $domain , $mofile  ){
            $this->console("Trying ".$domain." at ".$mofile."");
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


    $this->console("Language URL: $lang");

    if ($translate && !$lang) {
        $_GET["lang"] = $translate;
    }

    $lang = get_array_value($vars, "lang", pll_default_language());

     echo "LANG!!!! [" . $lang . "] [" . get_array_value($vars, "lang", "novalue") . "]";
    return $vars;
}
