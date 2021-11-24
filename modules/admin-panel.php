<?php


add_filter("wpheadless/modules/load","wpheadless_adminpanel_load_module");
function wpheadless_adminpanel_load_module($modules) {
    $modules["adminpanel"]="WPHeadlessAdminPanel";
    return $modules;
}




class WPHeadlessAdminPanel extends WPHeadlessModules {

        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            // register actions
            add_action('admin_init', array(&$this, 'admin_init'));
            add_action('admin_menu', array(&$this, 'add_menu'));
        } // END public function __construct

        /**
         * hook into WP's admin_init action hook
         */
        public function admin_init()
        {


            // register your plugin's settings
            register_setting('wp_headless-group', 'rest-lang');

            // add your settings section
            add_settings_section(
                'wp_headless-section',
                __('WP Headless Settings', 'wordpress-headless'),
                array(&$this, 'settings_section_wp_headless'),
                'wp_headless'
            );

            // add your setting's fields
            add_settings_field(
                'wp_headless-rest-lang',
                __('Enable post and taxonomies lang on rest', 'wordpress-headless'),
                array(&$this, 'settings_field_input_checkbox'),
                'wp_headless',
                'wp_headless-section',
                array(
                    'field' => 'rest-lang'
                )
            );
            /*             add_settings_field(
                'wp_headless-setting_b',
                'Setting B',
                array(&$this, 'settings_field_input_text'),
                'wp_headless',
                'wp_headless-section',
                array(
                    'field' => 'setting_b'
                )
            ); */
            // Possibly do additional admin_init tasks
        } // END public static function activate

        public function settings_section_wp_headless()
        {
            // Think of this as help text for the section.
        }

        /**
         * This function provides text inputs for settings fields
         */
        public function settings_field_input_text($args)
        {
            // Get the field name from the $args array
            $field = $args['field'];
            // Get the value of this setting
            $value = get_option($field);
            // echo a proper input type="text"
            echo sprintf('<input type="text" name="%s" id="%s" value="%s" />', $field, $field, $value);
        } // END public function settings_field_input_text($args)

        public function settings_field_input_checkbox($args)
        {
            // Get the field name from the $args array
            $field = $args['field'];
            $default = (isset($args['default']) ? $args['default'] : false);
            // Get the value of this setting
            $value = get_option($field, $default);
            // echo a proper input type="text"
            echo sprintf('<input type="checkbox" name="%s" id="%s" value="1" %s/>', $field, $field, checked(1, $value, false));
        } // END public function settings_field_input_text($args)

        /**
         * add a menu
         */
        public function add_menu()
        {
            // Add a page to manage this plugin's settings
            add_options_page(
                'Headless',
                'Headless',
                'manage_options',
                'wp_headless',
                array(&$this, 'plugin_settings_page')
            );
        } // END public function add_menu()

        /**
         * Menu Callback
         */
        public function plugin_settings_page()
        {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            // Render the settings template
            include(sprintf("%s/../templates/settings.php", dirname(__FILE__)));
        } // END public function plugin_settings_page()

} // END if(!class_exists('wp_headless_Settings'))
