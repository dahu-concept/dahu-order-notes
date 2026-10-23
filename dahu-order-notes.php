<?php
/**
 * Plugin Name:       Dahu Order Notes
 * Plugin URI:        https://dahu-concept.fr
 * Description:       Visually separates the order notes a human actually wrote from the ones WooCommerce and its extensions generate automatically.
 * Version:           2.1.0
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

define( 'DAHU_ORDER_NOTES_VERSION', '2.1.0' );
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

/*
 * The welcome screen, shown once just after activation.
 *
 * Registered here rather than inside the admin include, because the
 * activation flag has to be set whatever else is loaded.
 */
require_once DAHU_ORDER_NOTES_PATH . 'includes/dahu-welcome.php';

/*
 * The flag, not a redirect: an activation hook that redirects breaks bulk
 * activation and WP-CLI, which activate several plugins in one request. The
 * welcome screen reads the flag on the next page load, where a redirect is
 * safe, and clears it when several plugins were activated together.
 */
register_activation_hook(
	__FILE__,
	static function () {
		Dahu_Welcome::mark_activated( 'dahu-order-notes' );
	}
);

add_action(
	'init',
	static function () {
		Dahu_Welcome::register(
			array(
				'slug'       => 'dahu-order-notes',
				'name'       => __( 'Order notes', 'dahu-order-notes' ),
				'tagline'    => __( 'Makes the notes a human wrote stand out from the ones the shop generated.', 'dahu-order-notes' ),
				'file'       => DAHU_ORDER_NOTES_FILE,
				'version'    => DAHU_ORDER_NOTES_VERSION,
				'textdomain' => 'dahu-order-notes',
				'mark'       => array(
					'view'  => '0 0 112 112',
					'paths' => array(
						'M18 6 L74 6 L98 30 L98 106 L18 106 Z',
						'M74 6 L74 30 L98 30',
						'M34 54 L82 54 M34 70 L82 70',
						'M34 86 L62 86',
					),
				),
				/*
				 * The welcome screen's words come from here: the shared file is
				 * copied byte for byte across the whole family, so it can carry
				 * no text domain of its own.
				 */
				'strings'    => array(
					'next'   => __( 'Next', 'dahu-order-notes' ),
					'done'   => __( 'Done', 'dahu-order-notes' ),
					'skip'   => __( 'Skip', 'dahu-order-notes' ),
					'of'     => __( 'of', 'dahu-order-notes' ),
					'denied' => __( 'You are not allowed to view this page.', 'dahu-order-notes' ),
				),
				'points'     => array(
				array(
					'title' => __( 'Yours first', 'dahu-order-notes' ),
					'text'  => __( 'Private notes somebody typed are highlighted.', 'dahu-order-notes' ),
				),
				array(
					'title' => __( 'Customer notes marked', 'dahu-order-notes' ),
					'text'  => __( 'The ones the customer can see are told apart at a glance.', 'dahu-order-notes' ),
				),
				array(
					'title' => __( 'Your colours', 'dahu-order-notes' ),
					'text'  => __( 'Both are yours to set.', 'dahu-order-notes' ),
				),
				),

				/*
				 * The one-time tour. Every selector points at a control the settings
				 * screen always renders, whatever the saved options are: a step whose
				 * target is missing is skipped without a sound.
				 */
				'tour_screen' => 'woocommerce_page_dahu-order-notes',
				'tour'        => array(
					array(
						'selector' => 'input[name="dahu_order_notes_options[enabled]"]',
						'title'    => __( 'Switch it on or off', 'dahu-order-notes' ),
						'text'     => __( 'Hand-written private notes are highlighted while this is ticked.', 'dahu-order-notes' ),
					),
					array(
						'selector' => '#dahu-order-notes-handwritten-bg',
						'title'    => __( 'Pick your colours', 'dahu-order-notes' ),
						'text'     => __( 'One colour for your notes, one for the customer notes header.', 'dahu-order-notes' ),
					),
					array(
						'selector' => '#dahu-order-notes-custom-prefixes',
						'title'    => __( 'Teach it your plugins', 'dahu-order-notes' ),
						'text'     => __( 'One phrase per line: notes starting with it count as automatic.', 'dahu-order-notes' ),
					),
				),
			)
		);
	}
);
