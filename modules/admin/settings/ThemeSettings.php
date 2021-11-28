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
            $this->section =  get_array_value($_GET, "section", array_key_first($this->getMenuSections()) );
        }
        return $this->section;
    }

    private function console(string $string) {

            if ( $this->debug ) {
                echo "<br> DEBUG[ThemeSettings]: ".$string;
            }

    }

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

        //Secció seleccionada, o primera secció seleccionada
        $section = get_array_value($_GET, "section", array_key_first($sections) );


        // Carregar CSS
        self::render_page_css();

        //Carregar Menu ThemeSettings - Sections
        
        ?>
        <ul class="nav-section-wrapper">
        <?php
        $callback="";
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

        // Carregar el contingut de la secció seleccionada
        $sections=array();
        if ($callback) {
            if(function_exists($callback)) {
                $sections = call_user_func($callback);
            }
            else {
                echo "<br> Callback NotFound [".$callback."]";
            }
        }

        // Action abans de mostrar els inputs
        do_action("wpheadless/themesettings/tab/content/before", $section);

        // Carrega els inputs de la secció seleccionada
        $adminPanel->render_sections($section,$sections);

        // Action després de mostrar els inputs
        do_action("wpheadless/themesettings/tab/content/after", $section);

        // Filtrar css i javascript per a la secció seleccionada
        do_action("wpheadless/themesettings/css", $section);
        do_action("wpheadless/themesettings/js", $section);

        ?>
        <?php
    }
    public function render_page_css() {
        ?>
        <style type="text/css">
            :root{
                --wphl-ts-section-tab-color:#373d43;
                --wphl-ts-section-tab-select-text-color:#373d43;
                --wphl-ts-section-tab-select-text-active-color:#eee;
            }
            .nav-section-wrapper{display:flex;flex-direction:row;border-bottom:1px solid var(--wphl-ts-section-tab-select-text-color);margin:35px 0 25px 0}
            .nav-section-wrapper .nav-section {margin: 0px 5px;text-decoration: none;color:#bbb;border:1px solid transparent;outline:none;border-radius:4px 4px 0px 0px;position:relative;}
            .nav-section-wrapper .nav-section a{padding:10px 15px;color:var(--wphl-ts-section-tab-select-text-color);display:block;outline:none;text-decoration:none;box-shadow:none}
            .nav-section-wrapper .nav-section:focus{outline:none}
            .nav-section-wrapper .nav-section:hover, .nav-section-wrapper .nav-section.nav-section-active {border-color:var(--wphl-ts-section-tab-color) var(--wphl-ts-section-tab-color) transparent var(--wphl-ts-section-tab-color);color: var(--wphl-ts-section-tab-color);}
            .nav-section-wrapper .nav-section:hover a, .nav-section-wrapper .nav-section.nav-section-active a{color:var(--wphl-ts-section-tab-select-text-active-color);}
            .nav-section-wrapper .nav-section:after{content:"";position:absolute;left:0;right:0px;height:2px;background:transparent;}
            .nav-section-wrapper .nav-section:hover:after,.nav-section-wrapper .nav-section.nav-section-active:after{background-color:#1a1a1a}
            .wphl-settings-page hr{border-color:var(--wphl-ts-section-tab-color)}
            .wphl-form h2 hr{border-color:var(--wphl-ts-section-tab-color)}
        </style>
        <?php
    }
    public function getMenuSections() : array {
        return apply_filters("wpheadless/themesettings/tabs",array());
    }

}