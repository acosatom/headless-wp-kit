<?php
/**
 * Editorial meta box, row actions and agent block styles.
 *
 * @package Headless_WP_Kit
 */

defined( 'ABSPATH' ) || exit;

if ( hwk_feature_enabled( 'editorial' ) ) {
	add_action( 'add_meta_boxes', 'hwk_add_editorial_meta_box' );
	add_action( 'save_post', 'hwk_save_editorial_meta_box', 10, 2 );
}

if ( hwk_feature_enabled( 'agent_blocks' ) ) {
	add_action( 'init', 'hwk_register_agent_block_styles' );
}

add_filter( 'post_row_actions', 'hwk_filter_row_actions', 10, 2 );
add_filter( 'page_row_actions', 'hwk_filter_row_actions', 10, 2 );

/**
 * Register the three agent block styles.
 */
function hwk_register_agent_block_styles(): void {
	$styles = array(
		'hwk-agent-public'     => 'Agent · public',
		'hwk-agent-labeled'    => 'Agent · labeled',
		'hwk-agent-restricted' => 'Agent · restricted',
	);

	foreach ( $styles as $name => $label ) {
		register_block_style(
			'core/group',
			array(
				'name'  => $name,
				'label' => $label,
			)
		);
	}
}

/**
 * Editorial meta box.
 */
function hwk_add_editorial_meta_box(): void {
	add_meta_box(
		'hwk-editorial',
		'Headless WP Kit',
		'hwk_render_editorial_meta_box',
		'post',
		'side',
		'high'
	);
}

/**
 * Render fields from the active profile.
 */
function hwk_render_editorial_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'hwk_editorial', 'hwk_editorial_nonce' );

	$model = hwk_content_model();
	$meta  = isset( $model['meta'] ) && is_array( $model['meta'] ) ? $model['meta'] : array();

	foreach ( $meta as $name => $definition ) {
		if ( ! is_array( $definition ) || empty( $definition['key'] ) ) {
			continue;
		}

		hwk_render_meta_field( $post, (string) $name, $definition );
	}

	$frontend = hwk_frontend_url();

	if ( '' !== $frontend && hwk_feature_enabled( 'preview' ) ) {
		?>
		<p>
			<a class="button" href="<?php echo esc_url( hwk_admin_preview_url( $post->ID ) ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html__( 'Preview on frontend', 'headless-wp-kit' ); ?>
			</a>
		</p>
		<?php
	}

	$public = 'publish' === $post->post_status ? hwk_public_url( $post ) : '';

	if ( '' !== $public ) {
		?>
		<p><a href="<?php echo esc_url( $public ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'View on frontend', 'headless-wp-kit' ); ?></a></p>
		<?php
	}
}

/**
 * Render one profile field.
 *
 * @param WP_Post              $post       Post.
 * @param string               $name       Logical name.
 * @param array<string, mixed> $definition Definition.
 */
