<?php
/**
 * Example editorial profile.
 *
 * It demonstrates the optional content model: authors, sections, topics,
 * formats, signature fields and agent-aware blocks. It intentionally does not
 * carry any site-specific brand or terms.
 *
 * @package Headless_WP_Kit
 */

return array(
	'name'        => 'Example editorial site',
	'description' => 'A generic editorial profile with authors, sections, topics, formats, signature and agent blocks.',
	'features'    => array(
		'preview'      => true,
		'redirects'    => true,
		'audit'        => true,
		'cms_noindex'  => true,
		'editorial'    => true,
		'agent_blocks' => true,
	),
	'content_model' => array(
		'author_post_type' => array(
			'slug'      => 'hwk_author',
			'rest_base' => 'authors',
			'type_meta' => 'hwk_type',
			'labels'    => array(
				'name'          => 'Authors',
				'singular_name' => 'Author',
				'menu_name'     => 'Authors',
				'all_items'     => 'All authors',
				'add_new_item'  => 'Add author',
				'edit_item'     => 'Edit author',
				'new_item'      => 'New author',
				'view_item'     => 'View author',
				'search_items'  => 'Search authors',
				'not_found'     => 'No authors yet',
			),
		),
		'taxonomies'       => array(
			'section' => array(
				'slug'      => 'hwk_section',
				'rest_base' => 'sections',
				'labels'    => array(
					'name'          => 'Sections',
					'singular_name' => 'Section',
					'menu_name'     => 'Sections',
					'all_items'     => 'All sections',
					'edit_item'     => 'Edit section',
					'add_new_item'  => 'Add section',
					'search_items'  => 'Search sections',
				),
			),
			'topic'   => array(
				'slug'      => 'hwk_topic',
				'rest_base' => 'topics',
				'labels'    => array(
					'name'          => 'Topics',
					'singular_name' => 'Topic',
					'menu_name'     => 'Topics',
					'all_items'     => 'All topics',
					'edit_item'     => 'Edit topic',
					'add_new_item'  => 'Add topic',
					'search_items'  => 'Search topics',
				),
			),
			'format'  => array(
				'slug'      => 'hwk_format',
				'rest_base' => 'formats',
				'labels'    => array(
					'name'          => 'Formats',
					'singular_name' => 'Format',
					'menu_name'     => 'Formats',
					'all_items'     => 'All formats',
					'edit_item'     => 'Edit format',
					'add_new_item'  => 'Add format',
					'search_items'  => 'Search formats',
				),
			),
		),
		'meta'             => array(
			'direction'    => array(
				'key'      => 'hwk_direction',
				'type'     => 'string',
				'label'    => 'Direction',
				'input'    => 'text',
				'required' => true,
				'sanitize' => 'text',
			),
			'review'       => array(
				'key'      => 'hwk_review',
				'type'     => 'string',
				'label'    => 'Review',
				'input'    => 'text',
				'required' => true,
				'sanitize' => 'text',
			),
			'execution'    => array(
				'key'      => 'hwk_execution',
				'type'     => 'array',
				'items'    => 'integer',
				'label'    => 'Execution',
				'input'    => 'authors',
				'required' => true,
				'sanitize' => 'author_ids',
			),
			'audience'     => array(
				'key'      => 'hwk_audience',
				'type'     => 'string',
				'label'    => 'Audience',
				'input'    => 'select',
				'options'  => array(
					'human' => 'Human',
					'agent' => 'Agent',
					'both'  => 'Both',
				),
				'sanitize' => 'audience',
			),
			'agents'       => array(
				'key'      => 'hwk_agents',
				'type'     => 'array',
				'items'    => 'string',
				'label'    => 'Agents',
				'input'    => 'textarea_list',
				'sanitize' => 'string_list',
			),
			'level'        => array(
				'key'      => 'hwk_level',
				'type'     => 'string',
				'label'    => 'Level',
				'input'    => 'select',
				'options'  => array(
					'basic'        => 'Basic',
					'intermediate' => 'Intermediate',
					'advanced'     => 'Advanced',
				),
				'sanitize' => 'level',
			),
			'requirements' => array(
				'key'      => 'hwk_requirements',
				'type'     => 'array',
				'items'    => 'string',
				'label'    => 'Requirements',
				'input'    => 'textarea_list',
				'sanitize' => 'string_list',
			),
		),
		'guide_format'     => array(
			'taxonomy' => 'format',
			'slug'     => 'guide',
		),
		'terms'            => array(
			'section' => array(
				array( 'name' => 'Laboratory', 'slug' => 'laboratory' ),
				array( 'name' => 'Engineering', 'slug' => 'engineering' ),
				array( 'name' => 'Operations', 'slug' => 'operations' ),
			),
			'format'  => array(
				array( 'name' => 'Guide', 'slug' => 'guide' ),
				array( 'name' => 'Report', 'slug' => 'report' ),
				array( 'name' => 'Note', 'slug' => 'note' ),
				array( 'name' => 'Evaluation', 'slug' => 'evaluation' ),
			),
		),
	),
);
