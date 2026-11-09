<?php
/**
 * Plugin Name:       DahuConcept Order Notes for WooCommerce
 * Description:       Visually separates the order notes a human actually wrote from the ones WooCommerce and its extensions generate automatically.
 * Version:           2.1.0
 * Author:            Hugo Vial-Jaime
 * Author URI:        https://dahu-concept.fr
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       dahuconcept-order-notes
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   11.2
 *
 * @package UbacNotes
 */

defined( 'ABSPATH' ) || exit;

define( 'UBAC_NOTES_VERSION', '2.1.0' );
define( 'UBAC_NOTES_FILE', __FILE__ );
define( 'UBAC_NOTES_PATH', plugin_dir_path( __FILE__ ) );
define( 'UBAC_NOTES_SETTINGS_PAGE', 'ubac-notes' );

/* -------------------------------------------------------------------------
 * Translations
 *
 * There is deliberately no load_plugin_textdomain() call here. Since
 * WordPress 4.6 the translations of a plugin hosted on WordPress.org are
 * loaded on demand, and the directory's review asks that the call be removed
 * rather than kept: this plugin requires 6.0, so there is no older WordPress
 * to cover. Translations come from translate.wordpress.org.
 * ---------------------------------------------------------------------- */

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

add_action( 'plugins_loaded', 'ubac_notes_bootstrap' );

/**
 * Load the plugin once WooCommerce is confirmed present.
 *
 * @return void
 */
function ubac_notes_bootstrap() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'ubac_notes_missing_wc_notice' );
		return;
	}

	require_once UBAC_NOTES_PATH . 'includes/class-ubac-notes-settings.php';
	require_once UBAC_NOTES_PATH . 'includes/class-ubac-notes-detector.php';
	require_once UBAC_NOTES_PATH . 'includes/class-ubac-notes-highlighter.php';

	Ubac_Notes_Settings::init();
	Ubac_Notes_Highlighter::init();
}

/**
 * Admin notice shown when WooCommerce is not active.
 *
 * @return void
 */
function ubac_notes_missing_wc_notice() {

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'DahuConcept Order Notes requires WooCommerce to be installed and active.', 'dahuconcept-order-notes' )
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
			esc_url( admin_url( 'admin.php?page=' . UBAC_NOTES_SETTINGS_PAGE ) ),
			esc_html__( 'Settings', 'dahuconcept-order-notes' )
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
				esc_html__( 'Version %s', 'dahuconcept-order-notes' ),
				esc_html( $header['Version'] )
			),
			sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( 'https://dahu-concept.fr' ),
				esc_html__( 'By Hugo Vial-Jaime — Dahu-Concept', 'dahuconcept-order-notes' )
			),
			sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( 'https://dahu-concept.fr' ),
				esc_html__( 'Plugin website', 'dahuconcept-order-notes' )
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
require_once UBAC_NOTES_PATH . 'includes/ubac-welcome.php';

/*
 * The flag, not a redirect: an activation hook that redirects breaks bulk
 * activation and WP-CLI, which activate several plugins in one request. The
 * welcome screen reads the flag on the next page load, where a redirect is
 * safe, and clears it when several plugins were activated together.
 */
register_activation_hook(
	__FILE__,
	static function () {
		Ubac_Welcome::mark_activated( 'dahuconcept-order-notes' );
	}
);

add_action(
	'init',
	static function () {
		Ubac_Welcome::register(
			array(
				'slug'       => 'dahuconcept-order-notes',
				'name'       => __( 'Order notes', 'dahuconcept-order-notes' ),
				'tagline'    => __( 'Makes the notes a human wrote stand out from the ones the shop generated.', 'dahuconcept-order-notes' ),
				'file'       => UBAC_NOTES_FILE,
				'version'    => UBAC_NOTES_VERSION,
				'textdomain' => 'dahuconcept-order-notes',
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
					'next'   => __( 'Next', 'dahuconcept-order-notes' ),
					'done'   => __( 'Done', 'dahuconcept-order-notes' ),
					'skip'   => __( 'Skip', 'dahuconcept-order-notes' ),
					'of'     => __( 'of', 'dahuconcept-order-notes' ),
					'denied' => __( 'You are not allowed to view this page.', 'dahuconcept-order-notes' ),
				),
				'points'     => array(
				array(
					'title' => __( 'Yours first', 'dahuconcept-order-notes' ),
					'text'  => __( 'Private notes somebody typed are highlighted.', 'dahuconcept-order-notes' ),
				),
				array(
					'title' => __( 'Customer notes marked', 'dahuconcept-order-notes' ),
					'text'  => __( 'The ones the customer can see are told apart at a glance.', 'dahuconcept-order-notes' ),
				),
				array(
					'title' => __( 'Your colours', 'dahuconcept-order-notes' ),
					'text'  => __( 'Both are yours to set.', 'dahuconcept-order-notes' ),
				),
				),

				/*
				 * The one-time tour. Every selector points at a control the settings
				 * screen always renders, whatever the saved options are: a step whose
				 * target is missing is skipped without a sound.
				 */
				'tour_screen' => 'woocommerce_page_ubac-notes',
				'tour'        => array(
					array(
						'selector' => 'input[name="ubac_notes_options[enabled]"]',
						'title'    => __( 'Switch it on or off', 'dahuconcept-order-notes' ),
						'text'     => __( 'Hand-written private notes are highlighted while this is ticked.', 'dahuconcept-order-notes' ),
					),
					array(
						'selector' => '#ubac-notes-handwritten-bg',
						'title'    => __( 'Pick your colours', 'dahuconcept-order-notes' ),
						'text'     => __( 'One colour for your notes, one for the customer notes header.', 'dahuconcept-order-notes' ),
					),
					array(
						'selector' => '#ubac-notes-custom-prefixes',
						'title'    => __( 'Teach it your plugins', 'dahuconcept-order-notes' ),
						'text'     => __( 'One phrase per line: notes starting with it count as automatic.', 'dahuconcept-order-notes' ),
					),
				),
			)
		);
	}
);
