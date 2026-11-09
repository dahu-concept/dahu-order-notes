<?php
/**
 * Uninstall routine.
 *
 * @package UbacNotes
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ubac_notes_options' );

// The welcome screen's own three flags. They are interface state, not the
// shop's data: `includes/ubac-welcome.php` writes them with update_option(),
// so they outlive the plugin unless they are deleted here, and a reinstall
// would never greet anybody again — the screen and the guided tour staying
// silent for good, with nothing on screen to explain why.
delete_option( 'ubac_welcome_seen_ubac-notes' );
// Les memes clefs sous le nouveau slug : elle est construite au runtime
// a partir du slug, donc elle a change avec lui. Sans ces trois lignes,
// une mise a jour depuis la version Ubac laisse des orphelines que plus
// rien ne nomme.
delete_option( 'ubac_welcome_seen_dahuconcept-order-notes' );
delete_option( 'ubac_welcome_pending_ubac-notes' );
delete_option( 'ubac_welcome_pending_dahuconcept-order-notes' );
delete_option( 'ubac_welcome_tour_ubac-notes' );
delete_option( 'ubac_welcome_tour_dahuconcept-order-notes' );

if ( is_multisite() ) {
	$ubac_notes_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $ubac_notes_site_ids as $ubac_notes_site_id ) {
		switch_to_blog( $ubac_notes_site_id );
		delete_option( 'ubac_notes_options' );
		delete_option( 'ubac_welcome_seen_ubac-notes' );
		delete_option( 'ubac_welcome_seen_dahuconcept-order-notes' );
		delete_option( 'ubac_welcome_pending_ubac-notes' );
		delete_option( 'ubac_welcome_pending_dahuconcept-order-notes' );
		delete_option( 'ubac_welcome_tour_ubac-notes' );
		delete_option( 'ubac_welcome_tour_dahuconcept-order-notes' );
		restore_current_blog();
	}
}
