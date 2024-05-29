<?php

namespace ReallySpecific\WP_Util;

use \WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get WordPress post by slug
 *
 * @param string $slug
 * @return WP_Post|null
 *
 * @throws \Exception
 */
function get_post_by_slug( string $slug, string $post_type = 'any' ) {
	$post = get_posts( [
		'name'           => $slug,
		'posts_per_page' => 1,
		'post_type'      => $post_type,
		'post_status'    => 'any',
	] );
	if ( empty( $post ) ) {
		return null;
	}
	return $post[0]->to_array();
}