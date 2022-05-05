<?php


class WPHeadlessContent extends WPHeadlessModule
{


	var $excerpt = "";
	var $site_url = false;


	function _filter_content($content)
	{	
		$content = do_shortcode($content);
		return $content;
	}


	function content_render_components($object, $field_name, WP_REST_Request $request)
	{

		if ( $this->is_post_archive()) {
			if (!apply_filters("wpheadless/rest/content/list/components/include", false, array("object" => $object, "request" => $request))) {
				return false;
			}
		}

		$object = apply_filters("wpheadless/rest/content/components", array(), $object);

		return $object;
	}

	function content_render_post_meta($object, $field_name, WP_REST_Request $request)
	{
		//echo "<br> POST TYPE[".$this->get_instance()->get_request_type()."]";
		if ( $this->is_post_archive()) {
			if (!apply_filters("wpheadless/rest/content/list/post_meta/include", false, array("object" => $object, "request" => $request))) {
				return false;
			}
		}
		$post_type = get_array_value($object["type"]);

		if ( !apply_filters("wpheadless/rest/content/single/post_meta/include", true , array("post_type"=>$post_type,"object" => $object, "request" => $request)) ) {
			return false;
		}
		

		/*- Passar les variables de postmeta arreglades -*/

		$post_meta = get_post_meta($object["id"]);

		if ($post_meta and is_array($post_meta)) {
			foreach ($post_meta as $meta_key => $value) {
				if ($value and is_array($value) and count($value) == 1) {
					$value = get_array_value($value, 0, false);
					$post_meta[$meta_key] = $value;
				}
			}
		}

		/*- Aplicar altres modificacions al post_meta de qualsevol PostType -*/
		$post_meta = apply_filters("wpheadless/rest/content/post_meta", $post_meta, $object);


		return $post_meta;
	}


	function content_render_term_objects($object, $field_name, $request)
	{


		/*- Passar les variables de taxs arreglades -*/

		$object_id = get_array_value($object, "id", false);
		$object_type = get_array_value($object, "type", false);
		$terms_check = get_object_taxonomies($object_type);
		$ret = array();

		/*- Filtrar quines Taxonomy s'afegeixent al rest -*/
		$prevent_term = apply_filters("wpheadless/rest/content/taxonomy/hide", array());

		foreach ($terms_check as $term_taxonomy) {
			if (in_array($term_taxonomy, $prevent_term)) {
				continue;
			}
			$object_terms = get_the_terms($object_id, $term_taxonomy);
			if ($object_terms && is_array($object_terms)) {
				foreach ($object_terms as $object_term) {
					$new_term = array("taxonomy" => $term_taxonomy);
					$new_term["name"] = ($object_term ? $object_term->name : "no-term-name-" . $item_id . "-" . $term_taxonomy);
					$new_term["link"] = ($object_term ? get_term_link($object_term) : '');
					$new_term["term_id"] = $object_term->term_id;
					$new_term["taxonomy"] = $term_taxonomy;
					$ret[] = $new_term;
				}
			}
		}

		/*- Aplicar altres modificacions als terms de qualsevol Taxonomy -*/
		$ret = apply_filters("wpheadless/rest/content/taxonomy", $ret, $object);


		return $ret;
	}
	function _post_dispatch($object, $server, $request) {
		$route = $request->get_route();
		//echo "<br> ROUTE: : $route";die();
		$route_prevent = array(
			"/wp/v2/blocks",
			"/wp/v2/pages",
			"/wp/v2/media",
			"/wp/v2/themes",
			"/wp/v2/templates",
			"/wp/v2/taxonomies",
			"/wp/v2/categories",
			"/wp/v2/tags",
		);
		$this->console("_post_dispatch: route[".$route."]");
		if ( in_array($route,$route_prevent) ) {
			//echo "<br> OJBECT: <pre>".print_r($object,true)."</pre>";
			return $object;
		}

		if ( $this->is_post_archive()) {
			if ( $this->is_embed() ) {
				global $wp_rest_server;
				$responseData= $wp_rest_server->response_to_data($object,true );
			}
			else {
				$responseData = $object->get_data();
			}

		 	$responseData = apply_filters("wpheadless/archive",array("entries"=>$responseData),$object,$request);
		 	$object->set_data($responseData);
			 $object->entries = $responseData;

			// echo "<br> RESPONSE DATA: <pre>".print_r($responseData,true)."</pre>";
		//	echo "<br> SERVER :<pre>".print_r($object,true)."</pre>";
		}
		return $object;
	}

