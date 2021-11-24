<?php

class Integrations
{
    public static function render(WPHeadlessAdminPanel $adminPanel)
    {
        echo "<h1>".__("Integraciones", "wpheadlessltd")."</h1>";
        echo "<p>".__("WPH se integra con estos plugins.", "wpheadlessltd")."</p>";
        $adminPanel->render_sections("archive-meta-cpts", self::getSections());
    }

    public static function getSections(): array
    {
        return array();
    }
}