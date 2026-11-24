<?php
/**
 * Settings and profile helpers.
 *
 * @package Headless_WP_Kit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default settings.
 *
 * @return array<string, mixed>
 */
function hwk_default_settings(): array {
	return array(
		'profile'      => 'core',
		'frontend_url' => '',
		'preview_ttl'  => 900,
	);
}

/**
 * Current settings merged with defaults.
 *
 * @return array<string, mixed>
 */
function hwk_get_settings(): array {
	$settings = get_option( 'hwk_settings', array() );

	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	return wp_parse_args( $settings, hwk_default_settings() );
}

/**
 * One setting.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function hwk_get_setting( string $key, $default = null ) {
	$settings = hwk_get_settings();

	return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
}

/**
 * Available profiles for the settings screen.
 *
 * @return array<string, string>
 */
function hwk_profile_slugs(): array {
	return array(
		'core'    => 'Core headless',
		'example' => 'Example editorial site',
	);
}

/**
 * Load the active profile.
 *
 * @return array<string, mixed>
 */
function hwk_get_profile(): array {
	$slug    = (string) hwk_get_setting( 'profile', 'core' );
	$allowed = hwk_profile_slugs();

	if ( ! isset( $allowed[ $slug ] ) ) {
		$slug = 'core';
	}

	$file = HWK_DIR . 'profiles/' . $slug . '.php';
	$data = array();

	if ( is_readable( $file ) ) {
		$data = include $file;
	} else {
		$data = include HWK_DIR . 'profiles/core.php';
	}

	if ( ! is_array( $data ) ) {
		$data = array();
	}

	$data['slug'] = $slug;

	return (array) apply_filters( 'hwk_profile', $data, $slug );
}

/**
 * Content model from the profile.
 *
 * @return array<string, mixed>
 */
function hwk_content_model(): array {
	$profile = hwk_get_profile();

	return isset( $profile['content_model'] ) && is_array( $profile['content_model'] )
		? $profile['content_model']
		: array();
}

/**
 * Whether a profile feature is enabled.
 */
function hwk_feature_enabled( string $feature ): bool {
	$profile  = hwk_get_profile();
	$features = isset( $profile['features'] ) && is_array( $profile['features'] ) ? $profile['features'] : array();
	$enabled  = ! empty( $features[ $feature ] );

	return (bool) apply_filters( 'hwk_feature_enabled', $enabled, $feature, $profile );
}

/**
 * Frontend base URL.
 */
function hwk_frontend_url(): string {
	$url = (string) hwk_get_setting( 'frontend_url', '' );
	$url = (string) apply_filters( 'hwk_frontend_url', $url );

	return untrailingslashit( esc_url_raw( $url ) );
}

/**
 * Preview TTL in seconds.
 */
function hwk_preview_ttl(): int {
	$ttl = (int) hwk_get_setting( 'preview_ttl', 900 );

	return max( 60, min( 3600, $ttl ) );
}

/**
 * Languages known to the site.
 *
 * @return string[]
 */
function hwk_languages(): array {
	if ( function_exists( 'pll_languages_list' ) ) {
		$languages = pll_languages_list();

		if ( is_array( $languages ) && array() !== $languages ) {
			return array_values( array_map( 'strval', $languages ) );
		}
	}

	return array( (string) get_locale() );
}

/**
 * Meta definition by logical name.
 *
 * @param string $name Logical name, e.g. direction.
 * @return array<string, mixed>|null
 */
function hwk_meta_definition( string $name ): ?array {
	$model = hwk_content_model();
	$meta  = isset( $model['meta'] ) && is_array( $model['meta'] ) ? $model['meta'] : array();

	return isset( $meta[ $name ] ) && is_array( $meta[ $name ] ) ? $meta[ $name ] : null;
}

/**
 * Stored meta key by logical name.
 */
function hwk_meta_key( string $name ): string {
	$definition = hwk_meta_definition( $name );

	return isset( $definition['key'] ) ? (string) $definition['key'] : '';
}

/**
 * Meta label by logical name.
 */
function hwk_meta_label( string $name ): string {
	$definition = hwk_meta_definition( $name );

	return isset( $definition['label'] ) ? (string) $definition['label'] : $name;
}

