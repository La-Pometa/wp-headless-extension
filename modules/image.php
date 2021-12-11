<?php

class WPHeadlessImage extends WPHeadlessModule {

		public function init()
		{
			add_filter('the_content', array(&$this, 'filter_ptags_on_images'), 1000);
		}

		function filter_ptags_on_images($content)
		{
			return preg_replace('/<p>\s*(<responsive-image .*>*.<\/responsive-image>)\s*<\/p>/iU', '\1', $content);
		}


}
