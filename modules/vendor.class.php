<?php


class WPHeadlessVendors {




    var $vendors_path; 
    var $vendors=array();


    function _construct() {

        add_action("init",array($this,"init"));
        $this->vendors_path= false;
        $this->vendors = array();

    }

    function init() {
    }
    function load() {

        $path = $this->get_vendors_path();
        $search = $path."*.php";

        $files = glob($search);
        if ( $files and is_array($files)) {
            foreach($files as $file) {
                $vendor = str_replace(array($this->get_vendors_path(),".php"),array("",""),$file);
                $this->vendors[$vendor]=$file;
                require_once($file);
            }
        }
        $this->_load();
    }

    function get_vendors_path() {
        if ( $this->vendors_path == false) {
            $this->vendors_path = str_replace("/modules","/vendors",dirname(__FILE__))."/";
        }
        return $this->vendors_path;
    }

    function _load() {


    }


}