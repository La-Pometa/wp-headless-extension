<?php

add_filter("wpheadless/modules/load","wpheadless_sitemap_load_module");
function wpheadless_sitemap_load_module($modules) {
    $modules["sitemap"]="WPHeadlessSitemap";
    return $modules;
}


class WPHeadlessSitemap extends WPHeadlessModule {

		public function __construct()
		{
			parent::__construct();
            $this->site_url=false;
            add_action("wpheadless/routes/new",array($this,"init_routes"));
        }


		function init_routes() {

            $this->console("Loading Route [sitemap]");

			register_rest_route( 'wp/v2', '/sitemap/', array(
		        'methods' => 'GET',
		        'callback' => array($this,"get_settings_response"),
                'permission_callback' => '__return_true',
		        'args' => array(
		            'slug' => array (
		                'required' => false
		            )
		        )
		    ) );
		}

        function get_slug_by_url($url) {
            if ( $this->site_url==false) {
                $this->site_url=site_url();
            }

            $slug = str_replace($this->site_url,"",$url);
            if ( substr($slug,0,1)=="/") {
                $slug = substr($slug,1,strlen($slug)-1);
            }
            if ( substr($slug,strlen($slug)-1,1)=="/"){
                $slug = substr($slug,0,strlen($slug)-1);
            }
            return $slug;

        }
        function get_settings_response(WP_REST_Request $request ) {
            $_req=array("sitemap"=>array());
            $site_url = site_url();

            $args = array(
                "post_type"=>array("post","page","landing","projecte"),
                "post_status"=>array("publish"),
                "fields"=>"ids",
                "posts_per_page"=>-1,
                "orderby"=>"post_type",
                "order"=>"asc"
            );

            $pages = new WP_Query($args);

            if ( $pages ) {
                $_req["items"]=array();
                $posts = get_object_value($pages,"posts",array());
                foreach($posts as $pos => $post_id) {
                    $slug = $this->get_slug_by_url(get_permalink($post_id));
                    if ( substr($slug,strlen($slug)-1,1)=="/"){$slug=substr_chop($slug);}
                    $item=array(
                        "id"=>$post_id,
                        "cpt"=>get_post_type($post_id),
                        "title"=>get_the_title($post_id)
                    );

                    if ( function_exists("pll_get_post_translations")) {
                        $translations = pll_get_post_translations($post_id);


                        $item["lang"]=pll_get_post_language($post_id, 'slug');

                        $trans = array_reduce($translations, function ($carry, $translation) {
                            $site_url = site_url();
                            $item = array(
                                'lang' => pll_get_post_language($translation, 'slug'),
                                'slug' => $this->get_slug_by_url(get_permalink($translation)),
                            );
                            array_push($carry, $item);
            
                            return $carry;
                        }, array());


                        $item["trans"]=$trans;
                    }

                    $_req["items"][$slug]=$item;

                }

                $_req = $_req["items"];
            }

            return $_req;
        }
}
