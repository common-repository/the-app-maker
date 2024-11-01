<?php
class TheAppMaker {
	/**
	 * Static property to hold our singleton instance
	 */
	static $instance = false;
	
	/**
	 * Variable to hold a reference to the post object
	 */
	var $post;	

	/**
	 * Variable to hold a reference to the parameters object
	 */
	var $parms;	
	
	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return TheAppMaker
	 */
	private function __construct() {
		$this->init();
		// Fires only if in WordPress Admin Area, do some actions
		if (is_admin()){
			//add_action('init',array(&$this,'wp_init));
		}
		// Other actions to always perform.
		//add_action('template_redirect',array(&$this,'template_redirect'));
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return TheAppMaker
	 */
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/**
	 * Simply instantiates the singleton
	 *
	 * @return void
	 */
	public function instantiate() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return void;
	}
	
	private function init(){
		$this->parms = array();
		add_shortcode('the_app',array(&$this,'shortcodes'));
		add_shortcode('app_item',array(&$this,'shortcodes'));
		add_shortcode('app_item_wrapper',array(&$this,'shortcodes'));
		add_shortcode('app_posts',array(&$this,'shortcodes'));
		
		add_filter('TheAppMaker_models',array(&$this,'addRegisteredModels'),10,2);
		add_filter('TheAppMaker_stores',array(&$this,'addRegisteredStores'),10,2);		

		do_action_ref_array('TheAppMaker_init',array(& $this));
	}
	
	private function reset(){
		$this->parms = array();
		$this->set('transition','slide');
		$this->set('items',array());
		$this->set('registered_post_queries',array());
		$this->set('query_defaults',array(
			'author' => '',
			'author_name' => '',
			'cat' => '',
			'category_name' => '',
			'category__and' => '',
			'category__in' => '',
			'category__not_in' => '',
			'tag' => '',
			'tag_id' => '',
			'tag__and' => '',
			'tag__in' => '',
			'tag__not_in' => '',
			'tag_slug__and' => '',
			'tag_slug__in' => '',
			'p' => '',
			'name' => '',
			'page_id' => '',
			'pagename' => '',
			'post_parent' => '',
			'post__in' => '',
			'post__not_in' => '',
			'post_type' => 'post',
			'post_status' => '',
			'order' => 'ASC',
			'orderby' => 'title',
			'year' => '',
			'monthnum' => '',
			'w' => '',
			'day' => '',
			'hour' => '',
			'minute' => '',
			'second' => '',
			'meta_key' => '',
			'meta_value' => '',
			'meta_compare' => '',
		));
	}
	
	public function get($what=null){
		if ($what != null){
			return $this->parms[$what];			
		}
		else{
			return $this->parms;
		}
	}
	
	public function set($what,$value){
		$this->parms[$what] = $value;
	}
	
