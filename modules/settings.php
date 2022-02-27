<?php


add_filter("wpheadless/modules/load", "wpheadless_settings_load_module");
function wpheadless_settings_load_module($modules)
{
    $modules["settings"] = "WPHeadlessSettings";
    return $modules;
}

class WPHeadlessSettings extends WPHeadlessModule
{

    var $url_web = "";
    var $field = ""; // Element que esta renderitzant en aquest moment

    public function __construct()
    {
        add_action("wpheadless/routes/new", array($this, "init_routes"));
        add_filter("wpheadless/request/type/filter",array($this,"_request_type"),20,2);

        


        add_filter("wpheadless/url/slug",array($this,"_filter_url_slug"));

    }
    function _request_type($type,$call) {

        if ( $call == "settings") {
            $type = "single";
        }

        return $type;
    }

    function init_routes()
    {


        $this->console("Loading Route [settings]");
        register_rest_route('wp/v2', '/settings/', array(
            'methods' => WP_REST_Server::READABLE,
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


        do_action("wpheadless/settings/load-settings-ret");

        $settings = get_option("wpheadless_settings");


        $res = array();
        $option_names = array(
            "wpheadless-general-settings",
            "wpheadless-theme-settings",
        );

        $option_names = apply_filters("wpheadless/settings/options",$option_names);

        //echo "<br> OPTIONS NAMES: <pre>".print_r($option_names,true)."</pre>";


        foreach($option_names as $option_name) {
            $res = array_merge($res,array($option_name=>get_option($option_name)));
        }

        return $this->parse_options($res);
    }

    function parse_options(array $options) {

        $format = array(
            "menu" => [$this,"_value_menu"],
            "logoarray" => [$this,"_value_logoarray"],
            "logo" => [$this,"_value_logo"],
            "cpt-page-id" => [$this,"_value_cptpageid"]
        );
        $format = apply_filters("wpheadless/settings/fields",$format);

        foreach($format as $format_name => $format_callback ) {
            add_filter("wpheadless/settings/field/".$format_name."/value",$format_callback);
            do_action("wpheadless/settings/field/".$format_name."/hooks",$this);
        }


        $res = array();

        foreach($options as $name => $fields ) {
            if ( !is_array($fields)) {
                continue;
            }
            foreach($fields as $field_id => $field_value) {

                $this->field = $field_id;
                $field_data = explode("-",$field_id);
                $field_lang = get_array_value($field_data,0,"es");
                $field_type = get_array_value($field_data,1,"text");
                $field_len  = strlen($field_lang) + strlen($field_type) + 2; //2: strlen("<lang>-<type>-");
                $field_key = substr($field_id,$field_len,strlen($field_id)-strlen($field_len));
                if ( $field_lang && $field_key ) {
                    foreach($format as $format_name => $format_callback ) {
                        $field_type = apply_filters("wpheadless/settings/field/type",substr($field_id,0,strlen($format_name)),$field_id);
                    
                        if ( $field_type==$format_name) {
                            $field_value = apply_filters("wpheadless/settings/field/".$format_name."/value",$field_value); 
                        }
                    }

                    if ( !isset($res[$field_lang])){$res[$field_lang]=array();}
                    if ( !isset($res[$field_lang][$field_key])) {$res[$field_lang][$field_key]=array();}

                    $res[$field_lang][$field_key] = $field_value;
                }
                $this->field = "";

            }
        }
        $options=array("settings"=>$res);

        $options = apply_filters("wpheadless/settings",$options);


        return $options;
    }

    function _value_menu($value) {

        $elements = array();
        $objects = wp_get_nav_menu_items($value);

        //Walker menus i submenus
        $elements = $this->_value_menu_walker($elements,$objects,0);

        //Walker neteja 
        $doClean = apply_filters("wpheadless/settings/field/menu/clean",true,array("field"=>$this->field));
        if ( $doClean ) {
             $elements = $this->_value_menu_walker_clean($elements);
        }
        return $elements;


    }
    function _value_menu_walker(array $elements,array $objects, $parent_id = 0) {

        $final_elements = array();

        foreach($objects as $pos => $object) {

            $field_parent = get_object_value($object, "menu_item_parent",false);
            if ( $field_parent == $parent_id ) {
                
                $field_id = get_object_value($object,"ID",false);

                $field_title = get_object_value($object,"post_title","post-title-".$field_id);
                if ( !$field_title ) {
                    $field_title = get_object_value($object,"title","no-title-".$field_id);
                }
                if ( !$field_title ) {
                    $field_title = get_object_value($object,"type_label","no-title-".$field_id);
                }
                $field_class = get_object_value($object, "classes",array());
                $field_link = get_object_value($object, "url","#");
                $field_link = apply_filters("wpheadless/url/slug",$field_link);


                // image

                $image_data = false;
                $image_id = get_post_meta($field_id, "_thumbnail_id",true);
                if ( $image_id ) {
                    $image_data =  wp_get_attachment_metadata($image_id);
                }
                
                
                $element = array(
                    "object"=>get_object_value($object,"object_id","custom"),
                    "obj"=>$object,
                    "title"=>$field_title,
                    "link"=>$field_link,
                    "class"=>$field_class,
                    "image"=>$image_data,
                    "children"=>$this->_value_menu_walker($elements,$objects,$field_id),
                );

                $element = apply_filters("wpheadless/settings/field/menu",$element);


                $final_elements[]=$element;
            }
        }
        return $final_elements;
    }

    function _filter_url_slug($url) {
        if ( !$this->url_web) {
            $this->url_web = site_url();
        }
        $url = str_replace($this->url_web,"",$url);

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


        // Codi idioma



        return $url;

    }

    function _value_menu_walker_clean($elements, $parent_id = 0) {

        $final_elements = array();
        foreach($elements as $element_id => $element_info ) {
            $children = get_array_value($element_info,"children",array());

            if (!count($children)) {
                unset($element_info["children"]);
            }
            else {
                $element_info["children"] = $this->_value_menu_walker_clean($children,$element_id);
            }

            $image = get_array_value($element_info,"image",false);

            if ( $image == false ) {
               unset($element_info["image"]);
            }
            unset($element_info["object"]);
            unset($element_info["obj"]);

            $final_elements[]=$element_info;
        }

        return $final_elements;

    }

    function _value_logoarray($value) {
        $values = array();
        $pos=0;
        foreach($value as $value_id) {
            $image = $value_id;
         //   $image = get_array_value($value_id,0,false);
            if ( $image ) {
                $pos++;
                $data = wp_get_attachment_metadata($image);
                $alt = get_array_value($data,"alt_text",false);
                $title = get_array_value($data,"title",false);
                $sizes = get_array_value($data,"sizes",array());
                $element = array();
                //$element["pos"]=$pos;
                $element["alt"]=$alt;
                $element["title"]=$title;
                $element["sizes"]=$sizes;
                $values[] = $element;
            }
        }
        return $values;
    }
    function _value_logo($value) {

        $image = get_array_value($value,0,false);
        if ( $image ) {
            $data = wp_get_attachment_metadata($image);
            $alt = get_array_value($data,"alt_text",false);
            $title = get_array_value($data,"title",false);
            $value = get_array_value(get_array_value($data,"sizes",array()),"full_webp",false);
            $value["alt"]=$alt;
            $value["title"]=$title;
            $value["sizes"]=get_array_value($data,"sizes",array());
        }
        return $value;
    }
    function _value_cptpageid($value) {
        $page_id = $value;
        if ( !$page_id ) {
            return $value;
        }
        $metas = get_post_meta($page_id);
        $meta = array();
        $items = array(
			"main" => array("name"=>"description","value"=>"","callback"=>[$this,"_value_cptpageid_main"]),
			"_yoast_wpseo_title" => array("name"=>"title","value"=>"","callback"=>[$this,"_value_cptpageid_title"]),
			"_thumbnail_id" => array("name"=>"og:image","value"=>"","callback"=>[$this,"_value_cptpageid_thumbnail_data"])
		);

        foreach($items as $meta_key => $meta_data ) {
			$name = get_array_value($meta_data,"name",false);
			$callback = get_array_value($meta_data,"callback",false);
			$value = get_array_value($metas,$meta_key,false);
			if ( $name ) {
				if ( is_array($value) and count($value) == 1) { $value = $value[0]; }
				if (  $value ) {$meta[$name]=$value;}
				if ( $callback ) {$meta = call_user_func($callback,$meta,$name,$value,$metas,$page_id);}
			}
		}

        return $meta;
    }
    function _value_cptpageid_main($meta,$name,$value,$metas,$page_id) {
        $meta["og:type"]="website";
        $meta["og:url"]="#";
        $meta["og:site_name"]=get_bloginfo("name");
        $meta["og:description"]=get_array_value(get_array_value($metas,"_yoast_wpseo_metadesc",array()),0,false);
        return $meta;
    }
    function _value_cptpageid_thumbnail_data($meta, $name, $value, $metas) {
        if ( !$value ) {return $meta;}
        unset($meta["og:image"]);
        $info = wp_get_attachment_image_src(intval($value),"full");
        $meta["og:image"]=get_array_value($info,0,false);
        $meta["og:image:secure_url"]=$meta["og:image"];
        $meta["og:image:width"]=get_array_value($info,1,false);
        $meta["og:image:height"]=get_array_value($info,2,false);
        $meta["og:image:alt"]=get_the_title(intval($value));
        return $meta;
    }
    
    function _value_cptpageid_title($meta,$name,$value,$metas,$page_id) {
        if ($value){$value = wpseo_replace_vars( $value, get_post($page_id) );}
        else{$value = get_the_title($page_id);}
        $meta["og:title"]=$value;
        $paged = get_array_value($_GET,"page",1);
        if ( $paged > 1 ) {$meta["og:title"].= " - ".sprintf(__("Pàgina %d","lapometaltd"), $paged );}
        return $meta;
    }


}



