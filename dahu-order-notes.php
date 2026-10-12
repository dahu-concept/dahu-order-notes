<?php
/**
 * Plugin Name:       Dahu Order Notes
 * Plugin URI:        https://dahu-concept.fr
 * Description:       Visually separates the order notes a human actually wrote from the ones WooCommerce and its extensions generate automatically.
 * Version:           2.0.0
 * Author:            Hugo Vial-Jaime
 * Author URI:        mailto:contact@dahu-concept.fr
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       dahu-order-notes
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   9.4
 *
 * @package DahuOrderNotes
 */

defined( 'ABSPATH' ) || exit;

define( 'DAHU_ORDER_NOTES_VERSION', '2.0.0' );
define( 'DAHU_ORDER_NOTES_FILE', __FILE__ );
define( 'DAHU_ORDER_NOTES_PATH', plugin_dir_path( __FILE__ ) );
define( 'DAHU_ORDER_NOTES_SETTINGS_PAGE', 'dahu-order-notes' );

/* -------------------------------------------------------------------------
 * Translations
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	static function () {
		load_plugin_textdomain(
			'dahu-order-notes',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
);

/* -------------------------------------------------------------------------
 * HPOS compatibility
 * ---------------------------------------------------------------------- */

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);

/* -------------------------------------------------------------------------
 * Bootstrap
 * ---------------------------------------------------------------------- */

add_action( 'plugins_loaded', 'dahu_order_notes_bootstrap' );

/**
 * Load the plugin once WooCommerce is confirmed present.
 *
 * @return void
 */
function dahu_order_notes_bootstrap() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'dahu_order_notes_missing_wc_notice' );
		return;
	}

	require_once DAHU_ORDER_NOTES_PATH . 'includes/class-dahu-order-notes-settings.php';
	require_once DAHU_ORDER_NOTES_PATH . 'includes/class-dahu-order-notes-detector.php';
	require_once DAHU_ORDER_NOTES_PATH . 'includes/class-dahu-order-notes-highlighter.php';

	Dahu_Order_Notes_Settings::init();
	Dahu_Order_Notes_Highlighter::init();
}

/**
 * Admin notice shown when WooCommerce is not active.
 *
 * @return void
 */
function dahu_order_notes_missing_wc_notice() {

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Dahu Order Notes requires WooCommerce to be installed and active.', 'dahu-order-notes' )
	);
}

/* -------------------------------------------------------------------------
 * Plugins screen
 * ---------------------------------------------------------------------- */

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( $links ) {

		$settings = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . DAHU_ORDER_NOTES_SETTINGS_PAGE ) ),
			esc_html__( 'Settings', 'dahu-order-notes' )
		);

		array_unshift( $links, $settings );

		return $links;
	}
);

add_filter(
	'plugin_row_meta',
	static function ( $links, $file ) {

		if ( plugin_basename( __FILE__ ) !== $file ) {
			return $links;
		}

		$header = get_file_data( __FILE__, array( 'Version' => 'Version' ) );

		return array(
			sprintf(
				/* translators: %s: plugin version number. */
				esc_html__( 'Version %s', 'dahu-order-notes' ),
				esc_html( $header['Version'] )
			),
			sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( 'https://dahu-concept.fr' ),
				esc_html__( 'By Hugo Vial-Jaime — Dahu-Concept', 'dahu-order-notes' )
			),
			sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( 'https://dahu-concept.fr' ),
				esc_html__( 'Plugin website', 'dahu-order-notes' )
			),
		);
	},
	10,
	2
);
