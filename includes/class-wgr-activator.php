<?php
/**
 * Activation / désactivation du plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WGR_Activator {

	/**
	 * Crée la table dédiée aux avis et les options par défaut.
	 */
	public static function activate(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'wgr_reviews';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			author_name VARCHAR(190) NOT NULL DEFAULT '',
			rating TINYINT(1) UNSIGNED NOT NULL DEFAULT 5,
			review_text TEXT NOT NULL,
			review_date DATE NULL DEFAULT NULL,
			relative_date VARCHAR(100) NOT NULL DEFAULT '',
			source VARCHAR(20) NOT NULL DEFAULT 'manual',
			status VARCHAR(20) NOT NULL DEFAULT 'published',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'wgr_db_version', WGR_DB_VERSION );

		$defaults = array(
			'page_slug'             => 'avis-clients',
			'page_title'            => __( 'Avis clients', 'websource-google-reviews' ),
			'show_wc_fallback'      => 1,
			'wc_fallback_title'     => __( 'Avis du magasin', 'websource-google-reviews' ),
			'business_name'         => get_bloginfo( 'name' ),
		);
		add_option( 'wgr_settings', $defaults );

		self::flush_on_activation();
	}

	/**
	 * Force le rafraîchissement des permaliens (nouvelle règle de réécriture).
	 */
	public static function flush_on_activation(): void {
		require_once WGR_PLUGIN_DIR . 'includes/class-wgr-rewrite.php';
		WGR_Rewrite::register_rules();
		flush_rewrite_rules();
	}

	/**
	 * Désactivation : on nettoie uniquement les règles de réécriture, jamais les données.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
