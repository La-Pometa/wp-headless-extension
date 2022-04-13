<?php



defined( 'ABSPATH' ) || exit;

//include_once 'gutenberg-blocks/wphcomponent/index.php';
include_once 'gutenberg-blocks/01-basic/index.php';

add_filter("wpheadless/modules/load","wpheadless_gutenberg_load_module");
function wpheadless_gutenberg_load_module($modules) {
    $modules["gutenberg"]="WPHeadlessGutenberg";
    return $modules;
}

class WPHeadlessGutenberg extends WPHeadlessModule {


	private $cpts=array();
	private $slices=array();
	private $post_id=false;
	private $slice_position=0;
	private $content="";
	private $blocks = array();
	private $blocks_registered=0;
	private $upload_path;

	function __construct() {


	}
    function init() {

		$this->setAsIntegration();

		$this->console("Gutenberg: Init");
		$this->cpts = apply_filters("wpheadless/gutenberg/cpt",array("page"=>"page"));


		$this->blocks = array(
			"wphcomponent"
		);

		$this->blocks_register_enabled = false;
		$this->console("Gutenberg: Loading Blocks: ".implode(" ",$this->blocks));
		if ( $this->blocks_register_enabled ) {
			$this->blocks_register();
			$this->console("Gutenberg: Loaded  ".$this->blocks_registered." Blocks.");
		}
		else {
			$this->console("Gutenberg: Not loaded 'bocks_register_enabled=false'");
		}

		add_action("wpheadless/content/init" , [$this,"_content_init"] );
		add_filter("wpheadless/content", [ $this, "_content_render"] , 20 , 2 );
		add_filter("wpheadless/path/response", [ $this, "_response" ]);
		//$this->bind_gutenberg_core_elements();

		$cpts = array();
		if (is_array($this->cpts)){
			foreach($this->cpts as $cpt => $cpt_label) {
				$cpts[]=$cpt;
			}
		}
		$this->console("Gutenberg: Loading CPTS: ".implode(" ",$cpts));

		// add_filter("render_block_data", [ $this , "_render_block_data" ] , 200 , 3);
		// add_filter("pre_render_block", [ $this , "_pre_render_block" ] , 200 , 3);
		add_filter("render_block" , [ $this, "_render_block" ] , 200 , 3);


    }
	function _render_block($block_output, $parsed_block, $render = false ) {

		$media_out = $this->_image_replace_url_out();

		$block_output="HOLA!";
		//echo "<br> MEDIAOUT[".$block_output."]";
		if ( $media_out ) {
		}
		return $block_output;
	}
	function _image_replace_url_out() {
			return apply_filters("wpheadless/gutenberg/media/url",false);
	}
	function _image_replace_url_in() {

		if ( !$this->upload_path) {
			$uploads = wp_upload_dir();
			$upload_path = $uploads['baseurl'];
			$this->upload_path = $upload_path ."/";
		}
		return $this->upload_path;
	}
	function _pre_render_block($output, $parsed_block, $parent_block = false) {
			echo "<br> OUTPUT:<pre>".print_r($output,true)."</pre>";
			return $output;
	}
	function _render_block_data($parsed_block, $source_block, $parent_block = false) {

		$innerHTML = get_array_value($parsed_block,"innerHTML","");
		$media_out = $this->_image_replace_url_out();

		
		if ( $media_out ) {
			$media_in = $this->_image_replace_url_in();
			$innerHTML = str_replace($media_in,$media_out,$innerHTML);
			$parsed_block["innerHTML"]=$innerHTML;
			$parsed_block["innerHTML"]="11";
			echo "<br> PARSED BLOCK:<pre>".print_r($parsed_block,true)."</pre>";
		}
		return $parsed_block;

	}
	function blocks_render_wphcomponent() {
			$html="render:blocks_render_wphcomponent";
			return $html;
	}
	function blocks_args($block_id) {
			$args = array();

			$args["render_callback"]= [ $this, "blocks_render_".$block_id ];
			$args["attributes"] = array();
			$atts = array();
			switch($block_id) {
				case "wphcomponent":
					$atts["component_id"]=array(
						"default"=>false,
						"type"=>"numeric"
					);
					break;
			}

			$args["attributes"]=$atts;
			return $args;
	}
	function blocks_register() {

		if ( $this->blocks && is_array($this->blocks) && count($this->blocks)) {
			foreach($this->blocks as $block_id) {
				$path = __DIR__  ."/gutenberg-blocks/".$block_id;
				$this->console("Gutenberg: Loading Block: ".$block_id);
				$block_args = $this->blocks_args($block_id);
				echo "<br> Loading Block(".$block_id."): [".$path."]";
				require_once($path."/index.php");

				// if(register_block_type($path,$block_args)) {
				// 	$this->blocks_registered++;
				// }
			}

		}

	}