function hwk_render_meta_field( WP_Post $post, string $name, array $definition ): void {
	$key   = (string) $definition['key'];
	$label = isset( $definition['label'] ) ? (string) $definition['label'] : $key;
	$input = isset( $definition['input'] ) ? (string) $definition['input'] : 'text';
	$value = get_post_meta( $post->ID, $key, true );

	echo '<p><label for="hwk-meta-' . esc_attr( $name ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';

	if ( 'authors' === $input ) {
		$author_type = isset( hwk_content_model()['author_post_type']['slug'] )
			? (string) hwk_content_model()['author_post_type']['slug']
			: '';
		$authors = '' === $author_type ? array() : get_posts(
			array(
				'post_type'      => $author_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$values = is_array( $value ) ? array_map( 'absint', $value ) : array();

		echo '<select multiple class="widefat" id="hwk-meta-' . esc_attr( $name ) . '" name="hwk_meta[' . esc_attr( $name ) . '][]" size="4">';
		foreach ( $authors as $author ) {
			echo '<option value="' . esc_attr( (string) $author->ID ) . '" ' . selected( in_array( (int) $author->ID, $values, true ), true, false ) . '>' . esc_html( get_the_title( $author ) ) . '</option>';
		}
		echo '</select>';
	} elseif ( 'select' === $input ) {
		$options = isset( $definition['options'] ) && is_array( $definition['options'] ) ? $definition['options'] : array();
		echo '<select class="widefat" id="hwk-meta-' . esc_attr( $name ) . '" name="hwk_meta[' . esc_attr( $name ) . ']">';
		echo '<option value="">' . esc_html__( 'Not set', 'headless-wp-kit' ) . '</option>';
		foreach ( $options as $option_value => $option_label ) {
			echo '<option value="' . esc_attr( (string) $option_value ) . '" ' . selected( (string) $value, (string) $option_value, false ) . '>' . esc_html( (string) $option_label ) . '</option>';
		}
		echo '</select>';
	} elseif ( 'textarea' === $input || 'textarea_list' === $input ) {
		$text = is_array( $value ) ? implode( "\n", $value ) : (string) $value;
		echo '<textarea class="widefat" rows="4" id="hwk-meta-' . esc_attr( $name ) . '" name="hwk_meta[' . esc_attr( $name ) . ']">' . esc_textarea( $text ) . '</textarea>';
		if ( 'textarea_list' === $input ) {
			echo '<span class="description">' . esc_html__( 'One per line.', 'headless-wp-kit' ) . '</span>';
		}
	} else {
		$text = is_array( $value ) ? implode( ', ', $value ) : (string) $value;
		echo '<input type="text" class="widefat" id="hwk-meta-' . esc_attr( $name ) . '" name="hwk_meta[' . esc_attr( $name ) . ']" value="' . esc_attr( $text ) . '">';
		if ( 'list' === $input ) {
			echo '<span class="description">' . esc_html__( 'Comma-separated.', 'headless-wp-kit' ) . '</span>';
		}
	}

	echo '</p>';
}

/**
 * Save profile fields.
 *
 * @param int     $post_id Post id.
 * @param WP_Post $post    Post.
 */
function hwk_save_editorial_meta_box( int $post_id, WP_Post $post ): void {
	if ( 'post' !== $post->post_type || ! isset( $_POST['hwk_editorial_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['hwk_editorial_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'hwk_editorial' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$model = hwk_content_model();
	$meta  = isset( $model['meta'] ) && is_array( $model['meta'] ) ? $model['meta'] : array();
	$input = isset( $_POST['hwk_meta'] ) && is_array( $_POST['hwk_meta'] ) ? wp_unslash( $_POST['hwk_meta'] ) : array();

	foreach ( $meta as $name => $definition ) {
		if ( ! is_array( $definition ) || empty( $definition['key'] ) ) {
			continue;
		}

		$sanitize = isset( $definition['sanitize'] ) ? (string) $definition['sanitize'] : 'text';
		$raw      = $input[ $name ] ?? ( 'author_ids' === $sanitize ? array() : '' );

		update_post_meta( $post_id, (string) $definition['key'], hwk_meta_sanitize_value( $sanitize, $raw ) );
	}
}

/**
 * Sanitize a field value by sanitizer name.
 *
 * @param string $sanitize Sanitizer name.
 * @param mixed  $value    Raw value.
 * @return mixed
 */
function hwk_meta_sanitize_value( string $sanitize, $value ) {
	return match ( $sanitize ) {
		'author_ids'  => hwk_sanitize_author_ids( $value ),
		'audience'    => hwk_sanitize_audience( $value ),
		'level'       => hwk_sanitize_level( $value ),
		'string_list' => hwk_sanitize_string_list( $value ),
		'textarea'    => sanitize_textarea_field( (string) $value ),
		default       => sanitize_text_field( (string) $value ),
	};
}

/**
 * Public frontend URL for a post.
 */
function hwk_public_url( WP_Post $post ): string {
	$frontend = hwk_frontend_url();

	if ( '' === $frontend ) {
		return '';
	}

	$url = $frontend . '/' . $post->post_name . '/';

	return (string) apply_filters( 'hwk_public_url', $url, $post );
}

/**
 * Replace WordPress row actions with frontend-aware ones.
 *
 * @param array<string, string> $actions Actions.
 * @param WP_Post               $post    Post.
 * @return array<string, string>
 */
function hwk_filter_row_actions( array $actions, WP_Post $post ): array {
	if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		return $actions;
	}

	$frontend = hwk_frontend_url();

	if ( '' === $frontend ) {
		return $actions;
	}

	unset( $actions['view'], $actions['preview'] );

	if ( 'publish' === $post->post_status ) {
		$url = hwk_public_url( $post );

		if ( '' !== $url ) {
			$actions['hwk_public'] = sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( $url ),
				esc_html__( 'View on frontend', 'headless-wp-kit' )
			);
		}

		return $actions;
	}

	if ( hwk_feature_enabled( 'preview' ) ) {
		$actions['hwk_preview'] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( hwk_admin_preview_url( $post->ID ) ),
			esc_html__( 'Preview', 'headless-wp-kit' )
		);
	}

	return $actions;
}
