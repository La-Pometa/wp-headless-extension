<?php

add_filter("wpheadless/modules/load", "wpheadless_archive_load_module");
function wpheadless_archive_load_module($modules)
{
    $modules["archive"] = "WPHeadlessArchive";
    return $modules;
}


final class WPHeadlessArchive extends WPHeadlessModule
{

    public function init()
    {
        add_action("wpheadless/content/init", array($this, "init_archive"));
    }


    function init_archive($cpt)
    {

        $this->console("Loading CPT [" . $cpt . "][meta_seo]");

    }
}

