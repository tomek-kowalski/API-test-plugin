<?php


if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * @link              http://www.kowalski-consulting.com/
 * @since             1.00
 * @package           API TEST PLUGIN
 * 
 * @wordpress-plugin
 * Plugin Name:       API TEST PLUGIN
 * Description:       API TEST PLUGIN - Apart  doing required stuff the plugin also saves the value from form by metabox into postmeta of cpt api-cpt. 
 * Version:           1.00
 * Author:            Tomasz Kowalski
 * Author URI:        https://kowalski-consulting.pl/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       api_test_plugin
 * Date:    		  2023-08-07  
 */


 class API_plugin 
 {

	function __construct()
	{
		$this->define_constants();
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'init', array( $this, 'create_post_type' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action('admin_menu', array($this, 'remove_add_new_submenu'));
		add_action('admin_enqueue_scripts', array($this, 'api_ajax_script'));
		add_action('admin_init', array($this, 'register_metaboxes'));
	}

	public function define_constants()
	{
		define( 'API_PATH', plugin_dir_path( __FILE__ ) );
		define( 'API_URL', plugin_dir_url( __FILE__ ) );
		define( 'API_VERSION', '1.0.0' );
	}
	
	public static function activate(){
		update_option( 'rewrite_rules', '' );
	}

	public static function deactivate(){
		flush_rewrite_rules();
		unregister_post_type( 'api-cpt' );
	}

	public static function uninstall(){

		delete_option( 'manage_options' );

		$posts = get_posts(
			array(
				'post_type' => 'api-cpt',
				'number_posts'  => -1,
				'post_status'   => 'any'
			)
		);

		foreach( $posts as $post ){
			wp_delete_post( $post->ID, true );
		}
	}


	//removig not needed add new cpt. 
	public function remove_add_new_submenu() {
		remove_submenu_page('edit.php?post_type=api-cpt', 'post-new.php?post_type=api-cpt');
	}

	// adding menu page 
	public function add_menu() {

		add_menu_page(
			__('API PLUGIN', 'api_test_plugin'),
			'API TEST',
			'manage_options',
			'api_test_main_menu', 
			array($this, 'render_admin_page'), 
			'dashicons-images-alt2',
			1
		);
	}
	//rendering the form 
	public function render_admin_page() {

		$post_id = 1; 
	
		if (!empty($_POST['API_plugin_nonce']) && isset($_POST['API_plugin_nonce']) && wp_verify_nonce($_POST['API_plugin_nonce'], 'API_plugin_nonce')) {
				$this->save_post($post_id);
		}
	

		?>
		<div id="api-form-wrapper" class="wrap">
			<h1><?php esc_html_e('API Plugin Fullname Test', 'api_test_plugin'); ?></h1>
			<form id="api-form"  method="post" action="">
				<?php wp_nonce_field('API_plugin_nonce', 'API_plugin_nonce'); ?>
				<table class="form-table">
					<tr valign="row">
						<td>Fullname:</td>
						<?php API_plugin::add_inner_meta_boxes($post_id); // including metabox field with static method?> 
						<td>
							<button type="submit" name="API_plugin_submit">Save it</button>
						</td>
					</tr>
				</table>
				<div id="target"></div>
			</form>
	
		</div>
		<?php
		require_once ABSPATH . '/wp-admin/admin-footer.php';
	}
	
	//adding metaboxes 
	public function register_metaboxes($post_type) {

		$post_types = array( 'api-cpt' );
		if ( in_array( $post_type, $post_types ) ) {

        add_meta_box(
            'api_meta_box',
            esc_html__('API CPT Meta Box', 'api_test_plugin'),
            array($this, 'add_inner_meta_boxes'),
            'api-cpt',
            'normal',
            'high'
        );
	}
    }
	//adding inner metavbox field connected with api-cpt 
	public static function add_inner_meta_boxes($post_id) {
		$fullname = get_post_meta($post_id, 'api_cpt_fullname', true);
		$value = isset($fullname) ? esc_html($fullname) : '';
	
		$output = '';
		$output .= '<tr valign="top">';
		$output .= '<td><input type="text" name="fullname" id="fullname" class="regular-text link-text" value="' . $value . '" required/></td>';
		$output .= '</tr>';

	
		echo $output;
	}

	//that's for saving metabox value into postmeta table 
	public function save_post($post_id) {

		if (isset($_POST['API_plugin_nonce']) && wp_verify_nonce($_POST['API_plugin_nonce'], 'API_plugin_nonce')) {
			$new_fullname = isset($_POST['fullname']) ? sanitize_text_field($_POST['fullname']) : '';
			$old_fullname = get_post_meta($post_id, 'api_cpt_fullname', true);
	
			update_post_meta($post_id, 'api_cpt_fullname', $new_fullname);
	
			if ($old_fullname !== $new_fullname) {
				echo '<div class="notice notice-success is-dismissible"><p>Fullname updated.</p></div>';
			} else {
				echo '<div class="notice notice-success is-dismissible"><p>Fullname saved.</p></div>';
			}
		}
	}

	//creating api-cpt post type - that's extra
	public function create_post_type(){
		register_post_type(
			'api-cpt',
			array(
				'public'    => true,
				'supports'  => array( '' ),
				'hierarchical'  => false,
				'show_ui'   => true,
				'show_in_menu'  => false,
				'show_in_admin_bar' => true,
				'show_in_nav_menus' => true,
				'can_export'    => true,
				'has_archive'   => false,
				'exclude_from_search'   => false,
				'publicly_queryable'    => true,
				'show_in_rest'  => true,
			)
		);
	}
	//enqueing jqery script plus localization of script and creating the url object  
	public function api_ajax_script() {
		if(is_admin()) {
		wp_enqueue_script( 'ajax_script',API_URL . '/assets/js/ajax-script.js', array('jquery'), null, false );

		wp_localize_script('ajax_script', 'apiAjax', array(
			'ajax_url' => admin_url('admin-ajax.php'), 
			'nonce' => wp_create_nonce('api_ajax_nonce') 
		));
		}
	}

 }
 //it is comfortable to have it just for flush_rewrite_rules() and  rewrite_rules()  
 if( class_exists( 'API_plugin' ) ){
    register_activation_hook( __FILE__, array( 'API_plugin', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'API_plugin', 'deactivate' ) );
    register_uninstall_hook( __FILE__, array( 'API_plugin', 'uninstall' ) );

    $pm = new API_plugin();
} 
 


