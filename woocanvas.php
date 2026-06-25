<?php
/**
 * Plugin Name:       WooCanvas
 * Plugin URI:        https://github.com/example/woocanvas
 * Description:       Edit WooCommerce product page layouts with the Gutenberg block editor. Works with any classic or block theme.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            WooCanvas Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woocanvas
 * Domain Path:       /languages
 *
 * WC requires at least: 9.4
 * WC tested up to:      10.8
 */

defined( 'ABSPATH' ) || exit;

define( 'WOOCANVAS_VERSION', '0.1.0' );
define( 'WOOCANVAS_PLUGIN_FILE', __FILE__ );
define( 'WOOCANVAS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOOCANVAS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOCANVAS_MIN_WC_VERSION', '9.4' );

// Declare WooCommerce feature compatibility.
add_action( 'before_woocommerce_init', static function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WOOCANVAS_PLUGIN_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', WOOCANVAS_PLUGIN_FILE, true );
	}
} );

require_once WOOCANVAS_PLUGIN_DIR . 'includes/class-cpt-manager.php';
require_once WOOCANVAS_PLUGIN_DIR . 'includes/class-product-renderer.php';
require_once WOOCANVAS_PLUGIN_DIR . 'includes/class-wc-dynamic-tags.php';
require_once WOOCANVAS_PLUGIN_DIR . 'includes/class-block-registrar.php';

register_activation_hook( WOOCANVAS_PLUGIN_FILE, [ 'WooCanvas_CPT_Manager', 'activate' ] );

/**
 * Bootstrap the plugin once all plugins are loaded.
 */
add_action( 'plugins_loaded', static function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>' .
				esc_html__( 'WooCanvas requires WooCommerce to be installed and active.', 'woocanvas' ) .
				'</p></div>';
		} );
		return;
	}

	if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, WOOCANVAS_MIN_WC_VERSION, '<' ) ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>' .
				sprintf(
					/* translators: %s: minimum WooCommerce version */
					esc_html__( 'WooCanvas requires WooCommerce %s or higher.', 'woocanvas' ),
					esc_html( WOOCANVAS_MIN_WC_VERSION )
				) .
				'</p></div>';
		} );
		return;
	}

	( new WooCanvas_CPT_Manager() )->init();
	( new WooCanvas_Product_Renderer() )->init();
	( new WooCanvas_WC_Dynamic_Tags() )->init();
	( new WooCanvas_Block_Registrar() )->init();
} );
