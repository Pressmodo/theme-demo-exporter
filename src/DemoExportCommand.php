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

use Exception;
use PhpSchool\CliMenu\Action\GoBackAction;
use PhpSchool\CliMenu\Builder\CliMenuBuilder;
use PhpSchool\CliMenu\CliMenu;
use PhpZip\ZipFile;
use Pressmodo\DB\DatabasePrefixer;
use Symfony\Component\Filesystem\Filesystem;
use WP_CLI;

/**
 * Demo export command
 */
class DemoExportCommand {

	protected $demoDatabasePrefix = 'demo_';

	protected $requiredPlugins = [];

	/**
	 * Start the command when invoked.
	 *
	 * @return void
	 */
	public function __invoke() {

		$menu = ( $builder = new CliMenuBuilder() )
			->setTitle( 'Pressmodo Theme Demo Exporter' )
			->addItem( 'Export database', $this->exportDatabase() )
			->addItem( 'Restore database', $this->restoreDatabase() )
			->addItem( 'Export uploads folder', $this->exportUploads() )
			->addSubMenuFromBuilder( 'Setup configuration file', $this->setupConfigurationFile() )
			->addItem( 'Create .zip file', $this->createPackage() )
			->addLineBreak( '-' )
			->setPadding( 2, 4 )
			->setWidth( $builder->getTerminal()->getWidth() )
			->build();

		$menu->open();
	}

	/**
	 * Process the database export.
	 *
	 * @return \callable
	 */
	private function exportDatabase() {

		return function ( CliMenu $menu ) {

			$menu->close();

			try {
				$prefix = ( new DatabasePrefixer( $this->demoDatabasePrefix ) )->init();
				$this->updateWPConfigPrefixValue( $this->demoDatabasePrefix );
			} catch ( Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}

			WP_CLI::line( 'Replaced database prefix with demo prefix.' );

			$wpContentPath = trailingslashit( WP_CONTENT_DIR );

			$dump = \Spatie\DbDumper\Databases\MySql::create()
					->setDbName( DB_NAME )
					->setUserName( DB_USER )
					->setPassword( DB_PASSWORD )
					->dumpToFile( $wpContentPath . 'demo.sql' );

			\WP_CLI::line( '' );
			\WP_CLI::success( sprintf( 'Database dumped in folder %s', $wpContentPath . 'demo.sql' ) );

		};

	}

	/**
	 * Restore the prefix to the original wp_ value.
	 *
	 * @return \callable
	 */
	private function restoreDatabase() {

		return function ( CliMenu $menu ) {

			$menu->close();

			try {
				$prefix = ( new DatabasePrefixer( 'wp_' ) )->init();
				$this->updateWPConfigPrefixValue( 'wp_' );
			} catch ( Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}

			\WP_CLI::success( 'Database prefix successfully restored.' );

		};

	}

	/**
	 * Update table prefix variable in wp-config.php file.
	 *
	 * @param string $newPrefix new prefix to use.
	 * @return void
	 */
	private function updateWPConfigPrefixValue( $newPrefix ) {

		$wpConfigPath     = \WP_CLI\Utils\locate_wp_config(); // we know this is valid, because wp-cli won't run if it's not
		$wpConfigContents = file_get_contents( $wpConfigPath );
		$searchPattern    = '/(\$table_prefix\s*=\s*)([\'"]).+?\\2(\s*;)/';
		$replacePattern   = "\${1}'{$newPrefix}'\${3}";
		$wpConfigContents = preg_replace( $searchPattern, $replacePattern, $wpConfigContents, - 1, $numberReplacements );

		if ( 0 === $numberReplacements ) {
			throw new Exception( 'Failed to replace `$table_prefix` in `wp-config.php`.' );
		}

		if ( ! file_put_contents( $wpConfigPath, $wpConfigContents ) ) {
			throw new Exception( 'Failed to update updated `wp-config.php` file.' );
		}

	}

	/**
	 * Export uploads folder.
	 *
	 * @return \callable
	 */
	private function exportUploads() {

		return function ( CliMenu $menu ) {

			$uploadsPath         = wp_upload_dir();
			$uploadsPath         = $uploadsPath['basedir'];
			$uploadsPathCopyPath = trailingslashit( WP_CONTENT_DIR ) . 'uploads_demo';

			$filesystem = new Filesystem();

			$filesystem->remove( [ $uploadsPathCopyPath, trailingslashit( get_home_path() ) . 'demo.zip' ] );

			try {
				$filesystem->mirror( $uploadsPath, $uploadsPathCopyPath );
			} catch ( IOExceptionInterface $exception ) {
				$menu->close();
				WP_CLI::error( $exception->getMessage() );
			}

			sleep( 1 );

			$menu->confirm( 'Demo uploads folder successfully created.' )->display( 'Ok' );

		};

	}

	/**
	 * Create the config.json file.
	 *
	 * @return CliMenuBuilder
	 */
	private function setupConfigurationFile() {

		$builder = ( new CliMenuBuilder() )->setTitle( 'Select the plugins required for the demo import' );
		$plugins = get_plugins();

		foreach ( $plugins as $slug => $plugin ) {
			$builder->addCheckboxItem(
				$plugin['Name'],
				function ( CliMenu $menu ) use ( &$selectedPlugins ) {
					$this->requiredPlugins[] = $menu->getSelectedItem()->getText();
				}
			);
		}

		$builder->addLineBreak( '-' );

		$builder->addItem(
			'Make configuration file',
			function( CliMenu $menu ) {

				$pluginsConfig = [];

				foreach ( $this->requiredPlugins as $pluginName ) {
					foreach ( get_plugins() as $slug => $plugin ) {
						if ( isset( $plugin['Name'] ) && $plugin['Name'] === $pluginName ) {
							$pluginsConfig[ $slug ] = $plugin;
							break;
						}
					}
				}

				$configMetadata = [
					'domain'  => esc_url( home_url() ),
					'theme'   => get_option( 'stylesheet' ),
					'plugins' => $pluginsConfig,
				];

				$filesystem = new Filesystem();

				$filesystem->dumpFile( trailingslashit( WP_CONTENT_DIR ) . 'config.json', wp_json_encode( $configMetadata ) );

				$menu->confirm( 'Demo configuration file successfully created.' )->display( 'Ok' );

			}
		);

		$builder->addLineBreak( '-' );

		$builder->addItem( 'Return to parent menu', new GoBackAction() );

		return $builder;

	}

	/**
	 * Create demo package.
	 *
	 * @return \callable
	 */
	private function createPackage() {

		return function ( CliMenu $menu ) {

			$wpContentPath = trailingslashit( WP_CONTENT_DIR );

			$filesystem = new Filesystem();

			$zipFile = new ZipFile();

			$zipFile->addFile( $wpContentPath . 'demo.sql' );

			$zipFile->addDirRecursive( trailingslashit( WP_CONTENT_DIR ) . 'uploads_demo', 'uploads_demo' );

			if ( $filesystem->exists( trailingslashit( WP_CONTENT_DIR ) . 'fonts' ) ) {
				$zipFile->addDirRecursive( trailingslashit( WP_CONTENT_DIR ) . 'fonts', 'fonts' );
			}

			$zipFile->addDirRecursive( trailingslashit( WP_CONTENT_DIR ) . 'uploads_demo', 'uploads_demo' );

			$zipFile->addFile( $wpContentPath . 'config.json' );

			$zipFile->saveAsFile( 'demo.zip' );

			$zipFile->close();

			$menu->close();

			WP_CLI::success( 'Demo package successfully created.' );

		};

	}

}
