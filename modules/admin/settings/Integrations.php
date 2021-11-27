<?php


/*-

    Plugin ID: whpi-integrations
    Plugin Name: WPHeadless-Integrations Info
    Plugin URI: https://www.lapometa.com/headless
    Description: Mostra informació relacionada amb les integracions amb WPHeadless
    Version: 0.0.3
    Author: WPHeadless
    Text Domain: wpheadlessltd
    RequiresPHP: 7.4.2


-*/

define("WPHI_INTEGRATION_INTEGRATION_ID","wphi-integration");

add_filter("wpheadless/integrations/info","wpheadless_admin_modules_integration_admin_info");
function wpheadless_admin_modules_integration_admin_info($modules) {

    $info = get_plugin_data(__FILE__);

    $modules[WPHI_INTEGRATION_INTEGRATION_ID] = array(
        "id"=>WPHI_INTEGRATION_INTEGRATION_ID,
        "title"=>get_array_value($info,"Name",false),
        "version"=>get_array_value($info,"Version","0.0.1"),
        "description"=>get_array_value($info,"Description",""),
        "type"=>"admin",
        "required"=>true,
        "loaded"=>false,
    );
   
    return $modules;

}

class Integrations
{

    private $debug;
    private $loaded=false;

    function __construct() {

        if ( $this->loaded ) {
            return;
        }

        $this->debug = false;
        $this->console("Loading INTEGRATIONS __construct ...");

        //Settings
        $this->settings=false;
        $this->settings_option = "wpheadless-admin-integrations";

        //Filtrar el valor de les columnes _per defecte_ de la taula Integraciones
        add_filter("wpheadless/integration/info/field/title/value",array($this,"_get_info_title"),20,2);
        add_filter("wpheadless/integration/info/field/id/value",array($this,"_get_info_id"),20,2);
        add_filter("wpheadless/integration/info/field/description/value",array($this,"_get_info_description"),20,2);
        add_filter("wpheadless/integration/info/field/version/value",array($this,"_get_info_version"),20,2);
        add_filter("wpheadless/integration/info/field/loaded/value",array($this,"_get_info_loaded"),20,2);

        $this->loaded=true;

    }

    private function console(string $string) {

        if ( $this->debug ) {
            echo " [INTEGRATIONS]: ".$string;
        }

    }

    function _get_info_title($title,$field_data) {
        $required = get_array_value($field_data,"required",false);
        $id = get_array_value($field_data,"id",false);
        $html = get_array_value($field_data,"title","NoTitle");
        $html.='<span class="subline">';
        $html.='<span class="subline-element"><strong>'.__("ID","wpheadlessltd").':</strong>'.$id.'</span>';
        if ( $required ) {
            $html.='<span class="subline-element subline-element-required"><strong>'.__("Requerit","wpheadlessltd").'</strong></span>';
        }
        $html.='</span>';
        return $html;
            }
    function _get_info_dscription($title,$field_data) {
        return get_array_value($field_data,"description","");
    }
    public function _get_info_id($id,$field_data) {
        return get_array_value($field_data,"id","NoID");
    }
    public function _get_info_version($version,$field_data) {
        return get_array_value($field_data,"version","NoVersion");
    }
    public function _get_info_description($version,$field_data) {
        return get_array_value($field_data,"description","NoDescription");
    }
   public function _get_info_loaded($loaded,$field_data) {
       $loaded = get_array_value($field_data,"loaded",false);
       $id = get_array_value($field_data,"id",false);
       $required = get_array_value($field_data,"required",false);

       $is_enabled = ($required || $this->is_integration_enabled($id));
       $is_disabled = $required;

       $html = '<input
                    type="checkbox"
                    name="integration['.$id.'][enabled]"
                    '.($is_enabled?' checked="checked"':'').'
                    '.($is_disabled?' disabled="disabled"':'').'
                >';
       return $html;
    }
    private function set_options($new) {
        update_option($this->settings_option,$new);
        $this->settings=$new;
    }
    private function get_options() {
        if ( $this->settings == false ) {
            $this->settings = get_option($this->settings_option);
        }
        return $this->settings;
    }

    public function is_integration_enabled($integration_id) {

        //Si acabo de guardar no funciona si no ho comprovo abans
        if ( get_array_value($_POST,"action",false) == "save" ) {
            return get_array_value(get_array_value(get_array_value($_POST,"integration",array()),$integration_id,array()),"enabled",false);
        }


        return get_array_value(get_array_value($this->get_options(),$integration_id,array()),"enabled",false);

    }

