<?php


add_filter("wpheadless/modules/load","wpheadless_settings_load_module");
function wpheadless_settings_load_module($modules) {
    $modules["settings"]="WPHeadlessSettings";
    return $modules;
}



class WPHeadlessSettings extends WPHeadlessModules {

        var $settings_options;
        var $settings_option_name;

		public function __construct()
		{
			parent::__construct();

            $this->settings_options=false;
            $this->settings_option_name="wpheadless_themeoptions";
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_menu', array($this, 'add_menu'));
            add_action("wpheadless/routes/new",array($this,"init_routes"));

        }

        function admin_init() {



		}
        function add_menu() {
            add_options_page(
                'Headless: Theme Options',
                'Headless: Theme Options',
                'manage_options',
                'wp_headless_themeoptions',
                array(&$this, 'settings_page')
            );

        }
        function _get_settings_menu($tabs=array()) {
            $tabs["info"]=array("title"=>__("Informació","wpheadlessltd"),"callback"=>array($this,"_get_settings_menu_info"));
            return apply_filters("wpheadless/settings/tabs",$tabs);
        }

        function _get_settings_menu_info() {
            echo "<p><strong>Configuració adaptada a la web</strong></p>";

            echo "<p>".__("Afegeix noves pestanyes i elimina aquesta amb el filtre <i>wpheadless/settings/tabs</i>.","wpheadlessltd")."</p>";
            
            
        }

        function settings_page() {

            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            $sub = get_array_value($_GET,"sub",apply_filters("wpheadless/settings/tabs/default","general"));

            
            
            $opt ="wp_headless_themeoptions";

            ?>
            <h1>Headless: Theme Options</h1>

            <?php do_action("wpheadless/settings/before/menu"); ?>
            <h2 class="nav-tab-wrapper">
            <?php
                $tabs = $this->_get_settings_menu();
                $callback="";
                foreach($tabs as $tab_id => $tab_info) {
                    $selected="";
                    $link = esc_attr( admin_url( 'options-general.php?page='.$opt.'&sub='.$tab_id ) );
                    $title = get_array_value($tab_info,"title","NoTitle:".$tab_id);
                    if ( $tab_id == $sub ) {
                        $this->sub = $sub;
                        $selected="nav-tab-active";
                        if ( !$callback) {$callback = get_array_value($tab_info,"callback",false);}
                    }
                    ?>
                    <a href="<?php echo $link; ?>" id="<?php echo $tab_id; ?>"  class="nav-tab <?php echo $selected; ?>"><?php echo $title; ?>
                    </a>
                    <?php
                }


            ?>
            </h2>
            <?php do_action("wpheadless/settings/after/menu",$this); ?>
            <?php

            if ( $callback ) {
                add_action("wpheadless/settings/tab/content",$callback);
            }
            do_action("wpheadless/settings/tab/content",$this);
            do_action("wpheadless/settings/css",$sub);
            do_action("wpheadless/settings/js",$sub);

        }
        function settings_get_options() {
            if ( !$this->settings_options ) {
                $this->settings_options=get_option($this->settings_option_name);
            }
            return $this->settings_options;
        }
        function settings_get_option($var_name="",$default=false) {
               $options =  $this->settings_get_options();
               if ( $var_name) {
                return get_array_value($options,$var_name,$default);
               }
        }
        function settings_input_sanitize($input) {
                $sanitary_values=$input;
                return $sanitary_values;
        }
        function settings_input($args=array()) {
            $input_id = get_array_value($args,"id",false);
            $input_class = get_array_value($args,"class",false);
            if ( !$input_id) {
                return "";
            }

            if ( is_array($input_class)) {
                $input_class=implode(" ",$input_class);
            }

            $class_attr="";
            if ( $input_class ) {
                $class_attr=' class="'.$input_class.'"';
            }

            $args["value"]=$this->settings_get_option($input_id,"");

            $html='<input type="text" value="" '.$class_attr.'>';

            $html = apply_filters("wpheadless/settings/input/html",$html,$args);



            return $html;
        }
        function settings_render($page,$sections=array()) {
          //  echo "<br> RENDER: <pre>".print_r($sections,true)."</pre>";
            $page = "wpheadless-page-".$page;


            register_setting(
                $this->settings_option_name, // option_group
                $this->settings_option_name, // option_name
                array( $this, 'settings_input_sanitize' ) // sanitize_callback
            );


            foreach($sections as $section_id => $section_data) {

                $section_title = get_array_value($section_data,"title","NoTitle[".$section_id."]");

                add_settings_section(
                    $section_id, // id
                    '<hr><br>'.$section_title, // title
                    function() use ( $section_id, $section_data) {
                        $section_description = get_array_value($section_data,"description","");
                        if ( $section_description) { echo "<p>".$section_description."</p>";}
                        $section_html = get_array_value($section_data,"html","");
                        if ( $section_html) { echo $html;}
                    }, // callback
                    $page // page
                );

                $fields = get_array_value($section_data,"fields",array());
                foreach($fields as $field_id => $field_data) {
                    $field_title = get_array_value($field_data,"title","NoTitle[".$field_id."]");
                    $field_description = get_array_value($field_data,"title","NoTitle[".$field_id."]");

                    add_settings_field(
                        $field_id, // id
                        $field_title, // title
                        function() use ($field_id,$field_data) {
                            $field_description = get_array_value($field_data,"description","");
                            $args = array(
                                "id"=>$field_id,
                                "type"=>get_array_value($field_data,"type","text"),
                                "class"=>get_array_value($field_data,"class",""),
                            );
                            echo $this->settings_input($args);

                            if ( $field_description) { echo "<p>".$field_description."</p>";}
                            $field_html = get_array_value($field_data,"html","");
                            if ( $field_html) { echo "<p>".$field_html."</p>";}


                        }, // callback
                        $page, // page
                        $section_id // section
                    );
            
                }
            }

            ?>
            <form method="post" action="options.php">
            <?php
                settings_fields( $this->settings_option_name );
                do_settings_sections( $page );
                submit_button();
            ?>
            </form>
            <?php


        }
		function init_routes() {


            $this->console("Loading Route [settings]");
			register_rest_route( 'wp/v2', '/settings/', array(
		        'methods' => 'GET',
		        'callback' => array($this,"get_settings_response"),
                'permission_callback' => '__return_true',
		        'args' => array(
		            'slug' => array (
		                'required' => false
		            )
		        )
		    ) );
		}


