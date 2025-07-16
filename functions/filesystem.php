<?php

namespace ReallySpecific\Utils\Filesystem;

use function ReallySpecific\Utils\debug;
use function ReallySpecific\Utils\Assets\path as utils_assets_dir;
use ZipArchive;
use Exception;

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
 * Recursively removes a directory and its contents.
 *
 * @param string $src The directory to remove.
 * @return void
 */
function recursive_rm_dir( string $src ) : void {
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
 * Recursively copies a directory and its contents.
 *
 * @param string $source_folder
 * @param string $destination_folder
 * @param bool $verbose Whether to output filenames as they are copied.
 * @return void
 * @throws Exception If the destination folder already exists.
 */
function recursive_copy_dir( string $source_folder, string $destination_folder, bool $verbose = false, string $chmod_flags = null ) : void {

	if ( file_exists( $destination_folder ) ) {
		throw new Exception( "Destination folder `$destination_folder` already exists" );
	}
	mkdir( $destination_folder );

	$directory = opendir( $source_folder );

	while (( $file = readdir($directory)) !== false ) {
		if ($file === '.' || $file === '..' || $file === 'node_modules' || $file === 'tests' ) {
			continue;
		}

		if ( is_dir( "$source_folder/$file" ) === true ) {
			recursive_copy_dir( "$source_folder/$file", "$destination_folder/$file", $verbose );
		} else {
			copy( "$source_folder/$file", "$destination_folder/$file" );
			if ( ! empty( $chmod_flags ) ) {
				chmod( "$destination_folder/$file", $chmod_flags );
			}
			if ( $verbose ) {
				echo "Copying $destination_folder/$file\n"; 
			}
		}
	}

	closedir( $directory );

}

/**
 * Takes multiple URLs or file paths and combines them into a single string.
 *
 * @param string ...$paths paths to join, only the first can be absolute.
 * @return string The combined paths
 */
function join_path( ...$paths ) : string {
	if ( empty( $paths ) ) {
		return '';
	}
	$combined = rtrim( array_shift( $paths ), '/' );
	while ( count( $paths ) > 0 ) {
		$combined .= '/' . ltrim( array_shift( $paths ), '/' );
	}
	return canonical_path( $combined );
}

/**
 * Reduces a path to its canonical form by removing
 * relative segments and normalizing the path.
 *
 * @param string $path
 * @param string $separator
 * @return string
 */
function canonical_path( string $path, string $separator = '/' ) : string {
	$canonical = [];
	$path = explode( $separator, $path );

	foreach ( $path as $segment ) {
		switch ( $segment ) {
			case '.':
				break;
			case '..':
				if ( count( $canonical ) ) {
					array_pop( $canonical );
				}
				break;
			default:
				$canonical[] = $segment;
				break;
		}
	}

	return rtrim( join( $separator, $canonical ), $separator );
}

function get_extension_from_mime_type( string $mime_type ) : ?string {

	// wordpress's mime list is too limited, we gonna use our own

	$mime_list = wp_cache_get( 'mime_types', 'rs_wp_util' );
	if ( ! $mime_list ) {
		$csv_list = fopen( utils_assets_dir() . '/mime-types.csv', 'r' );
		$mime_list = [];
		while ( ( $line = fgetcsv( $csv_list ) ) !== false ) {
			if ( isset( $mime_list[ $line[1] ] ) ) {
				$mime_list[ $line[1] ] .= '|' . $line[0];
			} else {
				$mime_list[ $line[1] ] = $line[0];
			}
		}
		wp_cache_set( 'mime_types', $mime_list, 'rs_wp_util' );
	}

	return $mime_list[ $mime_type ] ?? null;
}

function make_zip_from_folder( string $source_folder, string $destination_zip_file ) : bool {

	$zip_archive = new ZipArchive();

	if ( file_exists( $destination_zip_file ) ) {
		unlink( $destination_zip_file );
	}

	if ( $zip_archive->open( $destination_zip_file, ( ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) !== true ) {
		return debug( false, 'Failed to create zip archive' );
	}
	
	recursive_add_dir_to_zip( $zip_archive, $source_folder );
	if ( $zip_archive->status != ZIPARCHIVE::ER_OK ) {
		return debug( false, 'Failed to write files to zip' );
	}

	$zip_archive->close();

	return true;

}

function recursive_add_dir_to_zip( &$zip_archive, string $source_folder, string $rel = '/' ): void {
	$dir = opendir( $source_folder );
	if ( ! $dir ) {
		return;
	}
	while ( false !== ( $file = readdir( $dir ) ) ) {
		if ( ( $file !== '.' ) && ( $file !== '..' ) ) {
			$full = $source_folder . '/' . $file;
			if ( is_dir( $full ) ) {
				recursive_add_dir_to_zip( $zip_archive, $full, $rel . $file . '/' );
			} else {
				$zip_archive->addFile( $full, $rel . $file );
			}
		}
	}
	closedir( $dir );
}

function get_classes_from_dir( string $directory_path, string $namespace = '' ) : array {

    if ( ! is_dir( $directory_path ) ) {
        throw new Exception( 'Not a directory: ' . $directory_path );
    }
    $dir       = opendir( $directory_path );
    $classes = [];
    while ( false !== ( $file = readdir( $dir ) ) ) {
        if ( str_starts_with( $file, '.' ) ) {
            continue;
        }
        if ( ! str_ends_with( $file, '.php' ) ) {
            continue;
        }
        $class_name = basename( $file, '.php' );

        $classes[ $class_name ] = $namespace . '\\' . $class_name;
    }

    return $classes;

}