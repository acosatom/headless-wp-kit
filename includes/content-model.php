<?php
/**
 * Optional editorial content model.
 *
 * @package Headless_WP_Kit
 */

defined( 'ABSPATH' ) || exit;

if ( ! hwk_feature_enabled( 'editorial' ) ) {
	return;
}

add_action( 'init', 'hwk_register_content_model', 5 );

/**
 * Register the author post type and the section/topic/format taxonomies.
 */
function hwk_register_content_model(): void {
	$model = hwk_content_model();

	if ( array() === $model ) {
		return;
	}

	$author = isset( $model['author_post_type'] ) && is_array( $model['author_post_type'] )
		? $model['author_post_type']
		: array();

	if ( ! empty( $author['slug'] ) ) {
		register_post_type(
			(string) $author['slug'],
			array(
				'labels'              => isset( $author['labels'] ) ? $author['labels'] : array(),
				'description'         => 'Profile of a person or agent who signs content.',
				'public'              => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => true,
				'rest_base'           => isset( $author['rest_base'] ) ? (string) $author['rest_base'] : 'authors',
				'menu_icon'           => 'dashicons-admin-users',
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
			)
		);
	}

	$taxonomies = isset( $model['taxonomies'] ) && is_array( $model['taxonomies'] ) ? $model['taxonomies'] : array();

	foreach ( $taxonomies as $definition ) {
		if ( ! is_array( $definition ) || empty( $definition['slug'] ) ) {
			continue;
		}

		register_taxonomy(
			(string) $definition['slug'],
			array( 'post' ),
			array(
				'labels'             => isset( $definition['labels'] ) ? $definition['labels'] : array(),
				'description'        => isset( $definition['description'] ) ? (string) $definition['description'] : '',
				'public'             => true,
				'publicly_queryable' => false,
				'hierarchical'       => false,
				'show_ui'            => true,
				'show_in_rest'       => true,
				'rest_base'          => isset( $definition['rest_base'] ) ? (string) $definition['rest_base'] : '',
				'rewrite'            => false,
				'query_var'          => false,
			)
		);
	}

	add_post_type_support( 'post', 'custom-fields' );
}
