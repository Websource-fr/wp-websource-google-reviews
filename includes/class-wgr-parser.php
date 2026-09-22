<?php
/**
 * Analyse le texte brut copié depuis le panneau d'avis Google (Maps/Recherche)
 * pour en extraire des avis structurés (auteur, note, texte, date relative).
 *
 * Le format du texte collé par Google n'est pas garanti stable dans le temps ;
 * ce parseur applique des heuristiques tolérantes et laisse toujours la
 * possibilité à l'administrateur de corriger le résultat à la main ensuite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WGR_Parser {

	/**
	 * Motifs de date relative en français reconnus par Google.
	 */
	private const RELATIVE_DATE_PATTERN = '/^(il y a\s+)?(un|une|\d+)\s+(jour|jours|semaine|semaines|mois|an|ans|année|années)$/iu';

	/**
	 * Découpe le texte collé en avis structurés.
	 *
	 * @param string $raw_text Texte brut collé par l'utilisateur.
	 * @return array Liste d'avis : author_name, rating, review_text, relative_date, review_date.
	 */
	public static function parse( string $raw_text ): array {
		$lines = preg_split( '/\r\n|\r|\n/', $raw_text );
		$lines = array_values(
			array_filter(
				array_map( 'trim', $lines ),
				static fn( $line ) => '' !== $line
			)
		);

		$reviews  = array();
		$total    = count( $lines );
		$i        = 0;

		while ( $i < $total ) {
			$line = $lines[ $i ];

			// Une ligne "auteur" est suivie de près par une note (étoiles) puis une date relative.
			// Heuristique : on cherche une ligne contenant un nombre d'étoiles (ex: "5 étoiles" ou "★★★★★").
			$rating = self::extract_rating( $line );

			if ( null !== $rating ) {
				// La ligne précédente (déjà consommée) est censée être l'auteur : on la retrouve
				// en remontant, sinon on utilise "Client Google" par défaut.
				$author = self::find_preceding_author( $reviews, $lines, $i );

				// La ligne suivante est souvent la date relative.
				$relative_date = '';
				$j             = $i + 1;
				if ( $j < $total && self::is_relative_date( $lines[ $j ] ) ) {
					$relative_date = $lines[ $j ];
					++$j;
				}

				// Le texte de l'avis : on agrège les lignes suivantes jusqu'à la prochaine
				// détection d'une note (nouvel avis) ou "J'ai trouvé cet avis utile" (bruit Google à ignorer).
				$text_lines = array();
				while ( $j < $total && null === self::extract_rating( $lines[ $j ] ) && ! self::looks_like_new_author_block( $lines, $j ) ) {
					if ( self::is_noise_line( $lines[ $j ] ) ) {
						++$j;
						continue;
					}
					$text_lines[] = $lines[ $j ];
					++$j;
				}

				$reviews[] = array(
					'author_name'   => $author,
					'rating'        => $rating,
					'review_text'   => trim( implode( ' ', $text_lines ) ),
					'relative_date' => $relative_date,
					'review_date'   => self::relative_to_date( $relative_date ),
				);

				$i = $j;
				continue;
			}

			++$i;
		}

		return $reviews;
	}

	/**
	 * Tente d'extraire une note (1 à 5) d'une ligne.
	 */
	private static function extract_rating( string $line ): ?int {
		// Format "5 étoiles" / "5 star" / "5/5".
		if ( preg_match( '/^([1-5])\s*(étoiles?|stars?|\/\s*5)/iu', $line, $m ) ) {
			return (int) $m[1];
		}
		// Format étoiles unicode (★★★★★ ou ★★★★☆).
		if ( preg_match( '/^[★]{1,5}[☆]{0,4}$/u', $line ) ) {
			return (int) substr_count( $line, '★' );
		}
		// Format "Note : 4 sur 5".
		if ( preg_match( '/note\s*:?\s*([1-5])\s*(sur|\/)\s*5/iu', $line, $m ) ) {
			return (int) $m[1];
		}
		return null;
	}

	/**
	 * Une ligne de date relative, style Google ("il y a 3 mois", "2 semaines").
	 */
	private static function is_relative_date( string $line ): bool {
		return 1 === preg_match( self::RELATIVE_DATE_PATTERN, $line );
	}

	/**
	 * Lignes de bruit fréquentes dans un copier-coller Google (à ignorer).
	 */
	private static function is_noise_line( string $line ): bool {
		$noise = array(
			"j'ai trouvé cet avis utile",
			'utile',
			'partager',
			'répondre',
			'nouveau',
			'avis local guide',
			'guide local',
			"plus d'avis",
			'traduire',
			'voir la traduction',
			'photo',
		);
		$lower = mb_strtolower( trim( $line ) );
		foreach ( $noise as $n ) {
			if ( $lower === $n ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Heuristique : une ligne courte (pas de ponctuation finale, < 60 car.) suivie
	 * d'un compteur d'avis type "12 avis" indique probablement le nom du prochain auteur.
	 */
	private static function looks_like_new_author_block( array $lines, int $index ): bool {
		if ( ! isset( $lines[ $index + 1 ] ) ) {
			return false;
		}
		$next = $lines[ $index + 1 ];
		return (bool) preg_match( '/^\d+\s+(avis|reviews?)/iu', $next );
	}

	/**
	 * Remonte dans les lignes précédentes pour deviner le nom de l'auteur.
	 */
	private static function find_preceding_author( array $already_parsed, array $lines, int $rating_index ): string {
		for ( $k = $rating_index - 1; $k >= 0 && $k >= $rating_index - 3; $k-- ) {
			$candidate = $lines[ $k ];
			if ( '' === $candidate || self::is_relative_date( $candidate ) || null !== self::extract_rating( $candidate ) ) {
				continue;
			}
			if ( preg_match( '/^\d+\s+(avis|reviews?)/iu', $candidate ) ) {
				continue;
			}
			// Une ligne "nom" plausible : pas trop longue, pas de ponctuation finale de phrase.
			if ( mb_strlen( $candidate ) <= 60 ) {
				return sanitize_text_field( $candidate );
			}
		}
		return __( 'Client Google', 'websource-google-reviews' );
	}

	/**
	 * Convertit une date relative française en date absolue approximative (Y-m-d),
	 * calculée à partir d'aujourd'hui. Retourne null si non reconnu.
	 */
	private static function relative_to_date( string $relative ): ?string {
		if ( '' === $relative ) {
			return null;
		}
		if ( ! preg_match( '/(un|une|\d+)\s+(jour|jours|semaine|semaines|mois|an|ans|année|années)/iu', $relative, $m ) ) {
			return null;
		}

		$amount = ( 'un' === mb_strtolower( $m[1] ) || 'une' === mb_strtolower( $m[1] ) ) ? 1 : (int) $m[1];
		$unit   = mb_strtolower( $m[2] );

		$interval_spec = 'P';
		if ( str_starts_with( $unit, 'jour' ) ) {
			$interval_spec .= $amount . 'D';
		} elseif ( str_starts_with( $unit, 'semaine' ) ) {
			$interval_spec .= ( $amount * 7 ) . 'D';
		} elseif ( str_starts_with( $unit, 'mois' ) ) {
			$interval_spec .= $amount . 'M';
		} else { // an, ans, année, années.
			$interval_spec .= $amount . 'Y';
		}

		try {
			$date = new DateTime( 'now', wp_timezone() );
			$date->sub( new DateInterval( $interval_spec ) );
			return $date->format( 'Y-m-d' );
		} catch ( Exception $e ) {
			return null;
		}
	}
}
