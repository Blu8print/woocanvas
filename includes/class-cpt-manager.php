<?php
/**
 * Registers the woocanvas_template custom post type, ensures one template post
 * exists, and adds the Product Template submenu under WooCommerce.
 *
 * @package WooCanvas
 */

defined( 'ABSPATH' ) || exit;

/**
 * WooCanvas_CPT_Manager
 */
class WooCanvas_CPT_Manager {

	const POST_TYPE = 'woocanvas_template';
	const OPTION_KEY = 'woocanvas_template_id';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'admin_menu', [ $this, 'add_submenu' ] );
	}

	/**
	 * Register the private CPT used to store the product page block layout.
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'             => [
					'name'          => __( 'Product Templates', 'woocanvas' ),
					'singular_name' => __( 'Product Template', 'woocanvas' ),
					'edit_item'     => __( 'Edit Product Template', 'woocanvas' ),
				],
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'show_in_rest'       => true,
				'supports'           => [ 'editor' ],
				'capability_type'    => 'page',
			]
		);
	}

	/**
	 * Add "Product Template" submenu under the WooCommerce admin menu.
	 * Clicking it redirects straight to the block editor for the template post.
	 */
	public function add_submenu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Product Template', 'woocanvas' ),
			__( 'Product Template', 'woocanvas' ),
			'manage_woocommerce',
			'woocanvas-template',
			[ $this, 'redirect_to_editor' ]
		);
	}

	/**
	 * Redirect the submenu page request to the block editor for the template post.
	 */
	public function redirect_to_editor(): void {
		$post_id = $this->get_or_create_template_post();
		wp_safe_redirect( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) );
		exit;
	}

	/**
	 * Returns the ID of the single template post, creating it if it does not exist.
	 */
	public static function get_or_create_template_post(): int {
		$post_id = (int) get_option( self::OPTION_KEY, 0 );

		if ( $post_id && get_post( $post_id ) instanceof WP_Post ) {
			return $post_id;
		}

		$post_id = wp_insert_post(
			[
				'post_type'   => self::POST_TYPE,
				'post_title'  => __( 'Product Template', 'woocanvas' ),
				'post_status' => 'publish',
				'post_content' => '',
			]
		);

		update_option( self::OPTION_KEY, $post_id, false );

		return $post_id;
	}

	/**
	 * Activation hook — create the template post early so it's ready immediately.
	 */
	public static function activate(): void {
		// Register the post type so wp_insert_post knows about it.
		( new self() )->register_post_type();
		self::get_or_create_template_post();
	}
}
