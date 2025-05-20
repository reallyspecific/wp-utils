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

function recursive_rm_dir( $src ) {
	$dir = opendir( $src );
	while ( false !== ( $file = readdir( $dir ) ) ) {
		if ( ( $file != '.' ) && ( $file != '..' ) ) {
			$full = $src . '/' . $file;
			if ( is_dir( $full ) ) {
				recursive_rm_dir( $full );
			}
			else {
				unlink( $full );
			}
		}
	}
	closedir( $dir );
	rmdir( $src );
}

/**
 * Takes a relative URL and returns an absolute URL based
 * on the base URL passed to the constructor.
 *
 * @param string $path The relative URL.
 * @return string The absolute URL.
 */
function join_path( ...$paths ) {
	if ( empty( $paths ) ) {
		return '';
	}
	$combined = rtrim( array_shift( $paths ), '/' );
	while ( count( $paths ) > 0 ) {
		$combined .= '/' . ltrim( array_shift( $paths ), '/' );
	}
	return canonical_path( $combined );
}

function canonical_path( $path, $separator = '/' )
{
	$canonical = [];
	$path = explode( $separator, $path );

	foreach ( $path as $segment ) {
		switch ( $segment ) {
			case '.':
				continue;
			case '..':
				array_pop( $canonical );
				break;
			default:
				$canonical[] = $segment;
				break;
		}
	}

	return join( $separator, $canonical );
}

function get_extension_from_mime_type( $mime_type ) {

	$mime_list = wp_get_mime_types();

	foreach ( $mime_list as $extensions => $mime ) {
		if ( $mime_type === $mime ) {
			return $extensions;
		}
	}

	return null;
}
