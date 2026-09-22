<?php
/**
 * Vue : liste des avis (CRUD manuel).
 *
 * @var array      $reviews
 * @var array|null $editing_review
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wgr-admin">
	<h1><?php esc_html_e( 'Avis clients', 'websource-google-reviews' ); ?></h1>

	<?php if ( isset( $_GET['wgr_saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Avis enregistré.', 'websource-google-reviews' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['wgr_deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Avis supprimé.', 'websource-google-reviews' ); ?></p></div>
	<?php endif; ?>

	<h2><?php echo $editing_review ? esc_html__( 'Modifier l’avis', 'websource-google-reviews' ) : esc_html__( 'Ajouter un avis', 'websource-google-reviews' ); ?></h2>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wgr-review-form">
		<?php wp_nonce_field( 'wgr_save_review' ); ?>
		<input type="hidden" name="action" value="wgr_save_review" />
		<input type="hidden" name="review_id" value="<?php echo esc_attr( $editing_review['id'] ?? 0 ); ?>" />

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="author_name"><?php esc_html_e( 'Auteur', 'websource-google-reviews' ); ?></label></th>
				<td><input type="text" id="author_name" name="author_name" class="regular-text" required
					value="<?php echo esc_attr( $editing_review['author_name'] ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="rating"><?php esc_html_e( 'Note', 'websource-google-reviews' ); ?></label></th>
				<td>
					<select id="rating" name="rating">
						<?php for ( $r = 5; $r >= 1; $r-- ) : ?>
							<option value="<?php echo esc_attr( $r ); ?>" <?php selected( (int) ( $editing_review['rating'] ?? 5 ), $r ); ?>>
								<?php echo esc_html( str_repeat( '★', $r ) . str_repeat( '☆', 5 - $r ) ); ?>
							</option>
						<?php endfor; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="review_text"><?php esc_html_e( 'Texte de l’avis', 'websource-google-reviews' ); ?></label></th>
				<td><textarea id="review_text" name="review_text" rows="4" class="large-text" required><?php echo esc_textarea( $editing_review['review_text'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="review_date"><?php esc_html_e( 'Date (optionnel)', 'websource-google-reviews' ); ?></label></th>
				<td><input type="date" id="review_date" name="review_date" value="<?php echo esc_attr( $editing_review['review_date'] ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="relative_date"><?php esc_html_e( 'Date relative affichée (optionnel)', 'websource-google-reviews' ); ?></label></th>
				<td>
					<input type="text" id="relative_date" name="relative_date" class="regular-text" placeholder="<?php esc_attr_e( 'il y a 3 mois', 'websource-google-reviews' ); ?>"
						value="<?php echo esc_attr( $editing_review['relative_date'] ?? '' ); ?>" />
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer l’avis', 'websource-google-reviews' ); ?></button>
			<?php if ( $editing_review ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=websource-google-reviews' ) ); ?>" class="button"><?php esc_html_e( 'Annuler', 'websource-google-reviews' ); ?></a>
			<?php endif; ?>
		</p>
	</form>

	<h2><?php esc_html_e( 'Tous les avis', 'websource-google-reviews' ); ?></h2>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Auteur', 'websource-google-reviews' ); ?></th>
				<th><?php esc_html_e( 'Note', 'websource-google-reviews' ); ?></th>
				<th><?php esc_html_e( 'Texte', 'websource-google-reviews' ); ?></th>
				<th><?php esc_html_e( 'Date', 'websource-google-reviews' ); ?></th>
				<th><?php esc_html_e( 'Source', 'websource-google-reviews' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'websource-google-reviews' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $reviews ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'Aucun avis enregistré pour le moment.', 'websource-google-reviews' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $reviews as $review ) : ?>
				<tr>
					<td><?php echo esc_html( $review['author_name'] ); ?></td>
					<td><?php echo esc_html( str_repeat( '★', (int) $review['rating'] ) ); ?></td>
					<td><?php echo esc_html( wp_trim_words( $review['review_text'], 15 ) ); ?></td>
					<td><?php echo esc_html( $review['relative_date'] ?: $review['review_date'] ); ?></td>
					<td><?php echo esc_html( $review['source'] ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=websource-google-reviews&edit=' . $review['id'] ) ); ?>"><?php esc_html_e( 'Modifier', 'websource-google-reviews' ); ?></a>
						|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wgr_delete_review&id=' . $review['id'] ), 'wgr_delete_review_' . $review['id'] ) ); ?>"
							onclick="return confirm('<?php echo esc_js( __( 'Supprimer cet avis ?', 'websource-google-reviews' ) ); ?>');">
							<?php esc_html_e( 'Supprimer', 'websource-google-reviews' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
