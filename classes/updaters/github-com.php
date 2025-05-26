<?php

namespace ReallySpecific\WP_Util\Updaters\GitHub;

add_filter( 'rs_util_updater_theme_update_uri_github.com', __NAMESPACE__ . '\\filter_update_uri', 10, 1 );
add_filter( 'rs_util_updater_plugin_update_uri_github.com', __NAMESPACE__ . '\\filter_update_uri', 10, 1 );
add_filter( 'rs_util_updater_package_retrieval_uri_github.com', __NAMESPACE__ . '\\filter_package_retrieval_uri', 10, 2 );
add_filter( 'rs_util_updater_package_body_github.com', __NAMESPACE__ . '\\filter_package_body', 10, 2 );

function filter_update_uri( $uri ) {
	$path = parse_url( $uri, PHP_URL_PATH );
	return "https://api.github.com/repos{$path}";
}

function filter_package_retrieval_uri( $uri ) {
	return "$uri/releases/latest";
}

function filter_package_body( $body, $plugin )
{
	$package = json_decode($body, \true);

	if (empty($package['tag_name']) || empty($package['zipball_url'])) {
		return $body;
	}

	$meta_file_uri = filter_update_uri( $plugin->uri ) . '/contents/' . $plugin->basename;
	$meta_file_uri = add_query_arg( 'ref', $package['tag_name'], $meta_file_uri );
	$params = [];
	if ( $plugin->token ) {
		$params['headers'] = [
			'Authorization' => 'Bearer ' . $plugin->token,
		];
	}
	$request = wp_remote_get( $meta_file_uri, $params );
	if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) !== 200 ) {
		return $body;
	}

	$response = wp_remote_retrieve_body( $request );
	
	$meta_file = json_decode($response, \true);
	$contents  = base64_decode( $meta_file['content'] );

	$meta_file = wp_tempnam( $plugin->basename );
	file_put_contents( $meta_file, $contents );
	
	$release = get_file_data( $meta_file, [] );
	
	unlink( $meta_file );
	
	return $release;
}

