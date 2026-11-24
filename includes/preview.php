<?php
/**
 * Signed preview tokens and preview data.
 *
 * @package Headless_WP_Kit
 */

defined( 'ABSPATH' ) || exit;

if ( ! hwk_feature_enabled( 'preview' ) ) {
	return;
}

add_action( 'rest_api_init', 'hwk_register_preview_routes' );
add_filter( 'preview_post_link', 'hwk_filter_preview_post_link', 10, 2 );
add_filter( 'rest_prepare_post', 'hwk_filter_rest_preview_link', 10, 3 );
add_filter( 'rest_prepare_page', 'hwk_filter_rest_preview_link', 10, 3 );
add_action( 'admin_post_hwk_preview', 'hwk_handle_preview_redirect' );
add_action( 'template_redirect', 'hwk_redirect_preview_to_front' );

/**
 * Register preview routes.
 */
function hwk_register_preview_routes(): void {
	register_rest_route(
		'headless/v1',
		'/preview-token/(?P<id>\d+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'hwk_rest_preview_token',
			'permission_callback' => 'hwk_preview_token_permission',
			'args'                => array(
				'id'  => array(
					'type'     => 'integer',
					'required' => true,
				),
				'ttl' => array(
					'type'    => 'integer',
					'default' => hwk_preview_ttl(),
				),
			),
		)
	);

	register_rest_route(
		'headless/v1',
		'/preview/(?P<id>\d+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'hwk_rest_preview',
			'permission_callback' => 'hwk_preview_permission',
			'args'                => array(
				'id'    => array(
					'type'     => 'integer',
					'required' => true,
				),
				'token' => array(
					'type'     => 'string',
					'required' => true,
				),
			),
		)
	);
}

/**
 * Only editors can mint a token.
 *
 * @return true|WP_Error
 */
function hwk_preview_token_permission( WP_REST_Request $request ) {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'hwk_preview_unauthorized', 'Authentication required.', array( 'status' => 401 ) );
	}

	if ( ! current_user_can( 'edit_post', (int) $request['id'] ) ) {
		return new WP_Error( 'hwk_preview_forbidden', 'This credential cannot preview the post.', array( 'status' => 403 ) );
	}

	return true;
}

/**
 * The frontend uses a read credential to fetch preview data.
 *
 * @return true|WP_Error
 */
function hwk_preview_permission() {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'hwk_preview_unauthorized', 'Authentication required.', array( 'status' => 401 ) );
	}

	if ( ! current_user_can( 'read' ) ) {
		return new WP_Error( 'hwk_preview_forbidden', 'This credential cannot read previews.', array( 'status' => 403 ) );
	}

	return true;
}

function hwk_base64url_encode( string $value ): string {
	return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
}

function hwk_base64url_decode( string $value ): string {
	$padding = strlen( $value ) % 4;
	$value   = strtr( $value, '-_', '+/' );
	$value  .= $padding > 0 ? str_repeat( '=', 4 - $padding ) : '';

	return (string) base64_decode( $value, true );
}

/**
 * Sign a preview token.
 *
 * @return array{token: string, expires_at: string}
 */
function hwk_preview_token( int $post_id, ?int $ttl = null, ?int $user_id = null ): array {
	$ttl     = null === $ttl ? hwk_preview_ttl() : max( 60, min( 3600, $ttl ) );
	$expires = time() + $ttl;
	$payload = hwk_base64url_encode(
		(string) wp_json_encode(
			array(
				'id'   => $post_id,
				'user' => null === $user_id ? get_current_user_id() : $user_id,
				'exp'  => $expires,
			)
		)
	);
	$signature = hwk_base64url_encode(
		hash_hmac( 'sha256', $payload, wp_salt( 'auth' ), true )
	);

	return array(
		'token'      => $payload . '.' . $signature,
		'expires_at' => gmdate( 'c', $expires ),
	);
}

/**
 * Validate a token and return its payload.
 *
 * @return array<string, mixed>|null
 */
function hwk_preview_token_data( string $token, int $post_id ): ?array {
	$parts = explode( '.', $token );

	if ( 2 !== count( $parts ) ) {
		return null;
	}

	$payload  = hwk_base64url_decode( $parts[0] );
	$provided = hwk_base64url_decode( $parts[1] );

	if ( '' === $payload || '' === $provided ) {
		return null;
	}

	$expected = hash_hmac( 'sha256', $parts[0], wp_salt( 'auth' ), true );

	if ( ! hash_equals( $expected, $provided ) ) {
		return null;
	}

	$data = json_decode( $payload, true );

	if (
		! is_array( $data )
		|| (int) ( $data['id'] ?? 0 ) !== $post_id
		|| (int) ( $data['exp'] ?? 0 ) < time()
	) {
		return null;
	}

	return $data;
}

