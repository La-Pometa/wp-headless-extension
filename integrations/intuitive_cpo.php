<?php


add_filter("wpheadless/modules/load","wpheadless_intuitivecpo_load_module");
function wpheadless_intuitivecpo_load_module($modules) {
    $modules["intuitivecpo"]="WPHeadlessIntuitiveCPO";
    return $modules;
}



class WPHeadlessIntuitiveCPO extends WPHeadlessModule {


    function _construct() {
        add_action("init",array($this,"init"));
    }

    function init() {
        add_action("wpheadless/content/init",array($this,"_content_init"));
    }


    function _content_init($cpt) {
		add_filter('rest_'.$cpt.'_collection_params', array($this,'filter_add_rest_orderby_params'), 10, 1);
    }

	function filter_add_rest_orderby_params($params) {

		$params['orderby']['enum'][] = 'menu_order';
		return $params;

	}

}
