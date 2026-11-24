<?php
/**
 * Publication guard for the editorial profile.
 *
 * @package Headless_WP_Kit
 */

defined( 'ABSPATH' ) || exit;

if ( ! hwk_feature_enabled( 'editorial' ) ) {
	return;
}

add_filter( 'rest_pre_insert_post', 'hwk_guard_signature', 20, 2 );

/**
 * Reject publishing without direction, review, execution and, for guides, level.
 *
 * @param mixed           $prepared Prepared post data.
 * @param WP_REST_Request $request  Request.
 * @return mixed
 */
function hwk_guard_signature( $prepared, $request ) {
	if ( ! is_object( $prepared ) ) {
		return $prepared;
	}

	$type = (string) ( $prepared->post_type ?? 'post' );

	if ( 'post' !== $type ) {
		return $prepared;
	}

	$requested = (string) ( $request['status'] ?? '' );
	$status    = '' !== $requested ? $requested : (string) ( $prepared->post_status ?? '' );

	if ( ! in_array( $status, array( 'publish', 'future' ), true ) ) {
		return $prepared;
	}

	$post_id = (int) ( $prepared->ID ?? 0 );
	$meta    = $request->get_param( 'meta' );
	$meta    = is_array( $meta ) ? $meta : array();

	$missing = array();

	foreach ( array( 'direction', 'review' ) as $name ) {
		$key = hwk_meta_key( $name );

		if ( '' === $key ) {
			continue;
		}

		$value = array_key_exists( $key, $meta )
			? $meta[ $key ]
			: ( $post_id > 0 ? get_post_meta( $post_id, $key, true ) : '' );

		if ( '' === trim( (string) $value ) ) {
			$missing[] = $name;
		}
	}

	$execution_key = hwk_meta_key( 'execution' );

	if ( '' !== $execution_key ) {
		$execution = array_key_exists( $execution_key, $meta )
			? $meta[ $execution_key ]
			: ( $post_id > 0 ? get_post_meta( $post_id, $execution_key, true ) : array() );

		if ( array() === hwk_sanitize_author_ids( $execution ) ) {
			$missing[] = 'execution';
		}
	}

	if ( hwk_request_is_guide( $post_id, $request ) ) {
		$level_key = hwk_meta_key( 'level' );

		if ( '' !== $level_key ) {
			$level = array_key_exists( $level_key, $meta )
				? $meta[ $level_key ]
				: ( $post_id > 0 ? get_post_meta( $post_id, $level_key, true ) : '' );

			if ( '' === hwk_sanitize_level( $level ) ) {
				$missing[] = 'level';
			}
		}
	}

	if ( array() === $missing ) {
		return $prepared;
	}

	$labels = array();

	foreach ( $missing as $name ) {
		$labels[] = hwk_meta_label( $name ) . ' (' . $name . ')';
	}

	hwk_audit_log(
		'signature.rejected',
		'post',
		$post_id,
		array( 'missing' => $missing )
	);

	return new WP_Error(
		'hwk_signature_required',
		'The post requires a complete signature. Missing: ' . implode( ', ', $labels ) . '.',
		array(
			'status'  => 403,
			'missing' => $missing,
		)
	);
}

/**
 * Whether the request targets the guide format.
 */
function hwk_request_is_guide( int $post_id, WP_REST_Request $request ): bool {
	$model = hwk_content_model();
	$guide = isset( $model['guide_format'] ) && is_array( $model['guide_format'] )
		? $model['guide_format']
		: array();

	if ( empty( $guide['taxonomy'] ) || empty( $guide['slug'] ) ) {
		return false;
	}

	$taxonomy_key = (string) $guide['taxonomy'];
	$taxonomy     = isset( $model['taxonomies'][ $taxonomy_key ]['slug'] )
		? (string) $model['taxonomies'][ $taxonomy_key ]['slug']
		: '';
	$rest_base    = isset( $model['taxonomies'][ $taxonomy_key ]['rest_base'] )
		? (string) $model['taxonomies'][ $taxonomy_key ]['rest_base']
		: '';

	if ( '' === $taxonomy ) {
		return false;
	}

	if ( $post_id > 0 && has_term( (string) $guide['slug'], $taxonomy, $post_id ) ) {
		return true;
	}

	if ( '' === $rest_base ) {
		return false;
	}

	$terms = $request->get_param( $rest_base );

	if ( ! is_array( $terms ) ) {
		return false;
	}

	foreach ( $terms as $term_id ) {
		$term = get_term( absint( $term_id ), $taxonomy );

		if ( $term instanceof WP_Term && (string) $guide['slug'] === $term->slug ) {
			return true;
		}
	}

	return false;
}
