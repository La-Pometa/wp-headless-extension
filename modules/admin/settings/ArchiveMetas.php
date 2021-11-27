<?php




/*-

    Plugin ID: whpi-archivemetas
    Plugin Name: WPHeadless-ArchiveMetas
    Plugin URI: https://www.lapometa.com/headless
    Description: Permeteix establir SEO Metas per Post Type Archive
    Version: 0.0.1
    Author: WPHeadless
    Text Domain: wpheadlessltd
    RequiresPHP: 7.4.2


-*/

define("WPHI_INTEGRATION_ARCHIVEMETAS_ID","wphi-archivemetas");

add_filter("wpheadless/integrations/info","wpheadless_rest_modules_vendor_archivemetas_admin_info");
function wpheadless_rest_modules_vendor_archivemetas_admin_info($modules) {

    $info = get_plugin_data(__FILE__);

    $modules[WPHI_INTEGRATION_ARCHIVEMETAS_ID] = array(
        "id"=>WPHI_INTEGRATION_ARCHIVEMETAS_ID,
        "title"=>get_array_value($info,"Name",false),
        "version"=>get_array_value($info,"Version","0.0.1"),
        "description"=>get_array_value($info,"Description",""),
        "type"=>"admin",
        "loaded"=>false,
    );
   
    return $modules;

}




class ArchiveMetas
{
    public static function render(WPHeadlessAdminPanel $adminPanel)
    {
        echo "<h1>".__("Etiquetas meta (SEO)", "wpheadlessltd")."</h1>";
        echo "<p>".__("Aquí puedes configurar las etiquetas seo que quieres en las rutas de listados.", "wpheadlessltd")."</p>";
        $adminPanel->render_sections("archive-meta-cpts", self::getSections());
    }

    public static function getSections(): array
    {
        $sections = array();

        foreach (get_post_types(array("public" => true), "objects") as $post_type) {
            $sections[$post_type->name] = array(
                "title" => $post_type->label,
                "description" => __("Seleccionar quin es el menu per al peu de pàgina.", "wpheadlessltd"),
                "fields" => array(
                    "title" => array(
                        "title" => "Title",
                        "description" => __("Estableix OG:TITLE.", "wpheadlessltd"),
                        "type" => "text",
                        "class" => "wide",
                    ),
                    "Description" => array(
                        "title" => "Description",
                        "description" => __("Estableix OG:DESCRIPTION.", "wpheadlessltd"),
                        "type" => "text",
                        "class" => "wide",
                    )
                ),
            );
        }

        return $sections;
    }
}