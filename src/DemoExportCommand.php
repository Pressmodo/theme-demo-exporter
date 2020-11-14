<?php
/**
 * Setup the wp-cli command.
 *
 * @package   theme-demo-exporter
 * @author    Alessandro Tesoro <hello@pressmodo.com>
 * @copyright 2020 Alessandro Tesoro
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://pressmodo.com
 */

namespace Pressmodo\CLI;

use PhpZip\ZipFile;
use Symfony\Component\Filesystem\Filesystem;
use WP_CLI;

/**
 * Demo export command
 */
class DemoExportCommand {

	/**
	 * Export the database, copy the uploads folder and then zip it up.
	 *
	 * @return void
	 */
	public function export() {

		$wpContentPath = trailingslashit( WP_CONTENT_DIR );

		$dump = \Spatie\DbDumper\Databases\MySql::create()
					->setDbName( DB_NAME )
					->setUserName( DB_USER )
					->setPassword( DB_PASSWORD )
					->dumpToFile( $wpContentPath . 'demo.sql' );

		\WP_CLI::line( '' );
		\WP_CLI::log( sprintf( 'Database dumped in folder %s', $wpContentPath . 'demo.sql' ) );

		$uploadsPath         = wp_upload_dir();
		$uploadsPath         = $uploadsPath['basedir'];
		$uploadsPathCopyPath = trailingslashit( WP_CONTENT_DIR ) . 'uploads_demo';

		$filesystem = new Filesystem();

		$filesystem->remove( [ $uploadsPathCopyPath, trailingslashit( get_home_path() ) . 'demo.zip' ] );

		WP_CLI::log( 'Removed any previous demo uploads folder and demo package.' );

		try {

			$filesystem->mirror( $uploadsPath, $uploadsPathCopyPath );

			WP_CLI::log( 'Created uploads demo folder.' );

		} catch ( IOExceptionInterface $exception ) {
			WP_CLI::error( $exception->getMessage() );
		}

		$zipFile = new ZipFile();

		$zipFile->addFile( $wpContentPath . 'demo.sql' );

		WP_CLI::log( 'Added database dump to zip file.' );

		$zipFile->addDirRecursive( trailingslashit( WP_CONTENT_DIR ) . 'uploads_demo', 'uploads_demo' );

		WP_CLI::log( 'Added uploads folder to zip file.' );

		$zipFile->saveAsFile( 'demo.zip' );
		$zipFile->close();

		WP_CLI::log( 'Zip file saved.' );

		WP_CLI::line( '' );
		WP_CLI::success( 'Demo export completed' );

	}

}
