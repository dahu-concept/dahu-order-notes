<?php
/**
 * Uninstall routine.
 *
 * @package DahuOrderNotes
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'dahu_order_notes_options' );

// The welcome screen's own three flags. They are interface state, not the
// shop's data: `includes/dahu-welcome.php` writes them with update_option(),
// so they outlive the plugin unless they are deleted here, and a reinstall
// would never greet anybody again — the screen and the guided tour staying
// silent for good, with nothing on screen to explain why.
delete_option( 'dahu_welcome_seen_dahu-order-notes' );
delete_option( 'dahu_welcome_pending_dahu-order-notes' );
delete_option( 'dahu_welcome_tour_dahu-order-notes' );

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		delete_option( 'dahu_order_notes_options' );
		delete_option( 'dahu_welcome_seen_dahu-order-notes' );
		delete_option( 'dahu_welcome_pending_dahu-order-notes' );
		delete_option( 'dahu_welcome_tour_dahu-order-notes' );
		restore_current_blog();
	}
}
