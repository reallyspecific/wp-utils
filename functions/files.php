<?php

namespace ReallySpecific\WP_Util\Filesystem;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Creates a directory, recursively building subfolders as needed
 *
 * @param [type] $path
 * @return bool mkdir was successful
 */
function recursive_mk_dir( $path ): bool {
	$parent_path = dirname( $path );
	if ( ! is_dir( $parent_path ) ) {
		$success = recursive_mk_dir( $parent_path );
		if ( ! $success ) {
			return false;
		}
	}
	if ( ! is_dir( $path ) ) {
		return mkdir( $path, 0755 );
	}
	return true;
}

/**
 * Takes a relative URL and returns an absolute URL based
 * on the base URL passed to the constructor.
 *
 * @param string $path The relative URL.
 * @return string The absolute URL.
 */
function join_path( $base, $path ) {
	if ( empty( $path ) ) {
		return $base;
	}
	if ( substr( $path, 0, 1 ) === '/' ) {
		return $base . $path;
	}
	if ( substr( $path, 0, 1 ) === '.' ) {
		return $base . substr( $path, 1 );
	}

	$url = parse_url( $path );

	$cleaned = $url['scheme'] . '://' . $url['host'] . $url['path'];
	if ( $url['query'] ) {
		$cleaned .= '?' . $url['query'];
	}

	return $cleaned;
}