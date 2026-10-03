<?php
/**
 * Plugin Name:       Websource Google Reviews
 * Plugin URI:        https://www.websource.fr/modules-wordpress/module-avis-google-wordpress-optimise-seo
 * Description:       Publie une page publique d'avis clients Google (collés manuellement ou saisis un par un) avec injection JSON-LD AggregateRating réelle, et un bloc "avis du magasin" optionnel sur les fiches produit WooCommerce sans avis.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Websource
 * Author URI:        https://www.websource.fr/
 * License:            GPL v2 or later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        websource-google-reviews
 * Domain Path:        /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Sécurité : accès direct interdit.
}

define( 'WGR_VERSION', '1.1.0' );
define( 'WGR_PLUGIN_FILE', __FILE__ );
define( 'WGR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WGR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WGR_DB_VERSION', '1.0' );

require_once WGR_PLUGIN_DIR . 'includes/class-wgr-activator.php';
require_once WGR_PLUGIN_DIR . 'includes/class-wgr-db.php';
require_once WGR_PLUGIN_DIR . 'includes/class-wgr-parser.php';
require_once WGR_PLUGIN_DIR . 'includes/class-wgr-schema.php';
require_once WGR_PLUGIN_DIR . 'includes/class-wgr-shortcode.php';
require_once WGR_PLUGIN_DIR . 'includes/class-wgr-rewrite.php';
require_once WGR_PLUGIN_DIR . 'includes/class-wgr-woocommerce.php';

register_activation_hook( __FILE__, array( 'WGR_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WGR_Activator', 'deactivate' ) );

/**
 * Charge le domaine de traduction.
 */
function wgr_load_textdomain(): void {
	load_plugin_textdomain( 'websource-google-reviews', false, dirname( plugin_basename( WGR_PLUGIN_FILE ) ) . '/languages' );
}
add_action( 'init', 'wgr_load_textdomain' );

/**
 * Initialise les briques du plugin.
 */
function wgr_init_plugin(): void {
	WGR_Rewrite::init();
	WGR_Shortcode::init();
	WGR_Schema::init();

	if ( class_exists( 'WooCommerce' ) ) {
		WGR_WooCommerce::init();
	}

	if ( is_admin() ) {
		require_once WGR_PLUGIN_DIR . 'admin/class-wgr-admin.php';
		WGR_Admin::init();
		require_once WGR_PLUGIN_DIR . 'admin/class-wgr-support-box.php';
		WGR_Support_Box::init();
	}
}
add_action( 'plugins_loaded', 'wgr_init_plugin' );

/**
 * Point d'intégration public : retourne le bloc aggregateRating (tableau PHP)
 * pour que le thème / un plugin SEO l'injecte dans son propre JSON-LD
 * Organization ou LocalBusiness.
 *
 * Exemple d'utilisation dans functions.php :
 *
 *     $rating = wgr_get_aggregate_rating();
 *     if ( $rating ) {
 *         $organization_schema['aggregateRating'] = $rating;
 *     }
 *
 * @return array|null
 */
function wgr_get_aggregate_rating(): ?array {
	return WGR_Schema::get_aggregate_rating_data();
}
