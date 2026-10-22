<?php
/**
 * Settings screen.
 *
 * @package DahuOrderNotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores and renders the plugin options.
 */
class Dahu_Order_Notes_Settings {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	const OPTION = 'dahu_order_notes_options';

	/**
	 * Settings group.
	 *
	 * @var string
	 */
	const GROUP = 'dahu_order_notes_group';

	/**
	 * Capability required to view and save the settings.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_woocommerce';

	/**
	 * Hook everything up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Default option values.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'enabled'            => 1,
			'handwritten_bg'     => '#f8ffd7',
			'customer_header_bg' => '#a6dda6',
			'custom_prefixes'    => '',
		);
	}

	/**
	 * Options, merged over the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get() {

		$stored = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, self::defaults() );
	}

	/**
	 * User-supplied note prefixes, one per line.
	 *
	 * Treated as literal text, never as regular expressions, so a typo in the
	 * settings screen cannot break the order screen.
	 *
	 * @return string[]
	 */
	public static function get_custom_prefixes() {

		$options = self::get();
		$raw     = isset( $options['custom_prefixes'] ) ? (string) $options['custom_prefixes'] : '';

		if ( '' === trim( $raw ) ) {
			return array();
		}

		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$lines = array_map( 'trim', (array) $lines );

		return array_values( array_filter( $lines, 'strlen' ) );
	}

	/**
	 * Add the admin menu entry.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Order notes', 'dahu-order-notes' ),
			__( 'Order notes', 'dahu-order-notes' ),
			self::CAPABILITY,
			DAHU_ORDER_NOTES_SETTINGS_PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Load the screen's stylesheet, on that screen only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function enqueue_admin( $hook ) {

		if ( 'woocommerce_page_' . DAHU_ORDER_NOTES_SETTINGS_PAGE !== $hook ) {
			return;
		}

		// The family sheet first, and its own second: the plugin's own rules
		// are written to sit on top of the shared components, so the order is
		// a dependency and not a hope. One handle across the family -- the
		// file is byte-identical everywhere, so whichever plugin loads it
		// first serves all of them.
		wp_enqueue_style(
			'dahu-ui',
			plugins_url( 'assets/css/dahu-ui.css', DAHU_ORDER_NOTES_FILE ),
			array(),
			DAHU_ORDER_NOTES_VERSION
		);

		wp_enqueue_style(
			'dahu-order-notes-admin',
			plugins_url( 'assets/css/admin.css', DAHU_ORDER_NOTES_FILE ),
			array( 'dahu-ui' ),
			DAHU_ORDER_NOTES_VERSION
		);
	}

	/**
	 * Register the option and its sanitising callback.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitise submitted settings.
	 *
	 * @param mixed $input Raw submitted value.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {

		$defaults = self::defaults();
		$clean    = $defaults;

		if ( ! is_array( $input ) ) {
			return $clean;
		}

		$clean['enabled'] = empty( $input['enabled'] ) ? 0 : 1;

		foreach ( array( 'handwritten_bg', 'customer_header_bg' ) as $key ) {
			if ( ! isset( $input[ $key ] ) ) {
				continue;
			}

			$colour = sanitize_hex_color( wp_unslash( $input[ $key ] ) );

			if ( $colour ) {
				$clean[ $key ] = $colour;
			}
		}

		if ( isset( $input['custom_prefixes'] ) ) {
			$clean['custom_prefixes'] = sanitize_textarea_field( wp_unslash( $input['custom_prefixes'] ) );
		}

		return $clean;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'dahu-order-notes' ) );
		}

		$options = self::get();
		?>
		<div class="wrap dahu-ui">
			<div class="dahu-ui-head">
				<h1 class="dahu-ui-head__title"><?php esc_html_e( 'Order notes', 'dahu-order-notes' ); ?></h1>
				<p class="dahu-ui-head__sub"><?php esc_html_e( 'Makes the notes a human wrote stand out from the ones the shop generated.', 'dahu-order-notes' ); ?></p>
			</div>

			<hr class="wp-header-end">

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>

				<div class="dahu-ui-card">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Highlighting', 'dahu-order-notes' ); ?></th>
							<td>
								<label>
									<input type="checkbox"
										name="<?php echo esc_attr( self::OPTION ); ?>[enabled]"
										value="1"
										<?php checked( $options['enabled'], 1 ); ?> />
									<?php esc_html_e( 'Highlight hand-written private notes', 'dahu-order-notes' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="dahu-order-notes-handwritten-bg">
									<?php esc_html_e( 'Hand-written note colour', 'dahu-order-notes' ); ?>
								</label>
							</th>
							<td>
								<input type="color"
									id="dahu-order-notes-handwritten-bg"
									name="<?php echo esc_attr( self::OPTION ); ?>[handwritten_bg]"
									value="<?php echo esc_attr( $options['handwritten_bg'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="dahu-order-notes-customer-header-bg">
									<?php esc_html_e( 'Customer note header colour', 'dahu-order-notes' ); ?>
								</label>
							</th>
							<td>
								<input type="color"
									id="dahu-order-notes-customer-header-bg"
									name="<?php echo esc_attr( self::OPTION ); ?>[customer_header_bg]"
									value="<?php echo esc_attr( $options['customer_header_bg'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="dahu-order-notes-custom-prefixes">
									<?php esc_html_e( 'Additional automatic notes', 'dahu-order-notes' ); ?>
								</label>
							</th>
							<td>
								<textarea id="dahu-order-notes-custom-prefixes"
									class="large-text code"
									rows="6"
									name="<?php echo esc_attr( self::OPTION ); ?>[custom_prefixes]"><?php echo esc_textarea( $options['custom_prefixes'] ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'One per line. A note starting with one of these phrases is treated as automatic.', 'dahu-order-notes' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
				</div>

				<?php submit_button( null, 'dahu-ui-btn dahu-ui-btn--go' ); ?>
			</form>
		</div>
		<?php
	}
}