        function get_settings_response(WP_REST_Request $request ) {
            $_req = array();
            $_req["settings"]=array();
            $_req["settings"]["ca"]=array();
            $_req["settings"]["es"]=array();

            $_req["settings"]["ca"]["logo"]=array();
            $_req["settings"]["ca"]["logo"]["alt"]="Logo LaPometa Agència";
            $_req["settings"]["ca"]["logo"]["title"]="LaPometa Agència";
            $_req["settings"]["ca"]["logo"]["width"]=100;
            $_req["settings"]["ca"]["logo"]["height"]=60;
            $_req["settings"]["ca"]["logo"]["dark"]=array("id"=>1,"src"=>"","width"=>60,"height"=>60);
            $_req["settings"]["ca"]["logo"]["light"]=array("id"=>1,"src"=>"","width"=>60,"height"=>60);
            $_req["settings"]["ca"]["logo"]["dark"]=array("id"=>1,"src"=>"","width"=>60,"height"=>60);
            $_req["settings"]["ca"]["logo"]["mini-light"]=array("id"=>1,"src"=>"","width"=>60,"height"=>60);
            $_req["settings"]["ca"]["logo"]["mini-dark"]=array("id"=>1,"src"=>"","width"=>60,"height"=>60);

            $_req["settings"]["es"]["logo"]=array();
            $_req["settings"]["es"]["logo"]["alt"]="Logo LaPometa Agencia";
            $_req["settings"]["es"]["logo"]["title"]="LaPometa Agencia";
            $_req["settings"]["es"]["logo"]["width"]=100;
            $_req["settings"]["es"]["logo"]["height"]=60;
            $_req["settings"]["es"]["logo"]["dark"]=array("id"=>1,"src"=>"","width"=>60,"height"=>60);
            $_req["settings"]["es"]["logo"]["light"]=array("id"=>1,"src"=>"","width"=>60,"height"=>60);
            $_req["settings"]["es"]["logo"]["light-mini"]=array("id"=>1,"src"=>"","width"=>60,"height"=>60);
            $_req["settings"]["es"]["logo"]["dark-mini"]=array("id"=>1,"src"=>"","width"=>60,"height"=>60);
            return $_req;
        }

}