    function _content_init($cpt) {

		if ( in_array($cpt,$this->cpts)) {
			$this->console("Gutenberg: Loading filters for '".$cpt."'");
			
		}
	}

	function _content_render($original_content,$object) {

		$this_content = get_array_value($object,"content",array());

		if ( get_array_value($this_content,"block_version") == 1 ) {

			$this->post_id = get_array_value($object,"id","NONID");
			
			//echo "<br> OBJECT: <pre>".print_r($object,true)."</pre>";
			$content = get_array_value($this_content,"raw","");	
			$this->content = $content;
			//echo "<br> CONTENT: <pre>".print_r($content,true)."</pre>";
			$object_type = get_array_value($object,"type",false);

			if ( in_array($object_type,$this->cpts)) {
				$blocks = parse_blocks($content);

				$blocks = apply_filters("wpheadless/content/blocks",$blocks);
				
				//echo "<br> RES: <pre>".print_r($res,true)."</pre>";
				// Convertir a Slices 
				$content = "content-filtered-by-gutenberd-integration";
				foreach($blocks as $block_pos => $block_data) {
					$this->slices[]=$block_data;
				}

			}
		}
		return $original_content;
	}

	function _response($response) {

		if ( $this->slices && count($this->slices)) {
			$response["block"]=$this->content;

			foreach($this->slices as $slice_block_pos => $slice_block_data) {
				
				$slice = $this->get_slice_from_block($slice_block_data);
				if ( $slice ) {
					$response["slices"][] = $slice;
				}

			}
		}

		return $response;
	}


	function get_slice_from_block($block_data) {

		
		$name = get_array_value($block_data,"blockName",false);
		if ( !$name ) {
			$name="core/wpeditor";
		}

		$this->slice_position++;

		$slice = $block_data;
		$slice_args = array(
			"post_id"=>$this->post_id
		);
		$filter = "wpheadless/gutenberg/slice/".$name;
		//echo "<br> Loading Filter[".$filter."]";
		$slice = apply_filters($filter,$block_data,$slice_args);

		return $slice;
	}

	function bind_gutenberg_core_elements() {

		add_filter("wpheadless/gutenberg/slice/core/site-title", [ $this , "_block_core__site_title" ], 20, 2);
		add_filter("wpheadless/gutenberg/slice/core/paragraph", [ $this , "_block_core__paragraph" ], 20, 2);
		add_filter("wpheadless/gutenberg/slice/core/list", [ $this , "_block_core__list" ], 20, 2);
		add_filter("wpheadless/gutenberg/slice/core/cover", [ $this , "_block_core__cover" ], 20, 2);
		add_filter("wpheadless/gutenberg/slice/core/image", [ $this , "_block_core__image" ], 20, 2);
		add_filter("wpheadless/gutenberg/slice/core/wpeditor", [ $this , "_block_core__wpeditor" ], 20, 2);
	}