add_action( 'admin_menu', 'hwk_admin_menu' );
add_action( 'admin_init', 'hwk_register_settings' );

/**
 * Settings screen.
 */
function hwk_admin_menu(): void {
	add_options_page(
		'Headless WP Kit',
		'Headless WP Kit',
		'manage_options',
		'headless-wp-kit',
		'hwk_render_settings_page'
	);
}

/**
 * Register settings and sanitizers.
 */
function hwk_register_settings(): void {
	register_setting(
		'hwk',
		'hwk_settings',
		array(
			'type'              => 'array',
			'default'           => hwk_default_settings(),
			'sanitize_callback' => 'hwk_sanitize_settings',
		)
	);

	register_setting(
		'hwk',
		'hwk_redirects',
		array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'hwk_sanitize_redirects',
		)
	);

	register_setting(
		'hwk',
		'hwk_retired',
		array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'hwk_sanitize_retired',
		)
	);
}

/**
 * Sanitize settings.
 *
 * @param mixed $input Raw input.
 * @return array<string, mixed>
 */
function hwk_sanitize_settings( $input ): array {
	$input = is_array( $input ) ? $input : array();

	$profile = isset( $input['profile'] ) ? sanitize_key( (string) $input['profile'] ) : 'core';
	$allowed = hwk_profile_slugs();

	if ( ! isset( $allowed[ $profile ] ) ) {
		$profile = 'core';
	}

	$frontend = isset( $input['frontend_url'] ) ? esc_url_raw( (string) $input['frontend_url'] ) : '';
	$ttl      = isset( $input['preview_ttl'] ) ? absint( $input['preview_ttl'] ) : 900;

	return array(
		'profile'      => $profile,
		'frontend_url' => $frontend,
		'preview_ttl'  => max( 60, min( 3600, $ttl ) ),
	);
}

/**
 * Sanitize redirect lines.
 *
 * @param mixed $value Raw value.
 */
function hwk_sanitize_redirects( $value ): string {
	return sanitize_textarea_field( (string) $value );
}

/**
 * Sanitize retired lines.
 *
 * @param mixed $value Raw value.
 */
function hwk_sanitize_retired( $value ): string {
	return sanitize_textarea_field( (string) $value );
}

/**
 * Redirect manifest.
 *
 * @return array<int, array{from: string, to: string}>
 */
function hwk_redirects_list(): array {
	$raw   = (string) get_option( 'hwk_redirects', '' );
	$lines = preg_split( '/\r\n|\r|\n/', $raw );
	$items = array();

	foreach ( (array) $lines as $line ) {
		$line = trim( (string) $line );

		if ( '' === $line || ! str_contains( $line, '->' ) ) {
			continue;
		}

		list( $from, $to ) = array_map( 'trim', explode( '->', $line, 2 ) );

		if ( ! str_starts_with( $from, '/' ) || ! str_starts_with( $to, '/' ) ) {
			continue;
		}

		$items[] = array(
			'from' => $from,
			'to'   => $to,
		);
	}

	return $items;
}

/**
 * Retired paths manifest.
 *
 * @return array<int, array{path: string}>
 */
function hwk_retired_list(): array {
	$raw   = (string) get_option( 'hwk_retired', '' );
	$lines = preg_split( '/\r\n|\r|\n/', $raw );
	$items = array();

	foreach ( (array) $lines as $line ) {
		$line = trim( (string) $line );

		if ( '' === $line || ! str_starts_with( $line, '/' ) ) {
			continue;
		}

		$items[] = array( 'path' => $line );
	}

	return $items;
}

/**
 * Settings screen markup.
 */
