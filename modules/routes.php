<?php


class WPHeadlessRoutes extends WPHeadlessModule {

    function init() {
        add_action("rest_api_init",array($this,"init_new_routes"));

    }

    function init_new_routes() {

        /* Registrar noves routes */

        $this->console("Loading Routes...");
        do_action("wpheadless/routes/new");

    }

}
