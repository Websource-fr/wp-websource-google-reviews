<?php
/**
 * Couche d'accès aux données pour la table des avis.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WGR_DB {

	/**
	 * Nom complet de la table des avis.
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wgr_reviews';
	}

	/**
	 * Retourne tous les avis publiés, triés du plus récent au plus ancien.
	 */
	public static function get_published_reviews(): array {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY COALESCE(review_date, created_at) DESC, id DESC", 'published' ),
			ARRAY_A
		);
		return $rows ?: array();
	}

	/**
	 * Retourne tous les avis (admin), quel que soit leur statut.
	 */
	public static function get_all_reviews(): array {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A );
		return $rows ?: array();
	}

	/**
	 * Récupère un avis par son ID.
	 */
	public static function get_review( int $id ): ?array {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return $row ?: null;
	}

	/**
	 * Insère un avis. Retourne l'ID inséré ou 0 en cas d'échec.
	 */
	public static function insert_review( array $data ): int {
		global $wpdb;
		$table = self::table();

		$defaults = array(
			'author_name'   => '',
			'rating'        => 5,
			'review_text'   => '',
			'review_date'   => null,
			'relative_date' => '',
			'source'        => 'manual',
			'status'        => 'published',
		);
		$data = wp_parse_args( $data, $defaults );

		$wpdb->insert(
			$table,
			array(
				'author_name'   => sanitize_text_field( $data['author_name'] ),
				'rating'        => max( 1, min( 5, (int) $data['rating'] ) ),
				'review_text'   => sanitize_textarea_field( $data['review_text'] ),
				'review_date'   => $data['review_date'] ? gmdate( 'Y-m-d', strtotime( (string) $data['review_date'] ) ) : null,
				'relative_date' => sanitize_text_field( $data['relative_date'] ),
				'source'        => sanitize_key( $data['source'] ),
				'status'        => sanitize_key( $data['status'] ),
				'created_at'    => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Met à jour un avis existant.
	 */
	public static function update_review( int $id, array $data ): bool {
		global $wpdb;
		$table = self::table();

		$update = array( 'updated_at' => current_time( 'mysql' ) );
		$format = array( '%s' );

		if ( isset( $data['author_name'] ) ) {
			$update['author_name'] = sanitize_text_field( $data['author_name'] );
			$format[]              = '%s';
		}
		if ( isset( $data['rating'] ) ) {
			$update['rating'] = max( 1, min( 5, (int) $data['rating'] ) );
			$format[]         = '%d';
		}
		if ( isset( $data['review_text'] ) ) {
			$update['review_text'] = sanitize_textarea_field( $data['review_text'] );
			$format[]              = '%s';
		}
		if ( array_key_exists( 'review_date', $data ) ) {
			$update['review_date'] = $data['review_date'] ? gmdate( 'Y-m-d', strtotime( (string) $data['review_date'] ) ) : null;
			$format[]              = '%s';
		}
		if ( isset( $data['relative_date'] ) ) {
			$update['relative_date'] = sanitize_text_field( $data['relative_date'] );
			$format[]                = '%s';
		}
		if ( isset( $data['status'] ) ) {
			$update['status'] = sanitize_key( $data['status'] );
			$format[]         = '%s';
		}

		$result = $wpdb->update( $table, $update, array( 'id' => $id ), $format, array( '%d' ) );

		return false !== $result;
	}

	/**
	 * Supprime un avis.
	 */
	public static function delete_review( int $id ): bool {
		global $wpdb;
		$table  = self::table();
		$result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
		return false !== $result;
	}

	/**
	 * Insère plusieurs avis d'un coup (utilisé par le parseur de texte collé).
	 *
	 * @param array $reviews Tableau d'avis (author_name, rating, review_text, relative_date...).
	 * @return int Nombre d'avis insérés.
	 */
	public static function bulk_insert( array $reviews ): int {
		$count = 0;
		foreach ( $reviews as $review ) {
			$review['source'] = 'parsed';
			if ( self::insert_review( $review ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Calcule la moyenne et le nombre d'avis publiés.
	 */
	public static function get_stats(): array {
		$reviews = self::get_published_reviews();
		$count   = count( $reviews );
		if ( 0 === $count ) {
			return array(
				'count'   => 0,
				'average' => 0.0,
			);
		}
		$sum = array_sum( wp_list_pluck( $reviews, 'rating' ) );
		return array(
			'count'   => $count,
			'average' => round( $sum / $count, 1 ),
		);
	}
}