	function _block_core__site_title($block_data,$slice_args) {
		$HeadingLevel = get_array_value(get_array_value($block_data,"attrs",array()),"level","2");
		$title = get_array_value($block_data,"innerHTML",false);
		if ( !$title ) {
			$title = get_bloginfo( 'name' );
		}
		$html = '<h'.$HeadingLevel.'>'.$title.'</h'.$HeadingLevel.'>';
		//echo "<br> HTML:$html";
		$slice = array(
			"type"=>"content",
			"position"=>$this->slice_position,
			"params"=>array(
				"content"=>$html
			)
		);
		return $slice;
	}
	function _block_core__wpeditor($block_data,$slice_args) {
		$content = get_array_value($block_data,"innerHTML",false);
		$content_filtered = str_replace(array("\n","\r"),array("",""),$content);
		$content_filtered = str_replace("<p></p>","",$content_filtered);

		if ( !$content_filtered || !strip_tags($content_filtered)) {
			return false;
		}

		
		$slice = array(
			"type"=>"content",
			"position"=>$this->slice_position,
			"margins"=>"0",
			"params"=>array(
				"content"=>$content
			)
		);
		return $slice;
	}
	function _block_core__slice_content($block_data,$slice_args) {
		$content = get_array_value($block_data,"innerHTML",false);

		$content = get_array_value($block_data,"innerHTML",false);
		$content_filtered = str_replace(array("\n","\r"),array("",""),$content);
		$content_filtered = str_replace("<p></p>","",$content_filtered);

		if ( !$content_filtered || !strip_tags($content_filtered)) {
			return false;
		}


		$slice = array(
			"type"=>"content",
			"position"=>$this->slice_position,
			"margins"=>"0",
			"params"=>array(
				"content"=>$content
			)
		);
		return $slice;
	}
	function _block_core__list($block_data,$slice_args) {
		return $this->_block_core__slice_content($block_data,$slice_args);
	}
	function _block_core__paragraph($block_data,$slice_args) {
		return $this->_block_core__slice_content($block_data,$slice_args);
	}

	function _block_core__image($block_data,$slice_args) {


		$image_id = get_array_value(get_array_value($block_data,"attrs",array()),"id",false);
		$image_mbl_id = $image_id;
		$image_md = $image_mbl_md = false;
		if ( $image_id ) {
			$image_md = wp_get_attachment_metadata($image_id);
			$image_mbl_md = $image_md;
		}



		$align = get_array_value(get_array_value($block_data,"attrs",array()),"align",false);
		$class = get_array_value(get_array_value($block_data,"attrs",array()),"class",false);
		$caption = get_array_value($block_data,"innerHTML",false);

	


		$params = array(
			"image"=>$image_md,
			"imagembl"=>$image_mbl_md,
			"title"=>"",
			"class"=>$class,
		);


		$caption = strip_tags($caption);
		if ( $caption ) {
			$params["title"]=$caption;
		}
	
		if ( $align ) {
			$params["align"]=$align;
		}

		$slice = array(
			"type"=>apply_filters("wpheadless/slice/name","image"),
			"position"=>$this->slice_position,
			"params"=>$params
		);


		return $slice;


	}

	function _block_core__cover($block_data,$slice_args) {

		$image_id = get_array_value(get_array_value($block_data,"attrs",array()),"id",false);
		$image_mbl_id = $image_id;
		$image_md = $image_mbl_md = false;
		if ( $image_id ) {
			$image_md = wp_get_attachment_metadata($image_id);
			$image_mbl_md = $image_md;
		}
		$title = "";
		$layout="default";
		$class="class";
		$attrs = array();


		$focalpoint = get_array_value(get_array_value($block_data,"attrs",array()),"focalPoint",false);
		if ( $focalpoint && is_array($focalpoint)) {
			$x = get_array_value($focalpoint,"x",50);
			$y = get_array_value($focalpoint,"y",50);
			$x = ($x * 100)."%";
			$y = ($y * 100)."%";
			$attrs["object-cover"]="cover";
			$attrs["object-position"]="".$x." ".$y;
		}

		// Hi ha algo dins de la imatge?
		$inner = get_array_value($block_data,"innerBlocks",array());
		$content = "";
		if ( is_array($inner) && count($inner)) {
			foreach($inner as $inner_pos => $inner_block ) {
				$name = get_array_value($inner_block,"blockName",false);

				switch($name) {
					case "core/paragraph":
						$content .= get_array_value($inner_block,"innerHTML",false);
						break;
					default:
						break;
				}
			}

		}

		$params = array(
				"image"=>$image_md,
				"imagembl"=>$image_mbl_md,
				"title"=>$title,
				"content"=>$content,
				"layout"=>$layout,
				"class"=>$class,
		);
		if ( $attrs && is_array($attrs) && count($attrs) ) {
			$params["attrs"]=$attrs;
		}

		$slice = array(
			"type"=>apply_filters("wpheadless/slice/name","image"),
			"position"=>$this->slice_position,
			"params"=>$params
		);


		return $slice;

	}

}