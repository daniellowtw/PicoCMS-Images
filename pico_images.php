<?php

/**
 * Reading images from folder. Adapted from existing plugin pico_slider
 *
 * @author Daniel Low
 * @link http://about.me/daniel.low
 * @license http://opensource.org/licenses/MIT
 */
class Pico_Images extends AbstractPicoPlugin {
  /**
   * API version used by this plugin
   *
   * @var int
   */
  const API_VERSION = 3;

  /**
   * This plugin is disabled by default
   *
   * Usually you should remove this class property (or set it to NULL) to
   * leave the decision whether this plugin should be enabled or disabled by
   * default up to Pico. If all the plugin's dependenies are fulfilled (see
   * {@see DummyPlugin::$dependsOn}), Pico enables the plugin by default.
   * Otherwise the plugin is silently disabled.
   *
   * If this plugin should never be disabled *silently* (e.g. when dealing
   * with security-relevant stuff like access control, or similar), set this
   * to TRUE. If Pico can't fulfill all the plugin's dependencies, it will
   * throw an RuntimeException.
   *
   * If this plugin rather does some "crazy stuff" a user should really be
   * aware of before using it, you can set this to FALSE. The user will then
   * have to enable the plugin manually. However, if another plugin depends
   * on this plugin, it might get enabled silently nevertheless.
   *
   * No matter what, the user can always explicitly enable or disable this
   * plugin in Pico's config.
   *
   * @see AbstractPicoPlugin::$enabled
   * @var bool|null
   */
  protected $enabled = true;

  /**
   * This plugin depends on ...
   *
   * If your plugin doesn't depend on any other plugin, remove this class
   * property.
   *
   * @see AbstractPicoPlugin::$dependsOn
   * @var string[]
   */
  protected $dependsOn = array();
	private $plugin_path;
	private $image_path;
  private $page_id;
  private $config_assets;
	private $image_ext = '.jpg';
	private $have_images = true;

  /**
   * Triggered when Pico discovered the current, previous and next pages
   *
   * If Pico isn't serving a regular page, but a plugin's virtual page, there
   * will neither be a current, nor previous or next pages. Please refer to
   * {@see Pico::readPages()} for information about the structure of a single
   * page's data.
   *
   * @see Pico::getCurrentPage()
   * @see Pico::getPreviousPage()
   * @see Pico::getNextPage()
   *
   * @param array|null &$currentPage  data of the page being served
   * @param array|null &$previousPage data of the previous page
   * @param array|null &$nextPage     data of the next page
   */
  public function onCurrentPageDiscovered(
      array &$currentPage = null,
      array &$previousPage = null,
      array &$nextPage = null
  ) {
    $this->page_id = $currentPage['id'];
  }

  /**
   * Triggered after Pico has read its configuration
   *
   * @see Pico::getConfig()
   * @see Pico::getBaseUrl()
   * @see Pico::isUrlRewritingEnabled()
   *
   * @param array &$config array of config variables
   */
  public function onConfigLoaded(array &$config)
  {
    $this->config_assets['dir'] = $config['assets_dir'];
    $this->config_assets['url'] = $config['assets_url'];
  }

	// public function __construct()
	// {
	// 	@include_once(ROOT_DIR .'config.php');
	// 	$base_url = $this->base_url();
	// 	$temp = '/pico/'; // TODO. There should be a better way of keeping track of the folder name.
	// 	$url = str_replace($temp, CONTENT_DIR, $_SERVER["REQUEST_URI"]);
	// 	$url = str_replace(ROOT_DIR, '', $url);
	// 	$url .= 'images/';
	// 	$this->image_path = $url;
	// 	$this->plugin_path = dirname(__FILE__);
	// }

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

  /**
   * Triggered before Pico renders the page
   *
   * @see DummyPlugin::onPageRendered()
   *
   * @param string &$templateName  file name of the template
   * @param array  &$twigVariables template variables
   */
  public function onPageRendering(&$templateName, array &$twigVariables)
  {
    // assign the images to the twigVariables
    $this->image_path = $this->config_assets['dir'].$this->page_id;
    $image_url = $this->config_assets['url'].$this->page_id;
    $twigVariables['images'] = $this->get_files($this->image_path, $this->image_ext);
    // have a boolean to indicate whether there are images to show. Allows flexibility for templating
    $twigVariables['have_images'] = $this->have_images;
    foreach ($twigVariables['images'] as &$image) {
      $temp_array = array();
      // read the image info and assign the width and height
      $image_info = getimagesize($image);
      $temp_array['width'] = $image_info[0];
      $temp_array['height'] = $image_info[1];
      // strip the folder names and just leave the end piece without the extension
      $temp_array['name'] = preg_replace('/\.(jpg|jpeg|png|gif|webp)/', '', str_replace($this->image_path.'/', '', $image));
      $temp_array['name_full'] = str_replace($this->image_path.'/', '', $image);
      $temp_array['url'] = $image_url.'/'.$temp_array['name_full'];
      $image = $temp_array;
    }
    return;
  }

}

?>
