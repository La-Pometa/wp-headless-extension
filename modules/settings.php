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
        add_action("wpheadless/routes/new", array($this, "init_routes"));
        add_filter("wpheadless/request/type/filter",array($this,"_request_type"),20,2);

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
        foreach($option_names as $option_name) {
            $res = array_merge($res,array($option_name=>get_option($option_name)));
        }

        return $this->parse_options($res);
    }

    function parse_options(array $options) {

        $format = array(
            "menu" => [$this,"_value_menu"],
            "logo" => [$this,"_value_logo"],
            "cpt-page-id" => [$this,"_value_cptpageid"]
        );
        $format = apply_filters("wpheadless/settings/fields",$format);

        foreach($format as $format_name => $format_callback ) {
            add_filter("wpheadless/settings/field/".$format_name."/value",$format_callback);
        }

        $res = array();
        foreach($options as $name => $fields ) {
            if ( !is_array($fields)) {
                continue;
            }
            foreach($fields as $field_id => $field_value) {
                if (!isset($res[$name])){$res[$name]=array();}
                foreach($format as $format_name => $format_callback ) {
                    if ( substr($field_id,0,strlen($format_name))==$format_name) {
                        $field_value = apply_filters("wpheadless/settings/field/".$format_name."/value",$field_value); 
                    }
                }
                $res[$name][$field_id]= $field_value; 

            }
        }
        $options=$res;

        return $options;
    }

    function _value_menu($value) {
        return "menu:".$value;
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