function hwk_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = hwk_get_settings();
	$profile  = hwk_get_profile();
	$features = isset( $profile['features'] ) && is_array( $profile['features'] ) ? $profile['features'] : array();
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Headless WP Kit', 'headless-wp-kit' ); ?></h1>

		<?php if ( isset( $_GET['hwk_seeded'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Example terms created.', 'headless-wp-kit' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'hwk' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="hwk-profile"><?php echo esc_html__( 'Profile', 'headless-wp-kit' ); ?></label></th>
					<td>
						<select id="hwk-profile" name="hwk_settings[profile]">
							<?php foreach ( hwk_profile_slugs() as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $settings['profile'], $slug ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html__( 'The profile controls which features and content model are enabled.', 'headless-wp-kit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hwk-frontend-url"><?php echo esc_html__( 'Frontend URL', 'headless-wp-kit' ); ?></label></th>
					<td>
						<input type="url" class="regular-text" id="hwk-frontend-url" name="hwk_settings[frontend_url]" value="<?php echo esc_attr( (string) $settings['frontend_url'] ); ?>" placeholder="https://example.com">
						<p class="description"><?php echo esc_html__( 'Base URL of the public frontend used by preview links.', 'headless-wp-kit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hwk-preview-ttl"><?php echo esc_html__( 'Preview TTL', 'headless-wp-kit' ); ?></label></th>
					<td>
						<input type="number" min="60" max="3600" step="60" id="hwk-preview-ttl" name="hwk_settings[preview_ttl]" value="<?php echo esc_attr( (string) $settings['preview_ttl'] ); ?>">
						<p class="description"><?php echo esc_html__( 'Seconds. Between 60 and 3600.', 'headless-wp-kit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Features', 'headless-wp-kit' ); ?></th>
					<td>
						<ul>
							<?php foreach ( $features as $feature => $enabled ) : ?>
								<li><code><?php echo esc_html( (string) $feature ); ?></code>: <?php echo $enabled ? esc_html__( 'enabled', 'headless-wp-kit' ) : esc_html__( 'disabled', 'headless-wp-kit' ); ?></li>
							<?php endforeach; ?>
						</ul>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hwk-redirects"><?php echo esc_html__( 'Redirects', 'headless-wp-kit' ); ?></label></th>
					<td>
						<textarea class="large-text code" rows="6" id="hwk-redirects" name="hwk_redirects" placeholder="/old -> /new"><?php echo esc_textarea( (string) get_option( 'hwk_redirects', '' ) ); ?></textarea>
						<p class="description"><?php echo esc_html__( 'One per line: /old -> /new', 'headless-wp-kit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hwk-retired"><?php echo esc_html__( 'Retired paths', 'headless-wp-kit' ); ?></label></th>
					<td>
						<textarea class="large-text code" rows="4" id="hwk-retired" name="hwk_retired" placeholder="/old-path"><?php echo esc_textarea( (string) get_option( 'hwk_retired', '' ) ); ?></textarea>
						<p class="description"><?php echo esc_html__( 'One path per line. The frontend should answer 410.', 'headless-wp-kit' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>

		<?php if ( 'example' === $profile['slug'] ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'hwk_seed_terms' ); ?>
				<input type="hidden" name="action" value="hwk_seed_terms">
				<p><?php echo esc_html__( 'Create the example sections and formats if they do not exist.', 'headless-wp-kit' ); ?></p>
				<?php submit_button( __( 'Create example terms', 'headless-wp-kit' ), 'secondary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

add_action( 'admin_post_hwk_seed_terms', 'hwk_handle_seed_terms' );

/**
 * Create example terms from the active profile.
 */
function hwk_handle_seed_terms(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You cannot do that.', 'headless-wp-kit' ), '', array( 'response' => 403 ) );
	}

	check_admin_referer( 'hwk_seed_terms' );

	$model = hwk_content_model();
	$terms = isset( $model['terms'] ) && is_array( $model['terms'] ) ? $model['terms'] : array();

	foreach ( $terms as $taxonomy_key => $items ) {
		$taxonomy = isset( $model['taxonomies'][ $taxonomy_key ]['slug'] )
			? (string) $model['taxonomies'][ $taxonomy_key ]['slug']
			: '';

		if ( '' === $taxonomy || ! is_array( $items ) ) {
			continue;
		}

		foreach ( $items as $item ) {
			$name = isset( $item['name'] ) ? (string) $item['name'] : '';
			$slug = isset( $item['slug'] ) ? (string) $item['slug'] : '';

			if ( '' === $name || '' === $slug ) {
				continue;
			}

			if ( ! term_exists( $slug, $taxonomy ) ) {
				wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
			}
		}
	}

	wp_safe_redirect( add_query_arg( 'hwk_seeded', '1', admin_url( 'options-general.php?page=headless-wp-kit' ) ) );
	exit;
}
