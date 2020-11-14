# Pressmodo theme demo exporter

WP CLI command that creates a .zip file containing a database dump together with a copy of the uploads folder.

## Install

Requires Composer and WP-CLI to function.

##### Clone the repository into the demo site plugin's folder.

```
git clone git@github.com:Pressmodo/theme-demo-exporter.git
```

##### Navigate to the newly cloned repository folder.

```
cd theme-demo-exporter
```

##### Install dependencies via composer

```
composer install
```

##### Activate the plugin onto the site.

## Commands

##### Create a demo package

```
wp demo-export export
```
