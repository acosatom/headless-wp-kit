<?php
/**
 * Core headless profile: preview, redirects, audit and CMS noindex.
 *
 * @package Headless_WP_Kit
 */

return array(
	'name'          => 'Core headless',
	'description'   => 'Preview tokens, redirects, audit and CMS noindex. No editorial content model.',
	'features'      => array(
		'preview'      => true,
		'redirects'    => true,
		'audit'        => true,
		'cms_noindex'  => true,
		'editorial'    => false,
		'agent_blocks' => false,
	),
	'content_model' => array(),
);