	function content_render_author_objects($object, $field_name, $request)
	{
		$post = get_post($object['id']);
		$author_id = $post->post_author;
		$author_name = get_the_author_meta("display_name", $author_id);
		$author_url = get_the_author_link($author_id);
		$author_image = get_avatar($author_id);

		$output = array(
			"id" => $author_id,
			"name" => $author_name,
			"image" => array("rendered" => $author_image),
			"url" => $author_url
		);

		return $output;
	}

	function _headless_archive($response , $object, $request) {

		$newResponse = $response;
		return $newResponse;


	}

	function init()
	{

		// remove_filter('the_excerpt', 'wpautop');
		if ( !is_admin() ) {
			add_filter('rest_post_dispatch',array($this,'_post_dispatch'),200,3);
			// add_filter("the_content", array($this, "_filter_content"),20);
			// add_filter("wpheadless/archive",array($this,"_headless_archive"),20,3);
		}
		add_action("plugins_loaded", array($this, "load"),2000);

	}

	function load() {

		global $wp_post_types;
		$posttypes = array_keys($wp_post_types);

		/*- Filtrar tipus de post types -*/
		$posttypes = apply_filters("wpheadless/rest/post-types", $posttypes);

		if (is_array($posttypes)) {
			foreach ($posttypes as $post => $cpt) {





				$this->console("Loading CPT [" . $cpt . "]");

				$cpts_no = array(
					"attachment",
					"wp_block",
				);
				if (in_array($cpt,$cpts_no)) {
					$this->console("No aplicar en '".$cpt."");

					continue;
				}

				 if ( !is_admin() ) {
					 add_filter($cpt."_link", [ $this , "_headless_get_permalink"],100,2);


					register_rest_field(
						$cpt,
						'content',
						array(
							'get_callback'    => array($this, "content_render"),
							'update_callback' => null,
							'schema'          => null,
						)
					);
			
					$this->console("Loading CPT [" . $cpt . "][content_render_excerpt]");

					register_rest_field(
						$cpt,
						'excerpt',
						array(
							'get_callback'    => array($this, "content_render_excerpt"),
							'update_callback' => null,
							'schema'          => null,
						)
					);
					
				}
				

				$this->console("Loading CPT [" . $cpt . "][tax_info]");
				register_rest_field(
					$cpt,
					'tax_info',
					array(
						'get_callback'    => array($this, "content_render_term_objects"),
						'update_callback' => null,
						'schema'          => null,
					)
				);

				$this->console("Loading CPT [" . $cpt . "][autor_info]");
				register_rest_field(
					$cpt,
					'author_info',
					array(
						'get_callback'    => array($this, "content_render_author_objects"),
						'update_callback' => null,
						'schema'          => null,
					)
				);

				if ( apply_filters("wpheadless/rest/content/single/post_meta/include", true, array("post_type"=>$cpt)) ) {
					$this->console("Loading CPT [" . $cpt . "][meta_info]");
					register_rest_field(
						$cpt,
						'meta_info',
						array(
							'get_callback'    => array($this, "content_render_post_meta"),
							'update_callback' => null,
							'schema'          => null,
						)
					);
				}



				$this->console("Loading CPT [" . $cpt . "][slices]");
				register_rest_field(
					$cpt,
					'slices',
					array(
						'get_callback'    => array($this, "content_render_components"),
						'update_callback' => null,
						'schema'          => null,
					)
				);






				do_action("wpheadless/content/init", $cpt);
			}
		}

		add_filter("wpheadless/rest/content/taxonomy/hide", array($this, "_prevent_terms"));
	}
	function _headless_get_permalink($url, $post_id) {

		if ( $this->site_url == false ) {
			$this->site_url = site_url();
		}

		$url = str_replace($this->site_url,"",$url);

		$url = apply_filters("wpheadless/content/link",$url,$post_id);

		return $url;


	}

    function content_render_excerpt($object, $field_name, $request)
    {
		$post_content = get_array_value(get_array_value($object,"content",array()),"raw","");

		if ( !$post_content ) {
				$post_content = $this->excerpt;
		}

	//	$post_content = apply_filters("wpheadless/content/excerpt",$post_content,$object);

		$content = preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $post_content);

        $content = strip_tags($content);
      	$content = wp_trim_words($content,25);
       	$content = str_replace(array("\n","\t"),array("",""),$content);


		
		return array("rendered"=>$content);
	}

    function content_render($object, $field_name, $request)
    {
        $post_content = get_array_value(get_array_value($object,"content",array()),"raw","");

		$this->excerpt = $post_content;
        if ( $this->is_post_archive()) {
			$post_content = "#";
        }
		$post_content = apply_filters("wpheadless/content",$post_content,$object);
		$post_content = do_shortcode($post_content);
		return array("rendered"=>$post_content);
    }

	function _prevent_terms($terms)
	{
		$terms[] = "post_translations";
		$terms[] = "language";
		return $terms;
	}
}





