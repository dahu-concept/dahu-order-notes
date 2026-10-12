<?php
/**
 * Order-screen highlighting.
 *
 * @package DahuOrderNotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tags automatic notes and injects the highlighting stylesheet.
 */
class Dahu_Order_Notes_Highlighter {

	/**
	 * CSS class added to notes identified as automatic.
	 *
	 * @var string
	 */
	const AUTO_NOTE_CLASS = 'dahu-auto-note';

	/**
	 * Hook everything up.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'woocommerce_order_note_class', array( __CLASS__, 'flag_auto_notes' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	/**
	 * Add the automatic-note class where it applies.
	 *
	 * Notes WooCommerce already categorises natively — customer notes and
	 * system notes — are left alone; they are distinguishable without help.
	 *
	 * @param array  $css_class Classes computed by WooCommerce.
	 * @param object $note      Note object.
	 * @return array
	 */
	public static function flag_auto_notes( $css_class, $note ) {

		$css_class = (array) $css_class;

		if ( in_array( 'customer-note', $css_class, true ) || in_array( 'system-note', $css_class, true ) ) {
			return $css_class;
		}

		$content = isset( $note->content ) ? $note->content : '';

		if ( Dahu_Order_Notes_Detector::is_automatic( $content ) ) {
			$css_class[] = self::AUTO_NOTE_CLASS;
		}

		return $css_class;
	}

	/**
	 * Is the current admin screen an order screen?
	 *
	 * Covers both the legacy post table and HPOS.
	 *
	 * @return bool
	 */
	private static function is_order_screen() {

		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		$order_screens = array( 'shop_order', 'woocommerce_page_wc-orders' );

		/**
		 * Filters the admin screen ids the highlighting applies to.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $order_screens Screen identifiers.
		 */
		$order_screens = (array) apply_filters( 'dahu_order_notes_order_screens', $order_screens );

		return in_array( $screen->id, $order_screens, true );
	}

	/**
	 * Register and enqueue the inline stylesheet on order screens only.
	 *
	 * @return void
	 */
	public static function enqueue_styles() {

		$options = Dahu_Order_Notes_Settings::get();

		if ( empty( $options['enabled'] ) || ! self::is_order_screen() ) {
			return;
		}

		$handwritten = sanitize_hex_color( $options['handwritten_bg'] );
		$customer    = sanitize_hex_color( $options['customer_header_bg'] );

		$defaults    = Dahu_Order_Notes_Settings::defaults();
		$handwritten = $handwritten ? $handwritten : $defaults['handwritten_bg'];
		$customer    = $customer ? $customer : $defaults['customer_header_bg'];

		wp_register_style( 'dahu-order-notes', false, array(), DAHU_ORDER_NOTES_VERSION );
		wp_enqueue_style( 'dahu-order-notes' );

		$css = sprintf(
			/* Private notes a human typed: not a customer note, not a system note, not machine-generated. */
			'.order_notes li.note:not(.customer-note):not(.system-note):not(.%1$s) .note_body{background:%2$s;box-shadow:rgba(0,0,0,.24) 0 3px 8px;}' .
			'.order_notes li.note.customer-note .note_header{background:%3$s;}',
			esc_attr( self::AUTO_NOTE_CLASS ),
			$handwritten,
			$customer
		);

		wp_add_inline_style( 'dahu-order-notes', $css );
	}
}
