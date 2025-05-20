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
	$path = ltrim( $path, '/' );
	$base = rtrim( $base, '/' );

	$combined = $base . '/' . $path;

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