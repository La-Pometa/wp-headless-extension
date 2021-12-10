<?php


class WPHeadlessRoutes extends WPHeadlessModule {

    var $debug=false;

    function __construct() {
        $this->debug=false;
        add_action("rest_api_init",array($this,"init_new_routes"));

    }


    function init_new_routes() {

        /* Registrar noves routes */

        $this->console("Loading Routes...",$this->debug);
        do_action("wpheadless/routes/new");

    }

}