	public function setup($post){
		// reset the app
		$this->reset();
		
		// the post is the object that defines the app
		// though this is setup to work with WP posts as default
		// I'm keeping it open to allow other ways to instantiate 
		// an app
		$this->set('post',$post);
		if (isset($post->ID)){
			$this->is('wordpress_post',true);
		}
		else{
			$this->is('wordpress_post',false);
		}
		
		if ($this->is('wordpress_post')){
			// Let's see if there are any custom images to use for the app (startups and icon)
			$p = $this->get('post');
			$attachments = get_children( array( 'post_parent' => $p->ID, 'post_type' => 'attachment', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC') );
			if (is_array($attachments) and count($attachments)){
				$app_attachments_types = apply_filters('TheAppMaker_attachments_types',array('startup_phone','startup_tablet','icon','stylesheet'),array(& $this));
				foreach ($attachments as $attachment){
					foreach ($app_attachments_types as $type){
						if (strpos($attachment->post_title,$type) === 0 and !$this->get($type)){
							$this->set($type,$attachment->guid);
						}
					}
				}
			}
		}
		// This is the mothership, where the app calls to get data
		$this->set('mothership',apply_filters('TheAppMaker_mothership',trailingslashit(get_permalink()),array(&$this)));
		
		$this->set('app_id',substr(md5($this->get('mothership')),0,5)); // a unique app_id
		
		// second, setup the parms based on the shortcodes within the post
		$this->parsePost();
		
		$this->setupModels();
		$this->setupStores();
		$this->setupScripts();
		//$this->setupItems();
	}
	
	public function is($what,$value = null){
		if (isset($value)){
			$this->set('_is_'.$what,$value);
		}
		return $this->get('_is_'.$what);
	}
	
	public function parsePost(){
		$post = $this->get('post');
		if ($this->is('wordpress_post')){
			$this->set('title',$post->post_title);
			do_shortcode($post->post_content);
		}
		do_action_ref_array('TheAppMaker_parsePost',array(& $this));
	}

	public function shortcodes($atts = array(),$content = null,$code = ''){
		if (!is_array($atts)){
			$atts = array();
		}
		$original_atts = $atts;
		$atts = $this->sanitize_atts($atts,$code); // calls @shortcode_atts
		switch($code){
		case 'the_app':
			foreach ($atts as $key => $att){
				if (substr($key,0,4) == '_is_'){
					$this->set($key,($att == 'true' ? true : false));
				}
				else{
					$this->set($key,$att);
				}
			}
			break;
		case 'app_item_wrapper':
			$this->is('capturing',true);
			$this->set('captured_items',array());
			do_shortcode($content);
			$this->is('capturing',false);
			$captured = $this->get('captured_items');
			$atts['pages'] = array();
			if (is_array($captured)){
				foreach ($captured as $item){
					$atts['pages'][] = array(
						'title' => $item['title'],
						'card' => $item
					);
				}
			}
			$this->addWrapperItem($atts);
			break;
		case 'app_item':
			switch(true){
			case (isset($atts['callback']) and function_exists($atts['callback'])):
				$callback = $atts['callback'];
				call_user_func_array($atts['callback'],array($original_atts)); // pass the original, so there's no filtering.
				break;
			case (isset($atts['post_type'])):
				$this->addPostListItem($atts);
				break;
			case (isset($atts['post']) and is_numeric($atts['post'])):
				$p = get_post($atts['post']);
				if ($p){
					$atts['title'] = $p->post_title;
					$atts['content'] = do_shortcode($p->post_content);
					$this->addHTMLItem($atts);
				}
				break;
			case (isset($content) and $content != ' '):
				$atts['content'] = do_shortcode($content);
				$this->addHTMLItem($atts);
				break;
			}
			break;
		case 'app_posts':
			if ($atts['orderby'] == 'date'){
				// 'date' is formatted as "Jan 10 2011", which doesn't sort as well as 2011-01-10
				$atts['orderby'] = 'date_gmt';
			}
			$this->addPostListItem($atts);
		}
	}
	
	function sanitize_atts($atts = array(),$shortcode){
		$defaults = $this->get_default_atts($shortcode);
		$return = shortcode_atts($defaults,$atts);
		return $return;
	}
		
	function get_default_atts($shortcode){
		$defaults = array();
		switch($shortcode){
		case 'the_app':
			$defaults = array(
				'_is_debug_on' => 'false',	// sends Javascript messages to console.log() via a maybelog() call. Loads debug version of Sencha library.
				'_is_using_manifest' => 'false',	// saves data to the user's device. An app gets 5MB for free without having to ask the user for permission.
				'transition' => 'slide',	// slide (default), fade, pop, flip, cube, wipe (buggy) --+ the CSS3 transition to use between pages
				'manifest_version' => '',	// a version number for the manifest file.  Useful for forcing new manifest load. 
				'sencha' => '1.0'			// 1.0 or 2.0pr --+ the version of Sencha Touch to use 
			);
			break;
		case 'app_item':
			$defaults = array(
				'_is_default' => 'false',	// makes this item the first one that appears.
				'title' => '', 				// the title of page. Also the title on the bottom toolbar icon.
				'icon' => 'star',			// action, add, arrow_down, arrow_left, arrow_right, arrow_up, compose, delete, organize, refresh, reply, search, settings, star (default), trash, maps, locate, home
				'post' => null,				// $post->ID of any WordPress post (optional)
				'callback' => null			// a function to call to setup the page. Gives developers finer control
			);
			break;
		case 'app_item_wrapper':
			$defaults = array(
				'_is_default' => 'false',	// makes this item the first one that appears.
				'title' => '', 				// the title of page. Also the title on the bottom toolbar icon.
				'icon' => 'star'			// action, add, arrow_down, arrow_left, arrow_right, arrow_up, compose, delete, organize, refresh, reply, search, settings, star (default), trash, maps, locate, home
			);
			break;
		case 'app_posts':
			$defaults = array(
				'_is_default' => 'false',	// makes this item the first one that appears.
				'title' => '', 				// the title of page. Also the title on the bottom toolbar icon.
				'icon' => 'star',			// action, add, arrow_down, arrow_left, arrow_right, arrow_up, compose, delete, organize, refresh, reply, search, settings, star (default), trash, maps, locate, home
				'post_type' => 'post',		// any post_type, including custom.  If you're debugging and getting 404's read the FAQ at http://wordpress.org/extend/plugins/the-app-maker
				'grouped' => 'true',		// whether to create group headers
				'group_by' => 'first_letter', 	// first_letter, category, month
				'indexbar' => 'true',		// whether to create index bar
				'orderby' => 'title',		// what to sort the posts on
				'order' => 'ASC',			// the direction
				// the Sencha tpl for the list item
				'list_template' => 	'<div class="avatar"<tpl if="thumbnail"> style="background-image: url({thumbnail})"</tpl>></div><span class="name">{title}</span>',
				// the Sencha tpl for the detail page
				'detail_template' => '<tpl if="thumbnail"><img class="thumbnail" src="{thumbnail}"></tpl></div><h3>{title}</h3> {content}'
			);
			
			// Add in anything allowed by @get_posts();
			$get_post_defaults = WP_Query::fill_query_vars($atts);
			$defaults = array_merge($get_post_defaults,$defaults);
			break;
		}
		
		return apply_filters('the_app_shortcode_defaults',$defaults,$shortcode);
	}
	
	function addHTMLItem($atts){
		// HTML pages are easy
		$item_defaults = array(
			'xtype' => 'htmlpage',
			'icon' => 'star',
			'title' => '',
			'content' => ''
		);
		$meta_defaults = array(
			'_is_default' => 'false'
		);
		$this->addItem(shortcode_atts($item_defaults,$atts),shortcode_atts($meta_defaults,$atts));
	}
	
	function addWrapperItem($atts){
		$item_defaults = array(
			'xtype' => 'itemwrapper',
			'icon' => 'info',
			'title' => '',
			'pages' => array()
		);
		$meta_defaults = array(
			'_is_default' => 'false'
		);
		$this->addItem(shortcode_atts($item_defaults,$atts),shortcode_atts($meta_defaults,$atts));
	}
	
	function addPostListItem($atts){
		$query_defaults = $this->get('query_defaults'); 
		$query_atts = $this->simplify_atts(shortcode_atts($query_defaults,$atts));
		
		$item_defaults = array(
			'xtype' => $query_atts['post_type'],
			'icon' => 'star',
			'title' => 'Posts',
			'listeners' => array('afterrender' => array('fn' => $this->do_not_escape('this.setupTabBarClickEvent'), 'scope' => $this->do_not_escape('this')))
		);
		$meta_defaults = array(
			'_is_default' => 'false',
			'store' => $query_atts['post_type'],
			'title' => 'Posts',
			'query_vars' => $query_atts,
			'grouped' => 'true',
			'group_by' => 'first_letter',
			'indexbar' => 'true',
			'list_template' => '<div class="avatar"<tpl if="thumbnail"> style="background-image: url({thumbnail})"</tpl>></div><span class="name">{title}</span>',
			'detail_template' => '<tpl if="thumbnail"><img class="thumbnail" src="{thumbnail}"></tpl></div><h3>{title}</h3> {content}'
		);
		
		$item_atts = shortcode_atts($item_defaults,$atts);
		$meta_atts = shortcode_atts($meta_defaults,$atts);
		
		$index = $this->registerPostQuery($meta_atts);
		$item_atts['query_instance'] = $index;
		
		$this->addItem($item_atts,$meta_atts);
	}
	
	function addItem($atts,$meta = array()){
		$new_item = array();
		if (isset($atts['icon'])){
			$atts['iconCls'] = $atts['icon'];
		}
		$new_item = array('item' => $atts);
		if (count($meta)){
			$new_item['meta'] = $meta;
		}

		if ($new_item['meta']['_is_default'] == 'true'){
			$items = $this->get('items');
			array_unshift($items,$new_item);
			$this->set('items',$items);
		}
		elseif ($this->is('capturing')){
			$captured_items = $this->get('captured_items');
			$captured_items[] = $new_item['item'];
			$this->set('captured_items',$captured_items);
		}
		else{
			$new_item['item']['listeners'] = array('afterrender' => array('fn' => $this->do_not_escape('this.setupTabBarClickEvent'), 'scope' => $this->do_not_escape('this')));
			$items = $this->get('items');
			$items[] = $new_item;

			$this->set('items',$items);
		}
		
		
	}
	
	private function setupModels(){
		$models = array();
				
		if ($this->is('using_manifest')){
			$models['StoreStatus'] = array('fields' => array('id','store','timestamp'));
		}
		
		$this->set('models',apply_filters('TheAppMaker_models',$models,array(&$this)));
	}
	
	private function registerPostQuery($meta_atts){
		$registered_post_queries = $this->get('registered_post_queries');
		if (isset($meta_atts['query_vars']['post_type'])){
			$post_type = $meta_atts['query_vars']['post_type'];
		}
		elseif(isset($meta_atts['query_vars']['xtype'])){
			$post_type = $meta_atts['query_vars']['xtype'];
		}
		
		if (!isset($post_type)){
			die(__('Attempting to register a list without a post type.  Please set either $atts[query_vars][post_type] or $atts[query_vars][xtype] when calling TheAppMaker::addPostListItem','app-maker'));
		}
		
		if (!array_key_exists($post_type,$registered_post_queries)){
			$registered_post_queries[$post_type] = array();
		}
		$registered_post_queries[$post_type][] = $meta_atts;
		$this->set('registered_post_queries',$registered_post_queries);
		return (count($registered_post_queries[$post_type]) - 1); // the query instance index
	}
	
	function addRegisteredModels($models,$_this){
		if (is_array($this->get('registered_post_queries'))){
			
			foreach ($this->get('registered_post_queries') as $post_type => $registered_meta){
				
				$callback_exists = false;
				foreach($registered_meta as $query_instance => $registered_query){
					if (isset($registered_query['query_vars']['xtype']) and isset($registered_query['query_vars']['model_callback'])){
						$xtype = $registered_query['query_vars']['xtype'];
						$models[$xtype] = call_user_func($registered_query['query_vars']['model_callback'],$xtype);
						if (!in_array('query_num',$models[$xtype]['fields'])){
							$models[$xtype]['fields'][] = 'query_num';
						}
						$callback_exists = true;
					}
				}
				if ($callback_exists){
					continue;
				}
				$models[$post_type] = array('fields' => array());
				// common parms are parms that are common to all post types
				// We'll just get them from the app post  
				foreach ($this->get('post') as $field => $value){
					$field = $this->sanitize_key($field);
					$models[$post_type]['fields'][] = $field;
				}

				// Now, we need to get all custom fields that exist for posts of this type
				// Turns out to be tricker than I thought.  I don't know if WP has something 
				// native for this...
				$meta_keys = $this->lookupCustomFields($post_type);
				foreach ($meta_keys as $key){
					$models[$post_type]['fields'][] = $key;
				}
				
				// Finally, let's add one for the thumbnail
				$models[$post_type]['fields'][] = 'thumbnail';
				
				// If there are more than one queries registered for this post_type,
				// we'll add another field which will indicate which registered_query
				// we're dealing with
				$models[$post_type]['fields'][] = 'query_num';
				
				// Add for category
				$models[$post_type]['fields'][] = 'category';
				$models[$post_type]['fields'][] = 'spoof_id'; // allows a single post to appear under more than one category
			}
		}
		return $models;
	}
	
	public function lookupCustomFields($post_type){
		global $wpdb;
		static $query;
		if (!isset($query)){
			$query = "SELECT DISTINCT `meta_key` FROM $wpdb->postmeta where `post_id` in (SELECT ID FROM $wpdb->posts WHERE `post_type` = %s)";			
		}
		$meta_keys = $wpdb->get_col($wpdb->prepare($query, $post_type));
		foreach ($meta_keys as $k => $key){
			if (substr($key,0,1) == '_'){
				unset($key->$k);
			}
		}
		return $meta_keys;
	}
	
	private function setupStores(){
		$stores = array();
		
		if ($this->is('using_manifest')){
			$stores['StoreStatusStore'] = array();
			$stores['StoreStatusStore']['model'] = 'StoreStatus';
			$stores['StoreStatusStore']['offline_enable'] = true;
			$stores['StoreStatusStore']['proxy'] = array(
				'type' => 'scripttag',
				'url' => $this->get('mothership').'data/storemeta',
				'reader' => array('type' => 'json', 'root' => 'stores')
			);
		}
		
		$stores['WrapperStore'] = array();
		$stores['WrapperStore']['fields'] = array('name', 'card');
		
		$this->set('stores',apply_filters('TheAppMaker_stores',$stores,array(&$this)));
	}
	
	function addRegisteredStores($stores,$_this){
		if (is_array($this->get('registered_post_queries'))){
			foreach ($this->get('registered_post_queries') as $post_type => $registered_meta){
				$store_name = "{$post_type}Store";
				$stores[$store_name] = array();
				$stores[$store_name]['model'] = $post_type;

				if ($this->is('using_manifest')){
					$stores[$store_name]['offline_enable'] = true;
				}
				$stores[$store_name]['proxy'] = array(
					'type' => 'scripttag',
					'url' => $this->get('mothership').'data/'.$post_type.'/', // Note trailing slash, necesasry to avoid Status: 301 calls (which merely add a slash)
					'reader' => array('type' => 'json', 'root' => $post_type)
				);
				$stores[$store_name]['autoload'] = true;
			}
		}
		return $stores;
	}

	private function setupScripts(){
		$scripts = array();
		$scripts[] = 'src/index.js.php';
		$scripts[] = 'src/Models.js.php';
		$scripts[] = 'src/Stores.js.php';
		$scripts[] = 'src/App.js.php';
		$scripts[] = 'src/ItemsList.js.php';
		$scripts[] = 'src/ItemsDetail.js.php';
		$scripts[] = 'src/ItemWrapper.js.php';
		$scripts[] = 'src/HtmlPage.js.php';
		$scripts[] = 'src/SplashScreen.js.php';
		
		$this->set('scripts',apply_filters('TheAppMaker_scripts',$scripts,array(&$this)));
	}
	
	public function do_not_escape($text){
		return '__dne__'.$text.'__dne__';
	}

	public function anti_escape($text){
		return preg_replace('/\"?'.'__dne__'.'\"?/','',$text);
	}
	
	public function simplify_atts($atts){
		return array_filter($atts,create_function('$v','return ($v != "");'));
	}
	
	public function sanitize_key($key){
		$key = preg_replace('/^post_/','',$key);
		$key = strtolower($key);  // strtolower so ID turns to id
		return $key;
	}
	
	public function getPostImages($post_id,$index = null){
		$images = get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order', 'order' => 'ASC', 'numberposts' => 999 ) );
		$output = false;
		if ( $images ) {
			$output = array();
			foreach ($images as $image){
				$image_img_tag = wp_get_attachment_image_src( $image->ID, 'thumbnail' );
				$output[] = $image_img_tag[0];
			}
			if (is_numeric($index)){
				return $output[$index];
			}
		}
		return $output;
		
	}
	
	function the_items(){
		$the_items = $this->get('items');
		$items = array();
		if (is_array($the_items)){
			foreach ($the_items as $item){
				$items[] = $item['item'];
			}
		}
		return $items;
	}
	
}
?>