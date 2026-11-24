<?php
/**
 * Meta fields for the editorial profile.
 *
 * @package Headless_WP_Kit
 */

defined( 'ABSPATH' ) || exit;

if ( ! hwk_feature_enabled( 'editorial' ) ) {
	return;
}

add_action( 'init', 'hwk_register_editorial_meta', 6 );
add_action( 'rest_api_init', 'hwk_register_excerpt_raw' );

/**
 * Register profile meta on posts and the author type meta.
 */
function hwk_register_editorial_meta(): void {
	$model = hwk_content_model();
	$meta  = isset( $model['meta'] ) && is_array( $model['meta'] ) ? $model['meta'] : array();
	$author = isset( $model['author_post_type'] ) && is_array( $model['author_post_type'] )
		? $model['author_post_type']
		: array();

	$can_edit = static function (): bool {
		return current_user_can( 'edit_posts' );
	};

	foreach ( $meta as $definition ) {
		if ( ! is_array( $definition ) || empty( $definition['key'] ) ) {
			continue;
		}

		$key      = (string) $definition['key'];
		$type     = isset( $definition['type'] ) ? (string) $definition['type'] : 'string';
		$label    = isset( $definition['label'] ) ? (string) $definition['label'] : $key;
		$sanitize = isset( $definition['sanitize'] ) ? (string) $definition['sanitize'] : 'text';

		$schema = array(
			'type'        => $type,
			'description' => $label,
		);

		if ( 'array' === $type ) {
			$schema['items'] = array(
				'type' => isset( $definition['items'] ) ? (string) $definition['items'] : 'string',
			);
		}

		if ( isset( $definition['options'] ) && is_array( $definition['options'] ) ) {
			$schema['enum'] = array_keys( $definition['options'] );
		}

		$args = array(
			'type'              => $type,
			'description'       => $label,
			'single'            => true,
			'show_in_rest'      => array( 'schema' => $schema ),
			'sanitize_callback' => hwk_meta_sanitizer( $sanitize ),
			'auth_callback'     => $can_edit,
		);

		// An empty default is not a valid enum value; omit it and let the
		// sanitizer enforce the allowed options.
		if ( ! isset( $definition['options'] ) ) {
			$args['default'] = hwk_meta_default( $definition );
		}

		register_post_meta( 'post', $key, $args );
	}

	if ( ! empty( $author['slug'] ) && ! empty( $author['type_meta'] ) ) {
		register_post_meta(
			(string) $author['slug'],
			(string) $author['type_meta'],
			array(
				'type'              => 'string',
				'description'       => 'Human or agent.',
				'single'            => true,
				'default'           => 'human',
				'show_in_rest'      => array(
					'schema' => array(
						'type' => 'string',
						'enum' => array( 'human', 'agent' ),
					),
				),
				'sanitize_callback' => 'hwk_sanitize_author_type',
				'auth_callback'     => $can_edit,
			)
		);
	}
}

/**
 * Default value by meta type.
 *
 * @param array<string, mixed> $definition Meta definition.
 * @return mixed
 */
function hwk_meta_default( array $definition ) {
	$type = isset( $definition['type'] ) ? (string) $definition['type'] : 'string';

	return match ( $type ) {
		'array'   => array(),
		'integer' => 0,
		'boolean' => false,
		default   => '',
	};
}

/**
 * Sanitizer callback by name.
 */
function hwk_meta_sanitizer( string $name ): string {
	return match ( $name ) {
		'author_ids'    => 'hwk_sanitize_author_ids',
		'audience'      => 'hwk_sanitize_audience',
		'level'         => 'hwk_sanitize_level',
		'string_list'   => 'hwk_sanitize_string_list',
		'textarea'      => 'sanitize_textarea_field',
		default         => 'sanitize_text_field',
	};
}

/**
 * Sanitize an audience value.
 *
 * @param mixed $value Raw value.
 */
function hwk_sanitize_audience( $value ): string {
	return in_array( $value, array( 'human', 'agent', 'both' ), true ) ? (string) $value : 'human';
}

/**
 * Sanitize a level value.
 *
 * @param mixed $value Raw value.
 */
function hwk_sanitize_level( $value ): string {
	return in_array( $value, array( 'basic', 'intermediate', 'advanced' ), true ) ? (string) $value : '';
}

/**
 * Sanitize an author type.
 *
 * @param mixed $value Raw value.
 */
function hwk_sanitize_author_type( $value ): string {
	return in_array( $value, array( 'human', 'agent' ), true ) ? (string) $value : 'human';
}

/**
 * Sanitize a list of author IDs.
 *
 * @param mixed $value Raw value.
 * @return int[]
 */
function hwk_sanitize_author_ids( $value ): array {
	if ( ! is_array( $value ) ) {
		$value = preg_split( '/[\s,]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
	}

	$model  = hwk_content_model();
	$author = isset( $model['author_post_type']['slug'] ) ? (string) $model['author_post_type']['slug'] : '';
	$ids    = array();

	foreach ( (array) $value as $candidate ) {
		$id = absint( $candidate );

		if ( $id > 0 && '' !== $author && get_post_type( $id ) === $author ) {
			$ids[ $id ] = $id;
		}
	}

	return array_values( $ids );
}

/**
 * Sanitize a list of strings.
 *
 * @param mixed $value Raw value.
 * @return string[]
 */
function hwk_sanitize_string_list( $value ): array {
	if ( ! is_array( $value ) ) {
		$value = preg_split( '/\r\n|\r|\n/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
	}

	$items = array_map( 'sanitize_text_field', (array) $value );

	return array_values( array_unique( array_filter( $items, static fn( $item ) => '' !== $item ) ) );
}

/**
 * Publish the raw excerpt.
 */
function hwk_register_excerpt_raw(): void {
	foreach ( array( 'post', 'page' ) as $type ) {
		register_rest_field(
			$type,
			'excerpt_raw',
			array(
				'get_callback' => static function ( $object ) {
					$post = get_post( (int) $object['id'] );

					return $post ? (string) $post->post_excerpt : '';
				},
				'schema'       => array(
					'description' => 'Hand-written excerpt; empty when WordPress generates it from the body.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
	}
}
