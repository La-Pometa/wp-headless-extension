<?php




/*-

    Plugin ID: whpi-themesettings
    Plugin Name: WPHeadless-ThemeSettings
    Plugin URI: https://www.lapometa.com/headless
    Description: Configuració peronslitzada per a WPHeadless
    Version: 0.0.1
    Author: WPHeadless
    Text Domain: wpheadlessltd
    RequiresPHP: 7.4.2


-*/

define("WPHI_INTEGRATION_THEMESETTINGS_ID","wphi-themesettings");

add_filter("wpheadless/integrations/info","wpheadless_rest_modules_vendor_themesettings_admin_info");
function wpheadless_rest_modules_vendor_themesettings_admin_info($modules) {

    $info = get_plugin_data(__FILE__);

    $modules[WPHI_INTEGRATION_THEMESETTINGS_ID] = array(
        "id"=>WPHI_INTEGRATION_THEMESETTINGS_ID,
        "title"=>get_array_value($info,"Name",false),
        "version"=>get_array_value($info,"Version","0.0.1"),
        "description"=>get_array_value($info,"Description",""),
        "type"=>"admin",
        "required"=>true,
        "loaded"=>false,
    );
   
    return $modules;

}




class ThemeSettings
{

    private $settings_option_name="wpheadless_themesettings";
    private $settings_options=false;

