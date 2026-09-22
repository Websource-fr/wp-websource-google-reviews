<?php
/**
 * Génération et injection du JSON-LD AggregateRating (et Review) réel,
 * calculé à partir des avis stockés en base -- jamais inventé.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WGR_Schema {

	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'maybe_print_jsonld' ) );
	}

	/**
	 * Calcule le bloc aggregateRating (format schema.org) à partir des avis publiés.
	 * Retourne null s'il n'y a aucun avis (on ne doit jamais inventer une note).
	 */
	public static function get_aggregate_rating_data(): ?array {
		$stats = WGR_DB::get_stats();

		if ( 0 === $stats['count'] ) {
			return null;
		}

		return array(
			'@type'       => 'AggregateRating',
			'ratingValue' => $stats['average'],
			'reviewCount' => $stats['count'],
			'bestRating'  => 5,
			'worstRating' => 1,
		);
	}

	/**
	 * Construit le schema complet (Organization + AggregateRating + Review[])
	 * utilisé sur la page dédiée des avis.
	 */
	public static function get_full_schema(): ?array {
		$aggregate = self::get_aggregate_rating_data();
		if ( null === $aggregate ) {
			return null;
		}

		$settings = get_option( 'wgr_settings', array() );
		$reviews  = WGR_DB::get_published_reviews();

		$review_items = array();
		foreach ( array_slice( $reviews, 0, 50 ) as $review ) {
			$item = array(
				'@type'        => 'Review',
				'author'       => array(
					'@type' => 'Person',
					'name'  => $review['author_name'],
				),
				'reviewBody'   => $review['review_text'],
				'reviewRating' => array(
					'@type'       => 'Rating',
					'ratingValue' => (int) $review['rating'],
					'bestRating'  => 5,
					'worstRating' => 1,
				),
			);
			if ( ! empty( $review['review_date'] ) ) {
				$item['datePublished'] = $review['review_date'];
			}
			$review_items[] = $item;
		}

		return array(
			'@context'        => 'https://schema.org',
			'@type'           => 'LocalBusiness',
			'name'            => $settings['business_name'] ?? get_bloginfo( 'name' ),
			'url'             => home_url( '/' ),
			'aggregateRating' => $aggregate,
			'review'          => $review_items,
		);
	}

	/**
	 * Affiche le JSON-LD dans le <head> uniquement sur la page dédiée aux avis.
	 */
	public static function maybe_print_jsonld(): void {
		if ( ! WGR_Rewrite::is_reviews_page() ) {
			return;
		}

		$schema = self::get_full_schema();
		if ( null === $schema ) {
			return;
		}

		echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
	}
}