/**
 * Token endpoint.
 *
 * @return WP_REST_Response|WP_Error
 */
function hwk_rest_preview_token( WP_REST_Request $request ) {
	$post_id = (int) $request['id'];
	$post    = get_post( $post_id );

	if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		return new WP_Error( 'hwk_preview_not_found', 'The post does not exist.', array( 'status' => 404 ) );
	}

	$token = hwk_preview_token( $post_id, (int) ( $request['ttl'] ?? hwk_preview_ttl() ) );

	hwk_audit_log( 'preview.token', $post->post_type, $post_id );

	$response = rest_ensure_response(
		array(
			'token'        => $token['token'],
			'expires_at'   => $token['expires_at'],
			'preview_path' => sprintf( '/preview/%d/', $post_id ),
		)
	);
	$response->header( 'Cache-Control', 'no-store' );

	return $response;
}

/**
 * Preview data for the frontend.
 *
 * @return WP_REST_Response|WP_Error
 */
function hwk_rest_preview( WP_REST_Request $request ) {
	$post_id = (int) $request['id'];
	$data    = hwk_preview_token_data( (string) $request['token'], $post_id );

	if ( null === $data ) {
		return new WP_Error( 'hwk_preview_unauthorized', 'The token is invalid or expired.', array( 'status' => 401 ) );
	}

	$editor_id = (int) ( $data['user'] ?? 0 );

	if ( $editor_id > 0 && ! user_can( $editor_id, 'edit_post', $post_id ) ) {
		return new WP_Error( 'hwk_preview_forbidden', 'This credential can no longer edit the post.', array( 'status' => 403 ) );
	}

	$post = get_post( $post_id );

	if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		return new WP_Error( 'hwk_preview_not_found', 'The post does not exist.', array( 'status' => 404 ) );
	}

	$autosave = $editor_id > 0 ? wp_get_post_autosave( $post_id, $editor_id ) : false;
	$preview  = $autosave instanceof WP_Post ? $autosave : $post;

	$response = rest_ensure_response( hwk_preview_payload( $post, $preview, $autosave instanceof WP_Post ) );
	$response->header( 'Cache-Control', 'no-store' );

	return $response;
}

/**
 * Build the preview payload.
 *
 * @return array<string, mixed>
 */
function hwk_preview_payload( WP_Post $post, WP_Post $preview, bool $is_autosave ): array {
	$model      = hwk_content_model();
	$meta       = array();
	$meta_defs  = isset( $model['meta'] ) && is_array( $model['meta'] ) ? $model['meta'] : array();

	foreach ( $meta_defs as $name => $definition ) {
		$key = isset( $definition['key'] ) ? (string) $definition['key'] : '';

		if ( '' !== $key ) {
			$meta[ $name ] = get_post_meta( $post->ID, $key, true );
		}
	}

	$authors = array();
	$author  = isset( $model['author_post_type'] ) && is_array( $model['author_post_type'] )
		? $model['author_post_type']
		: array();
	$execution_key = hwk_meta_key( 'execution' );

	if ( $execution_key !== '' && isset( $author['slug'] ) ) {
		$ids = get_post_meta( $post->ID, $execution_key, true );

		foreach ( (array) $ids as $author_id ) {
			$author_post = get_post( (int) $author_id );

			if ( ! $author_post || (string) $author['slug'] !== $author_post->post_type ) {
				continue;
			}

			$authors[] = array(
				'id'    => (int) $author_post->ID,
				'title' => get_the_title( $author_post ),
				'slug'  => $author_post->post_name,
				'type'  => isset( $author['type_meta'] ) ? (string) get_post_meta( $author_post->ID, (string) $author['type_meta'], true ) : '',
			);
		}
	}

	$terms = array();
	$taxonomies = isset( $model['taxonomies'] ) && is_array( $model['taxonomies'] ) ? $model['taxonomies'] : array();

	foreach ( $taxonomies as $name => $definition ) {
		$taxonomy = isset( $definition['slug'] ) ? (string) $definition['slug'] : '';

		if ( '' === $taxonomy ) {
			continue;
		}

		$post_terms = get_the_terms( $post->ID, $taxonomy );
		$terms[ $name ] = array();

		if ( is_array( $post_terms ) ) {
			foreach ( $post_terms as $term ) {
				$terms[ $name ][] = array(
					'slug' => (string) $term->slug,
					'name' => (string) $term->name,
				);
			}
		}
	}

	return array(
		'id'          => (int) $post->ID,
		'type'        => (string) $post->post_type,
		'status'      => (string) $post->post_status,
		'slug'        => (string) $post->post_name,
		'lang'        => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post->ID ) : '',
		'autosave'    => $is_autosave,
		'title'       => get_the_title( $preview ),
		'content'     => wp_kses_post( apply_filters( 'the_content', $preview->post_content ) ),
		'excerpt'     => wp_kses_post( get_the_excerpt( $preview ) ),
		'excerpt_raw' => (string) $preview->post_excerpt,
		'date'        => (string) $post->post_date,
		'modified'    => (string) $preview->post_modified,
		'meta'        => $meta,
		'authors'     => $authors,
		'terms'       => $terms,
	);
}

