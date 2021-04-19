/**
 * Plugin Export Script
 *
 * Exports the production-ready plugin with only the files and folders needed for
 * uploading to a site or zipping. Edit the `files` or `folders` variables if
 * you need to change something.
 *
 * This configuration is a copy of the configuration from https://github.com/justintadlock/mythic/
 * modified to fit Pressmodo's plugins purposes.
 */

// Import required packages.
const mix     = require( 'laravel-mix' );
const rimraf  = require( 'rimraf' );
const fs      = require( 'fs' );

// Folder name to export the files to.
let exportPath = 'theme-demo-exporter';

// Plugin root-level files to include.
let files = [
	'theme-demo-exporter.php',
	'command.php',
];

// Folders to include.
let folders = [
	'src',
	'vendor',
];

// Delete the previous export to start clean.
rimraf.sync( exportPath );

// Loop through the root files and copy them over.
files.forEach( file => {

	if ( fs.existsSync( file ) ) {
		mix.copy( file, `${exportPath}/${file}` );
	}
} );

// Loop through the folders and copy them over.
folders.forEach( folder => {

	if ( fs.existsSync( folder ) ) {
		mix.copyDirectory( folder, `${exportPath}/${folder}` );
	}
} );

// Delete the `vendor/bin` and `vendor/composer/installers` folder, which can
// get left over, even in production. Mix will also create an additional
// `mix-manifest.json` file in the root, which we don't need.
mix.then( () => {

	let files = [
		'mix-manifest.json',
		`${exportPath}/vendor/bin`,
		`${exportPath}/vendor/composer/installers`,
		`${exportPath}/vendor/composer/installers`,
		`${exportPath}/dist/mix-manifest.json`,
		`${exportPath}/dist/mix-js.map`,
		`${exportPath}/dist/svg`,
		`${exportPath}/vendor/dealerdirect`,
		`${exportPath}/vendor/phpcompatibility`,
		`${exportPath}/vendor/squizlabs`,
		`${exportPath}/vendor/wp-coding-standards`,
		`${exportPath}/vendor/**/.git`,
		`${exportPath}/vendor/**/.editorconfig`,
		`${exportPath}/vendor/**/.gitignore`,
		`${exportPath}/vendor/**/composer.json`,
		`${exportPath}/vendor/**/composer.lock`,
		`${exportPath}/vendor/**/LICENSE`,
		`${exportPath}/vendor/**/phpcs.xml`,
		`${exportPath}/vendor/**/readme.md`,
		`${exportPath}/vendor/**/.gitattributes`,
		`${exportPath}/dist/**/*.map`,
		`${exportPath}/vendor/**/*.md`,
		`${exportPath}/vendor/**/*.yml`,
		`${exportPath}/vendor/**/*.yaml`,
		`${exportPath}/vendor/**/*.dist`,
		`${exportPath}/vendor/**/tests`,
		`${exportPath}/vendor/**/.github`,
	];

	files.forEach( file => {
		rimraf.sync( file );
	} );
} );
