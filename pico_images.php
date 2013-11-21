<?php

/**
 * Reading images from folder. Adapted from existing plugin pico_slider
 *
 * @author Daniel Low
 * @link http://about.me/daniel.low
 * @license http://opensource.org/licenses/MIT
 */
class Pico_Images {

	private $plugin_path;
	private $image_path;
	private $image_ext = '.jpg';
	private $have_images = true;

	public function __construct()
	{
		@include_once(ROOT_DIR .'config.php');
		$base_url = $this->base_url();
		$temp = '/pico/'; // TODO. There should be a better way of keeping track of the folder name.
		$url = str_replace($temp, CONTENT_DIR, $_SERVER["REQUEST_URI"]);
		$url = str_replace(ROOT_DIR, '', $url);
		$url .= 'images/';
		$this->image_path = $url;
		$this->plugin_path = dirname(__FILE__);
	}

	// from pico.php lib
	protected function get_protocol()
	{
		$protocol = 'http';
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'){
			$protocol = 'https';
		}
		return $protocol;
	}

	// from pico.php lib
	protected function base_url()
	{
		global $config;
		if(isset($config['base_url']) && $config['base_url']) return $config['base_url'];
		$url = '';
		$request_url = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
		$script_url  = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : '';
		if($request_url != $script_url) $url = trim(preg_replace('/'. str_replace('/', '\/', str_replace('index.php', '', $script_url)) .'/', '', $request_url, 1), '/');
		$protocol = $this->get_protocol();
		return rtrim(str_replace($url, '', $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']), '/');
	}

	// modified get_files function from the pico.php lib that checks if images directory exists
	private function get_files($directory, $ext = '')
	{
		$array_items = array();
		if (!is_dir($directory)) {
			$this->have_images = false;
			return $array_items;
		}
		if($handle = opendir($directory)){
			while(false !== ($file = readdir($handle))){
				if($file != "." && $file != ".."){
					if(is_dir($directory. "/" . $file)){
						$array_items = array_merge($array_items, $this->get_files($directory. "/" . $file, $ext));
					} else {
						$file = $directory . "/" . $file;
						if(!$ext || strstr($file, $ext)) $array_items[] = preg_replace("/\/\//si", "/", $file);
					}
				}
			}
			closedir($handle);
		}
		return $array_items;
	}

	public function before_render(&$twig_vars, &$twig)
	{
		// assign the images to the twig_vars
		$twig_vars['images'] = $this->get_files($this->image_path, $this->image_ext);
		// have a boolean to indicate whether there are images to show. Allows flexibility for templating
		$twig_vars['have_images'] = $this->have_images;
		foreach ($twig_vars['images'] as &$image) {
			$temp_array = array();
			// lazy link to the image
			$temp_array['url'] = $twig_vars['base_url'].'/'.$image;
			// read the image info and assign the width and height
			$image_info = getimagesize($image);
			$temp_array['width'] = $image_info[0];
			$temp_array['height'] = $image_info[1];
			// strip the folder names and just leave the end piece without the extension
			$temp_array['name'] = preg_replace('/\.(jpg|jpeg|png|gif|webp)/', '', str_replace($this->image_path.'/', '', $image));
			$image = $temp_array;
		}
		return;
	}

}

?>