/**
 * Classic editor preview link.
 */
function hwk_filter_preview_post_link( string $link, WP_Post $post ): string {
	if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		return $link;
	}

	if ( '' === hwk_frontend_url() || ! current_user_can( 'edit_post', $post->ID ) ) {
		return $link;
	}

	return hwk_admin_preview_url( $post->ID );
}

/**
 * Admin-post URL that mints the token on click.
 */
function hwk_admin_preview_url( int $post_id ): string {
	$url = add_query_arg(
		array(
			'action' => 'hwk_preview',
			'post'   => $post_id,
		),
		admin_url( 'admin-post.php' )
	);

	return add_query_arg( '_wpnonce', wp_create_nonce( 'hwk_preview_' . $post_id ), $url );
}

/**
 * Gutenberg preview and View Post link.
 *
 * @param WP_REST_Response $response Response.
 * @param WP_Post          $post     Post.
 * @param WP_REST_Request  $request  Request.
 * @return WP_REST_Response
 */
function hwk_filter_rest_preview_link( $response, $post, $request ) {
	if ( ! $response instanceof WP_REST_Response || ! $post instanceof WP_Post ) {
		return $response;
	}

	if ( 'edit' !== $request->get_param( 'context' ) || '' === hwk_frontend_url() ) {
		return $response;
	}

	if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		return $response;
	}

	$data = $response->get_data();

	if ( ! is_array( $data ) ) {
		return $response;
	}

	$data['link'] = hwk_admin_preview_url( $post->ID );

	$template = apply_filters( 'hwk_permalink_template', hwk_frontend_url() . '/%postname%/', $post );

	if ( is_string( $template ) && '' !== $template && isset( $data['permalink_template'] ) ) {
		$data['permalink_template'] = $template;
	}

	$response->set_data( $data );

	return $response;
}

/**
 * Admin-post handler: mint a token and redirect to the frontend.
 */
function hwk_handle_preview_redirect(): void {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

	check_admin_referer( 'hwk_preview_' . $post_id );

	if ( 0 === $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You cannot preview this post.', 'headless-wp-kit' ), '', array( 'response' => 403 ) );
	}

	$post = get_post( $post_id );

	if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		wp_die( esc_html__( 'The post does not exist.', 'headless-wp-kit' ), '', array( 'response' => 404 ) );
	}

	$frontend = hwk_frontend_url();

	if ( '' === $frontend ) {
		wp_die( esc_html__( 'Set the Frontend URL in Headless WP Kit settings.', 'headless-wp-kit' ), '', array( 'response' => 500 ) );
	}

	$token = hwk_preview_token( $post_id );
	$url   = $frontend . '/preview/' . $post_id . '/?token=' . rawurlencode( $token['token'] );

	hwk_audit_log( 'preview.redirect', $post->post_type, $post_id );

	wp_redirect( $url, 302, 'Headless WP Kit' );
	exit;
}

/**
 * Safety net: any ?preview=true request on the CMS goes to the frontend.
 */
function hwk_redirect_preview_to_front(): void {
	if ( ! is_user_logged_in() || ( ! is_preview() && ! isset( $_GET['preview'] ) ) ) {
		return;
	}

	$post_id = (int) get_queried_object_id();

	if ( $post_id <= 0 && isset( $_GET['p'] ) ) {
		$post_id = absint( $_GET['p'] );
	}

	if ( $post_id <= 0 && isset( $_GET['page_id'] ) ) {
		$post_id = absint( $_GET['page_id'] );
	}

	if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$frontend = hwk_frontend_url();

	if ( '' === $frontend ) {
		return;
	}

	$token = hwk_preview_token( $post_id );
	$url   = $frontend . '/preview/' . $post_id . '/?token=' . rawurlencode( $token['token'] );

	wp_redirect( $url, 302, 'Headless WP Kit' );
	exit;
}
