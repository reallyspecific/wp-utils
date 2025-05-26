<?php

namespace ReallySpecific\WP_Util\Updaters\GitHub;

add_filter( 'rs_util_updater_theme_update_uri_github.com', __NAMESPACE__ . '/filter_update_uri', 10, 2 );
add_filter( 'rs_util_updater_plugin_update_uri_github.com', __NAMESPACE__ . '/filter_update_uri', 10, 2 );
add_filter( 'rs_util_updater_package_retrieval_uri_github.com', __NAMESPACE__ . '/filter_package_retrieval_uri', 10, 2 );
add_filter( 'rs_util_updater_package_body_github.com', __NAMESPACE__ . '/filter_package_body', 10, 2 );

function filter_update_uri( $uri, $parsed_repo ) {
	return "https://api.github.com/repos{$parsed_repo['path']}";
}

function filter_package_retrieval_uri( $uri ) {
	return "$uri/releases/latest";
}

function filter_package_body( $body, $plugin ) {
	$package = json_decode( $body, \true );

	

	return [
		'version' => $package['tag_name'],
		'package' => $package['assets'][0]['browser_download_url'],
	];
}
