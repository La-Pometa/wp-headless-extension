<?php
/*
Plugin Name: Wordpress Headless Plugin
Plugin URI: https://lapometa.com
Description: A simple wordpress headless plugin to make your wordpress headless capable
Version: 0.0.3
Author: La Pometa
Author URI: https://github.com/La-Pometa
GitHub Plugin URI: La-Pometa/wp-headless-plugin
Primary Branch: main
License: GPL2
*/
/*
Copyright 2021  Jordi Gómez  (email : gnugomez@gmail.com)
Copyright 2021  Jordi Fonfreda  (email : suport@glapometa.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 5152 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once("includes/common.php");

if (!class_exists('WP_Headless')) {
    class WP_Headless
    {
        /**
         * @var IntegrationsLoader
         */
        private IntegrationsLoader $Integrations;

        /**
         * @var WPHeadlessModules
         */
        private WPHeadlessModules $Modules;

        /**
         * Construct the plugin object
         */
        public function __construct()
        {

            // Initialize Settings
            require_once(sprintf("%s/modules/module.php", dirname(__FILE__)));
            $this->Modules = new WPHeadlessModules();

            if (get_array_value($_GET, "debug", false) !== false) {
                $this->Modules->debug = true;
            }

            require_once(sprintf("%s/modules/IntegrationsLoader.php", dirname(__FILE__)));
            $this->Integrations = new IntegrationsLoader();

            //Carrega Vendors
            $this->Integrations->load();


            //Carrega Mòduls + Vendor Mòduls
            $this->Modules->load();

            $plugin = plugin_basename(__FILE__);
            add_filter("plugin_action_links_$plugin", array($this, 'plugin_settings_link'));

            $this->IntegrationsAdmin=false;
            if ( is_admin()) {
                // Estic a l'administrador? Carregar ThemeSettings 
                $this->Integrations->loadAdmin();
                $this->Integrations->loadAdminFilters();

            }


        } // END public function __construct

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            // Do nothing
        } // END public static function activate

        /**
         * Deactivate the plugin
         */
        public static function deactivate()
        {
            // Do nothing
        } // END public static function deactivate

        // Add the settings link to the plugins page
        function plugin_settings_link($links)
        {
            $settings_link = '<a href="options-general.php?page=wp_headless_settings">Settings</a>';
            array_unshift($links, $settings_link);
            return $links;
        }
    } // END class WP_Plugin_Template
} // END if(!class_exists('WP_Plugin_Template'))



if (class_exists('WP_Headless')) {
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_Headless', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_Headless', 'deactivate'));

    // instantiate the plugin class
    $wp_plugin_template = new WP_Headless();
}

