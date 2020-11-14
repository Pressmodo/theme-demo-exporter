<?php
/**
 * Plugin Name:     Theme demo exporter
 * Plugin URI:      https://sematico.com
 * Description:     Pressmodo theme demo export tool.
 * Author:          Alessandro Tesoro
 * Author URI:      https://sematico.com
 * Text Domain:     wp-cli-blurify
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package theme-demo-exporter
 * @author Sematico LTD
 */

namespace Pressmodo\CLI;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require dirname( __FILE__ ) . '/vendor/autoload.php';
}

require_once dirname( __FILE__ ) . '/command.php';
