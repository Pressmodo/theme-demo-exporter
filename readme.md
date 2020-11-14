# WP CLI Blurify

WP CLI command to blur all images under the `wp-content/uploads` folder. Useful when creating demo content for WordPress themes and can't export "real" images due to copyright.

Before blurring all images, a copy of the `uploads` folder is made under `wp-content`.

## Install

WP CLI Blurify requires Composer and WP-CLI to function.

```
wp package install pressmodo/wp-cli-blurify
```

## Commands

#### Blur all images

```
wp blurify blur
```

#### Blur all images & disable backup

```
wp blurify blue --backup=false
```