    public function render(WPHeadlessAdminPanel $adminPanel)
    {


        echo "<h1>".__("Theme Settings", "wpheadlessltd")."</h1>";
        echo "<p>".__("Personalitza les opcions del tema per a WPHeadless Settings.", "wpheadlessltd")."</p>";
        //$adminPanel->render_sections("themesettings", self::getSections());
        $opt = "wp_headless_settings";
        $tab_id = "themesettings";

        $section = get_array_value($_GET, "section", false);


        self::render_page_css();


        ?>
        <ul class="nav-section-wrapper">
        <?php
        $callback="";
        $sections = self::getMenuSections();
        foreach($sections as $section_id => $section_data) {

                $selected = "";
                $link = esc_attr(admin_url('options-general.php?page=' . $opt . '&sub=' . $tab_id . '&section='.$section_id));
                $title = get_array_value($section_data, "title", "NoTitle:" . $section_id);
                if ($section_id == $section) {
                    $selected = "nav-section-active";
                    $callback = get_array_value($section_data, "callback", false);
                }
                ?>
                <li class="nav-section <?php echo $selected; ?>">
                    <a href="<?php echo $link; ?>" id="<?php echo $section_id; ?>"><?php echo $title; ?></a>
                </li>
                <?php
        }
        ?>
        </ul>

        <?php do_action("wpheadless/themesettings/after/menu"); ?>
        <?php

        $sections=array();
        if ($callback) {
            $sections = call_user_func($callback);
        }
        do_action("wpheadless/themesettings/tab/content/before", $section);
        self::render_sections($section,$sections);
        do_action("wpheadless/themesettings/tab/content/after", $section);
        do_action("wpheadless/themesettings/css", $section);
        do_action("wpheadless/themesettings/js", $section);
        ?>
        <?php
    }
    public function render_page_css() {
        ?>
        <style type="text/css">
            :root{
                --wphl-ts-section-tab-color:#cccccc;
                --wphl-ts-section-tab-select-text-color:#eee
            }
            .nav-section-wrapper{display:flex;flex-direction:row;border-bottom:1px solid var(--wphl-ts-section-tab-color)}
            .nav-section-wrapper .nav-section {margin: 0px 5px;text-decoration: none;color:#bbb;border:1px solid transparent;outline:none;border-radius:4px 4px 0px 0px;position:relative;}
            .nav-section-wrapper .nav-section a{padding:10px 15px;color:var(--wphl-ts-section-tab-select-text-color);display:block;outline:none;text-decoration:none;box-shadow:none}
            .nav-section-wrapper .nav-section:focus{outline:none}
            .nav-section-wrapper .nav-section:hover, .nav-section-wrapper .nav-section.nav-section-active {border-color:var(--wphl-ts-section-tab-color) var(--wphl-ts-section-tab-color) transparent var(--wphl-ts-section-tab-color);color: var(--wphl-ts-section-tab-color);}
            .nav-section-wrapper .nav-section:after{content:"";position:absolute;left:0;right:0px;height:2px;background:transparent;}
            .nav-section-wrapper .nav-section:hover:after,.nav-section-wrapper .nav-section.nav-section-active:after{background-color:#1a1a1a}
        </style>
        <?php
    }
    public function getMenuSections() : array {
        $tabs = apply_filters("wpheadless/themesettings/tabs",array());
        return $tabs;
    }
    public function getSections(): array
    {
        $sections = array();
        return $sections;
    }
    function get_options()
    {
        if (!$this->settings_options) {
            $this->settings_options = get_option($this->settings_option_name);
        }
        return $this->settings_options;
    }

    function get_option($var_name = "", $default = false)
    {
        $options = $this->get_options();
        if ($var_name) {
            return get_array_value($options, $var_name, $default);
        }
    }
    function input_sanitize($input)
    {
        return $input;
    }
    function input($args = array())
    {
        $input_id = get_array_value($args, "id", false);
        $input_class = get_array_value($args, "class", false);
        if (!$input_id) {
            return "";
        }

        if (is_array($input_class)) {
            $input_class = implode(" ", $input_class);
        }

        $class_attr = "";
        if ($input_class) {
            $class_attr = ' class="' . $input_class . '"';
        }

        $args["value"] = $this->get_option($input_id, "");

        $html = '<input type="text" value="" ' . $class_attr . '>';

        $html = apply_filters("wpheadless/settings/input/html", $html, $args);


        return $html;
    }

    public function render_sections($page,$sections) {


            $option_name = $this->settings_option_name;
            $page= "whpeadless-themesettings-section-".$page;
    
            register_setting(
                $option_name, // option_group
                $option_name, // option_name
                function($input) {
                    return $input;
                } // sanitize_callback
            );
    
    
            /*- Habilitar Multidioma : Polylang (TODO: WPML) -*/
            $sections = apply_filters("wpheadless/settings/tab/sections",$sections);
    
            
            foreach ($sections as $section_id => $section_data) {
    
                $section_title = get_array_value($section_data, "title", "NoTitle[" . $section_id . "]");
    
                add_settings_section(
                    $section_id, // id
                    '<hr><br>' . $section_title, // title
                    function () use ($section_id, $section_data) {
                        $section_description = get_array_value($section_data, "description", "");
                        if ($section_description) {
                            echo "<p>" . $section_description . "</p>";
                        }
                        $section_html = get_array_value($section_data, "html", "");
                        if ($section_html) {
                            echo $section_html;
                        }
                    }, // callback
                    $page // page
                );
    
                $fields = get_array_value($section_data, "fields", array());
                foreach ($fields as $field_id => $field_data) {
                    $field_title = get_array_value($field_data, "title", "NoTitle[" . $field_id . "]");
                    $field_description = get_array_value($field_data, "title", "NoTitle[" . $field_id . "]");
    
                    add_settings_field(
                        $field_id, // id
                        $field_title, // title
                        function () use ($field_id, $field_data) {
                            $field_description = get_array_value($field_data, "description", "");
                            $args = array(
                                "id" => $field_id,
                                "field_data"=>$field_data,
                                "type" => get_array_value($field_data, "type", "text"),
                                "class" => get_array_value($field_data, "class", ""),
                            );
    
                            do_action("wpheadless/settings/input/start",$args);
    
                            echo self::input($args);
    
                            if ($field_description) {
                                echo "<p>" . $field_description . "</p>";
                            }
                            $field_html = get_array_value($field_data, "html", "");
                            if ($field_html) {
                                echo "<p>" . $field_html . "</p>";
                            }
                            do_action("wpheadless/settings/input/end",$args);
    
    
                        }, // callback
                        $page, // page
                        $section_id, // section
                        apply_filters("wpheadless/settings/input/atts",array(),array("field_data"=>$field_data))
                    );
    
                }
            }
    
            ?>
            <form method="post"  class="wphl-form" action="options.php">
                <?php
                settings_fields($option_name);
                do_settings_sections($page);
                submit_button();
                ?>
            </form>

            <?php
    

    }

}