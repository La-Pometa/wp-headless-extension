<?php


add_filter("wpheadless/admin/modules", "wpheadless_adminpanel_load_module");

function wpheadless_adminpanel_load_module($modules)
{
    $modules["adminpanel"] = array(
        "class"=>"WPHeadlessAdminPanel",
        "file"=>__FILE__
    );

    return $modules;
}

require_once "settings/General.php";
require_once "settings/Integrations.php";
require_once "settings/ArchiveMetas.php";

class WPHeadlessAdminPanel extends WPHeadlessModules
{

    private bool $settings_options;
    private string $settings_option_name;
    private array $settings_modules;

    /**
     * Construct the plugin object
     */
    public function __construct()
    {
        // register actions
        $this->settings_options = false;
        $this->settings_option_name = "wpheadless_settings";
        add_action('admin_menu', array($this, 'add_menu'));

        //Carregar el mòduls de AdminPanel
        $this->settings_modules["general"] = new General();
        $this->settings_modules["integrations"] = new Integrations();
        $this->settings_modules["archive-meta-cpts"] = new ArchiveMetas();
        


    } // END public function __construct

    function add_menu()
    {
        add_options_page(
            'Headless Settings',
            'Headless Settings',
            'manage_options',
            'wp_headless_settings',
            array(&$this, 'page')
        );

    }
    function is_integration_enabled($integration_id) {
       return  $this->settings_modules["integrations"]->is_integration_enabled($integration_id);
    }
    function get_tabs($tabs = array())
    {
        // Pestanyes dels mòduls de AdminPanel
        $tabs["general"] = array("title" => __("General", "wpheadlessltd"), "callback" => array($this->settings_modules["general"], "render"));
        $tabs["integrations"] = array("title" => __("Integraciones", "wpheadlessltd"), "callback" => array($this->settings_modules["integrations"], "render"));
       
        if ( $this->is_integration_enabled("wphi-archivemetas")) {
            $tabs["archive-meta-cpts"] = array("title" => __("MetaSEO en páginas de archivo", "wpheadlessltd"), "callback" => array($this->settings_modules["archive-meta-cpts"], "render"));
        }
        // Pestanyes de mòduls de AdminSettings
        $tabs = apply_filters("wpheadless/settings/tabs", $tabs);

        return $tabs;
    }

    function page()
    {

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $sub = get_array_value($_GET, "sub", apply_filters("wpheadless/settings/tabs/default", "general"));


        $opt = "wp_headless_settings";

        ?>
        <h1>Headless Settings</h1>

        <?php do_action("wpheadless/settings/before/menu"); ?>
        <h2 class="nav-tab-wrapper">
            <?php
            $tabs = $this->get_tabs();
            $callback = "";
            foreach ($tabs as $tab_id => $tab_info) {
                $selected = "";
                $link = esc_attr(admin_url('options-general.php?page=' . $opt . '&sub=' . $tab_id));
                $title = get_array_value($tab_info, "title", "NoTitle:" . $tab_id);
                if ($tab_id == $sub) {
                    $this->sub = $sub;
                    $selected = "nav-tab-active";
                    $callback = get_array_value($tab_info, "callback", false);
                }
                ?>
                <a href="<?php echo $link; ?>" id="<?php echo $tab_id; ?>"
                   class="nav-tab <?php echo $selected; ?>"><?php echo $title; ?>
                </a>
                <?php
            }


            ?>
        </h2>
        <?php do_action("wpheadless/settings/after/menu", $this); ?>
        <?php

        if ($callback) {
            add_action("wpheadless/settings/tab/content", $callback);
        }
        do_action("wpheadless/settings/tab/content/before", $this);
        do_action("wpheadless/settings/tab/content", $this);
        do_action("wpheadless/settings/tab/content/after", $this);
        do_action("wpheadless/settings/css", $sub);
        do_action("wpheadless/settings/js", $sub);

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

    function render_sections($page, $sections = array())
    {
        $page = "wpheadless-page-" . $page;


        register_setting(
            $this->settings_option_name, // option_group
            $this->settings_option_name, // option_name
            array($this, 'input_sanitize') // sanitize_callback
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

                        echo $this->input($args);

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
        <form method="post" action="options.php">
            <?php
            settings_fields($this->settings_option_name);
            do_settings_sections($page);
            submit_button();
            ?>
        </form>
        <?php


    }

} // END if(!class_exists('wp_headless_Settings'))
