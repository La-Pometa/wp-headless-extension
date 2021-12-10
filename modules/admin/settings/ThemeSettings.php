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
    Required: Yes
    Type: Admin


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

    private bool $debug = true;
    private string $section = "";
    private array $sections = array();

    function __construct() {
        add_action("wpheadless/settings/tab/content",array($this,"render"));
        add_filter("wpheadless/settings/sections",array($this,"get_sections"),20,2);
        add_action("wpheadless/settings/do_settings_sections",array($this,"do_settings_sections"));
        add_action("wpheadless/settings/do_settings_sections/before", array($this,"settings_section_start"));
        add_action("wpheadless/settings/do_settings_sections/after", array($this,"settings_section_end"));

        add_filter("wpheadless/settings/input/html",array($this,"settings_section_input"),20,2);
        
    }


    function get_sections($sections, WPHeadlessAdminPanel $adminPanel) {

        if ( $adminPanel->get_tab() != "themesettings" ) {
            return $sections;
        }

        $sections = apply_filters("wpheadless/themesettings/tab/".$this->get_section()."/sections",$sections);
        return $sections;
    }
    function get_section() {
        if ( !$this->section) {
            $default = array_key_first($this->getMenuSections());

            if ( !$default) {
                $default="";
            }

            $this->section =  get_array_value($_GET, "section", $default );
        }
        return $this->section;
    }
    public function getMenuSections() : array {
        return apply_filters("wpheadless/themesettings/tabs",array());
    }
    private function console(string $string) {

            if ( $this->debug ) {
                echo "<br> DEBUG[ThemeSettings]: ".$string;
            }

    }

    public function do_settings_sections(WPHeadlessAdminPanel $adminPanel) {


        if ( $adminPanel->get_tab() != "themesettings" ) {
            return;
        }

        $sections = self::getMenuSections();
        $section = get_array_value($_GET, "section", array_key_first($sections) );


        // Action abans de mostrar els inputs
        do_action("wpheadless/themesettings/tab/content/before", $adminPanel);


        foreach($sections as $section_id => $section_data) {
            $active = "";
            if ($section_id == $section) { 
                $active="nav-section-active";
            }
            ?>
            <div class="nav-section-panel nav-section-<?php echo $section_id; ?> <?php echo $active; ?>">
            <?php

                do_action("wpheadless/themesettings/tab/content/before/section", $section_id);

                // Carrega els inputs de la secció seleccionada
                $this_sections = apply_filters("wpheadless/themesettings/tab/".$section_id."/sections",array(),$adminPanel);
                $adminPanel->render_sections($section_id,$this_sections);
                do_settings_sections($section_id);
                // Filtrar css i javascript per a la secció seleccionada
                do_action("wpheadless/themesettings/css", $section_id);
                do_action("wpheadless/themesettings/js", $section_id);

                do_action("wpheadless/themesettings/tab/content/after/section", $section_id);



            ?>

            </div>
            <?php
        }


            // Action després de mostrar els inputs
            do_action("wpheadless/themesettings/tab/content/after", $adminPanel);


            ?>
         </div>
        <?php


    }

    function settings_section_start() {  ?><div class="nav-sections-wrapper"><?php }
    function settings_section_end() {  ?></div><?php }
    public function render(WPHeadlessAdminPanel $adminPanel)
    {

        if ( !$adminPanel->is_tab("themesettings")) {
            return;
        }

        echo "<h1>".__("Theme Settings", "wpheadlessltd")."</h1>";
        echo "<p>".__("Personalitza les opcions del tema per a WPHeadless Settings.", "wpheadlessltd")."</p>";


        $opt = "wp_headless_settings";
        $tab_id = "themesettings";

        //Filtra les seccions dins de ThemeSettings
        $sections = self::getMenuSections();

        //Secció Actual
        $section = $this->get_section();


        if ( !$section ) {
            // No hi ha cap secció definida+
           echo "<p><strong>".__("No hi ha configuració del tema definida. Crear el archivo settings.php al teu tema","wpheadlessltd").'</strong></p>';
           return ;
        }



        // Carregar CSS
        self::render_page_css();


        // Carregar JS
        self::render_page_js();

        //Carregar Menu ThemeSettings - Sections
        
        ?>
        <ul class="nav-section-wrapper">
        <?php
        $callback="";
        foreach($sections as $section_id => $section_data) {

                $selected = "";
               // $link = esc_attr(admin_url('options-general.php?page=' . $opt . '&sub=' . $tab_id . '&section='.$section_id));
                $link = '#panel-'.$section_id;
                $title = get_array_value($section_data, "title", "NoTitle:" . $section_id);
                if ($section_id == $section) {
                    $selected = "nav-section-active";
                    $callback = get_array_value($section_data, "callback", false);
                }
                ?>
                <li class="nav-section nav-section-<?php echo $section_id; ?> <?php echo $selected; ?>">
                    <a href="<?php echo $link; ?>" id="<?php echo $section_id; ?>"><?php echo $title; ?></a>
                </li>
                <?php
        }
        ?>
        </ul>
        <?php do_action("wpheadless/themesettings/after/menu"); ?>
        <?php

    }

    public function render_page_js() {
        ?>
            <script type="text/javascript">
                function WPHeadlessThemeSettingsBindSections() {
                    jQuery("a[href^='#panel-']").click(function(e) {
                        panel = jQuery(this).attr("href").replace("#panel-","");
                        console.log("Click Panel:"+panel);
                        jQuery(".nav-section-panel").removeClass("nav-section-active");
                        jQuery(".nav-section-panel.nav-section-"+panel).addClass("nav-section-active");
                        jQuery(".nav-section").removeClass("nav-section-active");
                        jQuery(".nav-section.nav-section-"+panel).addClass("nav-section-active");

                        e.preventDefault();
                    });
                }
                jQuery(document).ready(function() {
                    WPHeadlessThemeSettingsBindSections();
                });
            </script>
        <?php
    }
    public function render_page_css() {
        ?>
        <style type="text/css">
            :root{
                --wphl-ts-section-dark-tab-color:#373d43;
                --wphl-ts-section-dark-tab-select-text-color:#373d43;
                --wphl-ts-section-dark-tab-select-after:#fff;
                --wphl-ts-section-dark-tab-select-text-active-color:#eee;
                --wphl-ts-section-light-tab-color:#373d43;
                --wphl-ts-section-light-tab-select-text-color:#373d43;
                --wphl-ts-section-light-tab-select-text-active-color:#373d43;
                --wphl-ts-section-light-tab-select-after:#fff;

            }
            .nav-section-wrapper{display:flex;flex-direction:row;border-bottom:1px solid var(--wphl-ts-section-light-tab-select-text-color);margin:35px 0 25px 0}
            .nav-section-wrapper .nav-section {margin: 0px 5px;text-decoration: none;color:#bbb;border:1px solid transparent;border-bottom:none;outline:none;border-radius:4px 4px 0px 0px;position:relative;}
            .nav-section-wrapper .nav-section a{padding:10px 15px;display:block;outline:none;text-decoration:none;box-shadow:none}
            .nav-section-wrapper .nav-section:focus{outline:none}
            .nav-section-wrapper .nav-section:hover, .nav-section-wrapper .nav-section.nav-section-active {border-color:var(--wphl-ts-section-light-tab-color) var(--wphl-ts-section-light-tab-color) transparent var(--wphl-ts-section-light-tab-color);color: var(--wphl-ts-section-light-tab-color);}
            .nav-section-wrapper .nav-section:hover a, .nav-section-wrapper .nav-section.nav-section-active a{color:var(--wphl-ts-section-light-tab-select-text-active-color);}
            .nav-section-wrapper .nav-section:after{content:"";position:absolute;left:0;right:0px;height:2px;background:transparent;}
            .nav-section-wrapper .nav-section:hover:after,.nav-section-wrapper .nav-section.nav-section-active:after{background-color:var(--wphl-ts-section-light-tab-select-after)}
            .nav-sections-wrapper .nav-section-panel{display:none;}
            .nav-sections-wrapper .nav-section-panel.nav-section-active{display:block;}
            .nav-sections-wrapper .nav-section-panel hr:first-child {display: none;}
            html[data-theme="dark"] .nav-section-wrapper .nav-section a{color:var(--wphl-ts-section-dark-tab-select-text-color);}
            html[data-theme="dark"] .wphl-settings-page hr{border-color:var(--wphl-ts-section-dark-tab-color)}
            html[data-theme="dark"] .nav-section-wrapper .nav-section{border-color:var(--wphl-ts-section-dark-tab-color)}
            html[data-theme="dark"] .wphl-form h2 hr{border-color:var(--wphl-ts-section-dark-tab-color)}
            html[data-theme="dark"] .nav-section-wrapper{border-color: var(--wphl-ts-section-dark-tab-select-text-color);}
            html[data-theme="dark"] .nav-section-wrapper .nav-section:hover,html[data-theme="dark"] .nav-section-wrapper .nav-section.nav-section-active {border-color:var(--wphl-ts-section-dark-tab-color) var(--wphl-ts-section-dark-tab-color) transparent var(--wphl-ts-section-dark-tab-color);color: var(--wphl-ts-section-dark-tab-color);}
            html[data-theme="dark"] .nav-section-wrapper .nav-section:hover a,html[data-theme="dark"] .nav-section-wrapper .nav-section.nav-section-active a{color:var(--wphl-ts-section-dark-tab-select-text-active-color);}
            html[data-theme="dark"] .nav-section-wrapper .nav-section:hover:after,html[data-theme="dark"] .nav-section-wrapper .nav-section.nav-section-active:after{background-color:var(--uip-body-background)}

       </style>
        <?php
    }


    function settings_section_input($html,$args) {

        $type = get_array_value($args,"type","text");
        $id = get_array_value($args,"id",false);

        if ( !$type ) {
            return $html;
        }

        if ( !$id ) {
            return $html;
        }

        $value = get_array_value($args,"value","");
        $class = get_array_value($args,"class","");


        //echo "<br> TYPE[".$type."] <br> <pre>".print_r($args,true)."</pre>";

        switch($type) {
            case "text":
            case "hidden":
            case "number":
            case "date":


                $html = '<input type="'.$type.'" name="'.$id.'" class="'.$class.'" value="'.$value.'">';

            default:
        }

        return $html;

    }


}