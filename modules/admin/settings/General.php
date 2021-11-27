<?php



/*-

    Plugin ID: whpi-general
    Plugin Name: WPHeadless-General
    Plugin URI: https://www.lapometa.com/headless
    Description: Configuració general
    Version: 0.0.1
    Author: WPHeadless
    Text Domain: wpheadlessltd
    RequiresPHP: 7.4.2


-*/

define("WPHI_INTEGRATION_GENERAL_ID","wphi-general");

add_filter("wpheadless/integrations/info","wpheadless_admin_modules_general_admin_info");
function wpheadless_admin_modules_general_admin_info($modules) {

    $info = get_plugin_data(__FILE__);

    $modules[WPHI_INTEGRATION_GENERAL_ID] = array(
        "id"=>WPHI_INTEGRATION_GENERAL_ID,
        "title"=>get_array_value($info,"Name",false),
        "version"=>get_array_value($info,"Version","0.0.1"),
        "description"=>get_array_value($info,"Description",""),
        "type"=>"admin",
        "required"=>true,
        "loaded"=>false,
    );
   
    return $modules;

}



class General
{
    public static function render(WPHeadlessAdminPanel $adminPanel)
    {
        echo "<h1>".__("Ajustes generales", "wpheadlessltd")."</h1>";
        $adminPanel->render_sections("archive-meta-cpts", self::getSections());
    }

    public static function getSections(): array
    {
        return array();
    }
}