    public function render(WPHeadlessAdminPanel $adminPanel)
    {

        echo "<h1>".__("Integraciones", "wpheadlessltd")."</h1>";
        echo "<p>".__("WPH se integra con estos plugins.", "wpheadlessltd")."</p>";
        //$adminPanel->render_sections("archive-meta-cpts", self::getSections());


        // Comprovar el guardar les dades

        if ( get_array_value($_POST,"action",false) == "save") {
            $this->set_options(get_array_value($_POST,"integration",array()));
        }


        $integration_table="";
        $integration_table_rows="";

        //Agafa totes les integracions
        $integrations = self::getIntegrations();


        // Columnes de la taula Integraciones
        $integration_fields = array(
            "loaded"=>__("Actiu","wpheadless"),
            "title"=>__("Títol","wpheadlessltd"),
            "version"=>__("Versió","wpheadless"),
            "description"=>__("Descripció","wpheadlessltd"),
        );

        // Filtrar columnes per a modificar
        $integration_fields = apply_filters("wpheadless/integrations/info/fields",$integration_fields);

        // Generació de cada fila de la taula
        if ( is_array($integrations)) {
            foreach($integrations as $integration_id => $integration_data) {

                //Informació mínima per a cada Integració
                $integration_title = get_array_value($integration_data,"title","Integration::NoTitle(".$integration_id.")");
                $integration_version = get_array_value($integration_data,"version","0.0");
                $integration_loaded = get_array_value($integration_data,"loaded",false);
                $integration_required = get_array_value($integration_data,"required",false);

                //El plugin està activat?
                $integration_loaded = ($integration_required || $this->is_integration_enabled($integration_id));

                $loaded = "integration-".($integration_loaded ? "loaded":"not-loaded");

                // Comença la fila
                $integration_table_rows .='<tr class="inteagration-item '.$loaded.'">';

                //Establir el valor corresponent per a cada columna
                foreach($integration_fields as $field_id => $field_data ) {

                        //Filtrar valor de la columna
                        $value = apply_filters("wpheadless/integration/info/field/".$field_id."/value","",$integration_data);

                        //Cel·la
                        $integration_table_rows .= '<td class="column-'.$field_id.'">'.$value.'</td>';
                }

                // Acaba la fila
                $integration_table_rows .='</tr>';

            }
        }

        
        // Generar capçalera de la taula
        $integration_table_header = "";
        foreach($integration_fields as $field_id => $field_title) {
            //Filtrar títol de la columna
            $field_title = apply_filters("wpheadless/integration/info/header/".$field_id."/title",$field_title);
            $integration_table_header .='<th class="column-'.$field_id.'">'.$field_title.'</th>';
        }

        // Generar taula
        $integration_table = ''.
                    '<table class="wp-list-table widefat striped table-view-list wph-table">'. 
                        '<thead>'.$integration_table_header.'</thead>'. 
                        '<tobdy>'.$integration_table_rows.'</thead>'. 
                    '</table>'
        ;

        // Mostrar taula
        ?>
            <form action="" method="POST">
                <input type="hidden" name="action" value="save">
                <?php  echo $integration_table; ?>
                <input type="submit" class="button button-primary" value="<?php echo __("Guardar","whpeadlessltd"); ?>">
            </form>
        <?php
        // Carregar CSS i Javascript
        self::render_css();
        self::render_js();


    }
    private static function render_css() {

        // Carregar CSS Per a Integrations
        ?>
        <style type="text/css">
            .wph-table .column-description{width:40%;min-width:40%}
            .wph-table span.subline {display: block;width: 100%;font-size: 90%;}
            .wph-table span.subline span.subline-element {display: inline-block;margin-right: 10px;font-style: italic;}
            .wph-table span.subline span.subline-element strong {display: inline;margin-right: 5px;font-style: normal;font-size: 100%;}
            .wph-table span.subline-element.subline-element-required {background: #444;border-radius: 8px;color: #1b1b1b;padding: 0px 5px;}
            .wph-table tr.integration-not-loaded td {color: #444!important;}
            .wph-table tr.inteagration-item.integration-not-loaded {background: #222;}
        </style>
        <?php

        // Altres CSS Per a Integrations
        do_action("wpheadless/integration/info/css");

}
    private static function render_js() {

        // Carregar JS Per a Integrations
        ?>
        <script type="text/javascript">
        </script>
        <?php

        // Altres JS Per a Integrations
        do_action("wpheadless/integration/info/javascript");

    }
    public static function getIntegrations(): array
    {
        //Filtrar cada integració
        return apply_filters("wpheadless/integrations/info",array());
    }
}