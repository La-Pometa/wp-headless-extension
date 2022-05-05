<?php


add_filter("wpheadless/modules/load","wpheadless_path_load_module");
function wpheadless_path_load_module($modules) {
    $modules["path"]="WPHeadlessPath";
    return $modules;
}


class WPHeadlessPath extends WPHeadlessModule {



        function init() {



				$this->console("Register ::init_routes");
                add_action("wpheadless/routes/new",array($this,"init_routes"));
				add_filter( 'pre_get_posts', [$this,"pre_get_posts"] ,10000 );
				add_filter( 'posts_where', [$this,'_posts_where'], 10000, 2 );

				add_filter("wpheadless/request/type/filter",array($this,"_request_type"),20,2);
        }


		function _request_type($type,$call) {

			if ( $call == "path") {
				$type = "single";
			}

			return $type;
		}

		function _posts_where( $where, $wp_query ) {

			if ( parent::get_settings_allow_duplicate_slugs() ) {
				global $wpdb;
				$from  = $wp_query->get( 'from' );

				if ( $from == "path" && $wp_query->get( 'likename' ) ) {

					$names =  $wp_query->get( 'likename' );

					if ( !is_array($names)) { $names=array($names); }
					$where .="AND ( ";
					$n=0;
					foreach($names as $name_pos => $name) {
						$where .=($n?  ' OR ':'').  $wpdb->posts . '.post_name = \'' . esc_sql( $wpdb->esc_like( $name) )  . '\'';
						$n++;
					}
					$where.=" )";
				}
			}

			return $where;
		}

		function pre_get_posts($query) {

			if ( is_admin()) {
				return $query;
			}


			$lang = get_array_value($_GET,'lang',"es");
			$vars = get_object_value($query,"query",array());

			if ( get_array_value($vars,"from",false) != "path") {
				return $query;
			}

			$query->query_vars["lang"]=$lang;

			
			return $query;
		}

		function init_routes() {

			$this->console("Loading Route [path]");
			register_rest_route( 'wp/v2', '/path/', array(
				'methods' => WP_REST_Server::READABLE,
		        'callback' => array($this,"get_content_by_path"),
				'permission_callback' => '__return_true',
		        'args' => array(
		            'slug' => array (
		                'required' => false
		            )
		        )
		    ) );

		}
		function get_content_by_path(WP_REST_Request $request ) {

			$slug = get_array_value($_GET,'slug',false);
			$lang = get_array_value($_GET,'lang',"es");

			$translate = get_array_value($_GET,'translate',"");
			$multi_language=apply_filters("wpheadless/rest/path/multilanguage",false);
			$content="";
			$ret=array();
			$object_type = "post_type";
			$object_object = "page";

			$this->console("GET Request (Multilanguage): ".($multi_language ? "TRUE" :"FALSE"));

			$embed = false;
			if ( get_array_value($_GET,"_embed",false) !== false ) {
				 $embed = true;
			}

			if ( !$slug ) {

				$post_id = apply_filters("wpheadless/rest/path/frontpage",false);
				$response["path"]["frontpage"]=$post_id;

				if ( !$post_id ) {
					$post_id = get_option('page_on_front');
				}


				if ( $post_id ) {
					if ( $multi_language) {
						$post_id = pll_get_post($post_id,$lang);
					}

					$this->console("PATH[NO SLUG='".$slug."']='".$post_id."'");
					$request_url = '/wp/v2/pages/'.$post_id;

					$this->console("GET Request: $request_url");
					$request = new WP_REST_Request( 'GET', $request_url , $_GET);

					$request_resp = rest_do_request( $request );

					$ret=get_object_value($request_resp,"data",false);

					// Embed ?
					if ( $embed ) {
						global $wp_rest_server;
						$ret= $wp_rest_server->response_to_data($request_resp, $embed);
					}

				}

			}

			else {

				$args = array( 'post_type' => 'any','name'=>$slug, 'fields'=> 'ids','from'=>'path','post_status'=>'publish','lang'=>$lang);
				//$args['lang'] = ( $multi_language ? $args["lang"] : "" );

				if ( parent::get_settings_allow_duplicate_slugs() ) {
					unset($args["name"]);
					$ln=array();
					$ln[]=$slug;
					$args["likename"]=apply_filters("wpheadless/path/query/likename",$ln,$slug);
					$args["lang"]=$lang;
				}


				if ( !get_array_value($args,"lang",false)) {
				//	unset($args["lang"]);
				}

				$args =  apply_filters("wpheadless/path/slug/query/args",$args,$this);


				$content_id = false;
				$object_id =  apply_filters("wpheadless/path/slug/content/id",false,$args,$this);

				if ( is_array($object_id)) {
					$object_type = get_array_value($object_id,"obj_type",$object_type);
					$object_object = get_array_value($object_id,"obj_object",$object_object);
					$object_id = get_array_value($object_id,"obj_id",false);
				}


				if ( !$object_id ) {
					//echo "<br> QUERY ARGS: <pre>".print_r($args,true)."</pre>";
					$query = new WP_Query( $args );
				//	echo "<br> QUERY RES: <pre>".print_r($query,true)."</pre>";

					if ( $query ) {
						$found = get_object_value($query,"posts",array());
						$nfound = count($found);
						$this->console("GET Request (Found:".$nfound.")  [".print_r($found,true)."]");

						if ( $found ) {
							foreach($found as $content_id) {

								if ( $multi_language) {
									if ( $translate ) {
										$translations = pll_get_post_translations($content_id);
										$content_id = get_array_value($translations,$translate,"");
									}
								}

								$response["path"]["ids"][]=$content_id;
								$object_id = $content_id;
								$object_type = "post_type";
								
							}
						}
						else {
							if ( $content_id ) {
								$response["path"]["ids"][]="ERROR!(".$content_id.")";
							}
						}

					}


				}

				if ( $object_id ) {

					if ( $object_type == "post_type") {

						$cpt = get_post_type($object_id);

						if ( $cpt ) {
							$request_str =  '/wp/v2/'.$cpt.'/'.$object_id;

							switch ($cpt) {
								case "post":$request_str =  '/wp/v2/posts/'.$object_id;break;
								case "page":$request_str =  '/wp/v2/pages/'.$object_id;break;

							}

							$this->console("GET Request: $request_str [".$object_id."]");

							if ( $request_str ) {
								$request_resp = new WP_REST_Request( 'GET' , $request_str );
								$request_resp = rest_do_request( $request_resp );
							
								$ret=get_object_value($request_resp,"data",false);

								// Embed ?
								if ( $embed ) {
									global $wp_rest_server;
									$ret= $wp_rest_server->response_to_data($request_resp, $embed);
								}

							}
						}
					}
					else if ( $object_type == "taxonomy") {
						$ret["taxonomy"]=$object_id;
					}
				}
				else {
					$ret["error"]=404;
					header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);

				}
			}

			$ret = apply_filters("wpheadless/path/response",$ret);
			
			// $response["path"]["count"]=count(get_array_value($response["path"],"ids",array()));

			if ( get_array_value($ret,"error",false) == "404") {

				$data = apply_filters("wpheadless/error/404",null,$ret);
				$ret =  new WP_REST_Response($data, 404);
			}

			//return $response;
			return $ret;

		}




}