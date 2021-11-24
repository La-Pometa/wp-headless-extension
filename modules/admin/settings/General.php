<?php

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