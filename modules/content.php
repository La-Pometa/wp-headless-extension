<?php


class WPHeadlessContent extends WPHeadlessModules
{


	static $instance = false;

	public function __construct()
	{

		add_action("init", array($this, "init"));
		add_filter("the_content", array($this, "_filter_content"));
	}


	function _filter_content($content)
	{

		$content = do_shortcode($content);
		return $content;
	}

	function content_render_post_meta($object, $field_name, WP_REST_Request $request)
	{

		$post_type = get_post_type($object["id"]);
		$is_archive = false;


		if (get_array_value($request->get_params(), "id", false) == false) {

			/*- Estic en un llistat de post_type -*/
			$is_archive = true;

			if (!apply_filters("wpheadless/rest/content/list/post_meta/include", false, array("object" => $object, "request" => $request))) {
				return false;
			}
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


	function init()
	{

		add_filter('rest_post_dispatch', function ($object, $server, $request) {
			if (!get_array_value($request->get_params(), "per_page", false)) return $object;

			$responseData = $object->get_data();
			$newResponse = array();
			$newResponse['data'] = $responseData;
			$newResponse['page_meta'] = rest_do_request(new WP_REST_Request(
				'GET',
				'/wp/v2/types/' . $responseData[0]['type'],
				array(
					'lang' => get_array_value($request->get_params(), "lang", false),
				)
			))->get_data();

			$object->set_data($newResponse);
			return $object;
		}, 200, 3);


		remove_filter('the_excerpt', 'wpautop');

		global $wp_post_types;
		$posttypes = array_keys($wp_post_types);

		/*- Filtrar tipus de post types -*/
		$posttypes = apply_filters("wpheadless/rest/post-types", $posttypes);

		if (is_array($posttypes)) {
			foreach ($posttypes as $post => $cpt) {

				$this->console("Loading CPT [" . $cpt . "]");


				if ($cpt == "attachment") {
					$this->console("No aplicar en 'attachments'");

					continue;
				}

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



				do_action("wpheadless/content/init", $cpt);
			}
		}

		add_filter("wpheadless/rest/content/taxonomy/hide", array($this, "_prevent_terms"));
	}

	function _prevent_terms($terms)
	{
		$terms[] = "post_translations";
		$terms[] = "language";
		return $terms;
	}
}
