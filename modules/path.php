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


				add_filter("wpheadless/request/type/filter",array($this,"_request_type"),20,2);
        }


		function _request_type($type,$call) {

			if ( $call == "path") {
				$type = "single";
			}

			return $type;
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
			$content="";
			$ret= array();

			
			$embed = false;
			if ( get_array_value($_GET,"_embed",false) !== false ) {
				 $embed = true;
			}


			if ( !$slug ) {

				$post_id = apply_filters("wpheadless/rest/path/frontpage",false);
				// $response["path"]["frontpage"]=$post_id;

				if ( !$post_id ) {
					$post_id = get_option('page_on_front');
				}


				if ( $post_id ) {
					$post_id = pll_get_post($post_id,$lang);

					$request = new WP_REST_Request( 'GET', '/wp/v2/pages/'.$post_id  , $_GET);

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

				$args = array( 'post_type' => 'any','name'=>$slug, 'fields'=> 'ids' ,'lang' => $lang);

				$content_id = false;
				$query = new WP_Query( $args );
				if ( $query ) {
					$found = get_object_value($query,"posts",array());
					$nfound = count($found);
					if ( $found ) {
						foreach($found as $content_id) {

							if ( $translate ) {
								$translations = pll_get_post_translations($content_id);
								$content_id = get_array_value($translations,$translate,"");
							}


							$response["path"]["ids"][]=$content_id;




							$cpt = get_post_type($content_id);
							$request_str =  '/wp/v2/'.$cpt.'/'.$content_id;

							switch ($cpt) {
								case "post":$request_str =  '/wp/v2/posts/'.$content_id;break;
								case "page":$request_str =  '/wp/v2/pages/'.$content_id;break;

							}


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
					else {
						if ( $content_id ) {
							$response["path"]["ids"][]="ERROR!(".$content_id.")";
						}
					}
				
				}
			}


			// $response["path"]["count"]=count(get_array_value($response["path"],"ids",array()));

			//return $response;
			return $ret;

		}




}