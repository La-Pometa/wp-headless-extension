<?php

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