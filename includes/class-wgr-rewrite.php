<?php
/**
 * Gère la page publique dédiée aux avis (/avis-clients/ par défaut, slug configurable)
 * via une règle de réécriture + une query var, sans dépendre d'une page WP existante.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WGR_Rewrite {

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_page' ) );
	}

	public static function get_slug(): string {
		$settings = get_option( 'wgr_settings', array() );
		$slug     = $settings['page_slug'] ?? 'avis-clients';
		return sanitize_title( $slug ?: 'avis-clients' );
	}

	public static function register_rules(): void {
		add_rewrite_rule( '^' . self::get_slug() . '/?$', 'index.php?wgr_reviews_page=1', 'top' );
	}

	public static function register_query_var( array $vars ): array {
		$vars[] = 'wgr_reviews_page';
		return $vars;
	}

	public static function is_reviews_page(): bool {
		return (bool) get_query_var( 'wgr_reviews_page' );
	}

	public static function get_page_url(): string {
		return home_url( '/' . self::get_slug() . '/' );
	}

	/**
	 * Rend la page dédiée si l'URL correspond, en s'appuyant sur le template du thème actif.
	 */
	public static function maybe_render_page(): void {
		if ( ! self::is_reviews_page() ) {
			return;
		}

		status_header( 200 );

		$settings = get_option( 'wgr_settings', array() );
		$title    = $settings['page_title'] ?? __( 'Avis clients', 'websource-google-reviews' );

		add_filter(
			'document_title_parts',
			static function ( array $parts ) use ( $title ) {
				$parts['title'] = $title;
				return $parts;
			}
		);

		$template = locate_template( array( 'page.php', 'index.php' ) );
		if ( ! $template ) {
			$template = ABSPATH . WPINC . '/theme-compat/page.php';
		}

		global $wp_query;
		$wp_query->is_page     = true;
		$wp_query->is_singular = true;
		$wp_query->is_home     = false;
		$wp_query->is_404      = false;

		add_filter( 'the_content', array( __CLASS__, 'inject_reviews_content' ) );

		include $template;
		exit;
	}

	/**
	 * Injecte le contenu (shortcode) dans la boucle principale du template de page.
	 */
	public static function inject_reviews_content( string $content ): string {
		unset( $content );
		$settings = get_option( 'wgr_settings', array() );
		$title    = $settings['page_title'] ?? __( 'Avis clients', 'websource-google-reviews' );

		return '<h1 class="wgr-page-title">' . esc_html( $title ) . '</h1>' . do_shortcode( '[websource_google_reviews]' );
	}
}
