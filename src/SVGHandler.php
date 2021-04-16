<?php
/**
 * Helper methods to sanitize svg files.
 *
 * @package   theme-demo-exporter
 * @author    Alessandro Tesoro <hello@pressmodo.com>
 * @copyright 2020 Alessandro Tesoro
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://pressmodo.com
 */

namespace Pressmodo\CLI;

/**
 * SVG Handling helper methods.
 */
class SVGHandler {

	/**
	 * @var \DOMDocument
	 */
	private $svg_dom = null;

	/**
	 * is_allowed_tag
	 *
	 * @param $element
	 *
	 * @return bool
	 */
	private function is_allowed_tag( $element ) {
		static $allowed_tags = false;
		if ( false === $allowed_tags ) {
			$allowed_tags = $this->get_allowed_elements();
		}

		$tag_name = $element->tagName; // phpcs:ignore -- php DomDocument

		if ( ! in_array( strtolower( $tag_name ), $allowed_tags ) ) {
			$this->remove_element( $element );
			return false;
		}

		return true;
	}

	private function remove_element( $element ) {
		$element->parentNode->removeChild( $element ); // phpcs:ignore -- php DomDocument
	}

	/**
	 * is_a_attribute
	 *
	 * @param $name
	 * @param $check
	 *
	 * @return bool
	 */
	private function is_a_attribute( $name, $check ) {
		return 0 === strpos( $name, $check . '-' );
	}

	/**
	 * is_remote_value
	 *
	 * @param $value
	 *
	 * @return string
	 */
	private function is_remote_value( $value ) {
		$value          = trim( preg_replace( '/[^ -~]/xu', '', $value ) );
		$wrapped_in_url = preg_match( '~^url\(\s*[\'"]\s*(.*)\s*[\'"]\s*\)$~xi', $value, $match );
		if ( ! $wrapped_in_url ) {
			return false;
		}

		$value = trim( $match[1], '\'"' );
		return preg_match( '~^((https?|ftp|file):)?//~xi', $value );
	}

	/**
	 * has_js_value
	 *
	 * @param $value
	 *
	 * @return false|int
	 */
	private function has_js_value( $value ) {
		return preg_match( '/base64|data|(?:java)?script|alert\(|window\.|document/i', $value );
	}

	/**
	 * get_allowed_attributes
	 *
	 * @return array
	 */
	private function get_allowed_attributes() {
		$allowed_attributes = [
			'class',
			'clip-path',
			'clip-rule',
			'fill',
			'fill-opacity',
			'fill-rule',
			'filter',
			'mask',
			'opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'style',
			'systemlanguage',
			'transform',
			'href',
			'xlink:href',
			'xlink:title',
			'cx',
			'cy',
			'r',
			'requiredfeatures',
			'clippathunits',
			'type',
			'rx',
			'ry',
			'color-interpolation-filters',
			'stddeviation',
			'filterres',
			'filterunits',
			'height',
			'primitiveunits',
			'width',
			'x',
			'y',
			'font-size',
			'display',
			'font-family',
			'font-style',
			'font-weight',
			'text-anchor',
			'marker-end',
			'marker-mid',
			'marker-start',
			'x1',
			'x2',
			'y1',
			'y2',
			'gradienttransform',
			'gradientunits',
			'spreadmethod',
			'markerheight',
			'markerunits',
			'markerwidth',
			'orient',
			'preserveaspectratio',
			'refx',
			'refy',
			'viewbox',
			'maskcontentunits',
			'maskunits',
			'd',
			'patterncontentunits',
			'patterntransform',
			'patternunits',
			'points',
			'fx',
			'fy',
			'offset',
			'stop-color',
			'stop-opacity',
			'xmlns',
			'xmlns:se',
			'xmlns:xlink',
			'xml:space',
			'method',
			'spacing',
			'startoffset',
			'dx',
			'dy',
			'rotate',
			'textlength',
		];

		return apply_filters( 'pressmodo_svg_allowed_attributes', $allowed_attributes );
	}

	/**
	 * get_allowed_elements
	 *
	 * @return array
	 */
	private function get_allowed_elements() {
		$allowed_elements = [
			'a',
			'circle',
			'clippath',
			'defs',
			'style',
			'desc',
			'ellipse',
			'fegaussianblur',
			'filter',
			'foreignobject',
			'g',
			'image',
			'line',
			'lineargradient',
			'marker',
			'mask',
			'metadata',
			'path',
			'pattern',
			'polygon',
			'polyline',
			'radialgradient',
			'rect',
			'stop',
			'svg',
			'switch',
			'symbol',
			'text',
			'textpath',
			'title',
			'tspan',
			'use',
		];
		return apply_filters( 'pressmodo_svg_allowed_elements', $allowed_elements );
	}

	/**
	 * validate_allowed_attributes
	 *
	 * @param \DOMElement $element
	 */
	private function validate_allowed_attributes( $element ) {
		static $allowed_attributes = false;
		if ( false === $allowed_attributes ) {
			$allowed_attributes = $this->get_allowed_attributes();
		}

		for ( $index = $element->attributes->length - 1; $index >= 0; $index-- ) {
			// get attribute name
			$attr_name           = $element->attributes->item( $index )->name;
			$attr_name_lowercase = strtolower( $attr_name );
			// Remove attribute if not in whitelist
			if ( ! in_array( $attr_name_lowercase, $allowed_attributes ) && ! $this->is_a_attribute( $attr_name_lowercase, 'aria' ) && ! $this->is_a_attribute( $attr_name_lowercase, 'data' ) ) {
				$element->removeAttribute( $attr_name );
				continue;
			}

			$attr_value = $element->attributes->item( $index )->value;

			// Remove attribute if it has a remote reference or js or data-URI/base64
			if ( ! empty( $attr_value ) && ( $this->is_remote_value( $attr_value ) || $this->has_js_value( $attr_value ) ) ) {
				$element->removeAttribute( $attr_name );
				continue;
			}
		}
	}

	/**
	 * strip_xlinks
	 *
	 * @param \DOMElement $element
	 */
	private function strip_xlinks( $element ) {
		$xlinks = $element->getAttributeNS( 'http://www.w3.org/1999/xlink', 'href' );

		if ( ! $xlinks ) {
			return;
		}

		$allowed_links = [
			'data:image/png', // PNG
			'data:image/gif', // GIF
			'data:image/jpg', // JPG
			'data:image/jpe', // JPEG
			'data:image/pjp', // PJPEG
		];
		if ( 1 === preg_match( self::SCRIPT_REGEX, $xlinks ) ) {
			if ( ! in_array( substr( $xlinks, 0, 14 ), $allowed_links ) ) {
				$element->removeAttributeNS( 'http://www.w3.org/1999/xlink', 'href' );
			}
		}
	}

	/**
	 * validate_use_tag
	 *
	 * @param $element
	 */
	private function validate_use_tag( $element ) {
		$xlinks = $element->getAttributeNS( 'http://www.w3.org/1999/xlink', 'href' );
		if ( $xlinks && '#' !== substr( $xlinks, 0, 1 ) ) {
			$element->parentNode->removeChild( $element ); // phpcs:ignore -- php DomNode
		}
	}

	/**
	 * strip_docktype
	 */
	private function strip_doctype() {
		foreach ( $this->svg_dom->childNodes as $child ) {
			if ( XML_DOCUMENT_TYPE_NODE === $child->nodeType ) { // phpcs:ignore -- php DomDocument
				$child->parentNode->removeChild( $child ); // phpcs:ignore -- php DomDocument
			}
		}
	}

	/**
	 * sanitize_elements
	 */
	private function sanitize_elements() {
		$elements = $this->svg_dom->getElementsByTagName( '*' );
		// loop through all elements
		// we do this backwards so we don't skip anything if we delete a node
		// see comments at: http://php.net/manual/en/class.domnamednodemap.php
		for ( $index = $elements->length - 1; $index >= 0; $index-- ) {
			/**
			 * @var \DOMElement $current_element
			 */
			$current_element = $elements->item( $index );
			// If the tag isn't in the whitelist, remove it and continue with next iteration
			if ( ! $this->is_allowed_tag( $current_element ) ) {
				continue;
			}

			// validate element attributes
			$this->validate_allowed_attributes( $current_element );

			$this->strip_xlinks( $current_element );

			if ( 'use' === strtolower( $current_element->tagName ) ) { // phpcs:ignore -- php DomDocument
				$this->validate_use_tag( $current_element );
			}
		}
	}

	/**
	 * sanitizer
	 *
	 * @param $content
	 *
	 * @return bool|string
	 */
	public function sanitizer( $content ) {
		// Strip php tags
		$content = $this->strip_comments( $content );
		$content = $this->strip_php_tags( $content );

		// Find the start and end tags so we can cut out miscellaneous garbage.
		$start = strpos( $content, '<svg' );
		$end   = strrpos( $content, '</svg>' );
		if ( false === $start || false === $end ) {
			return false;
		}

		$content = substr( $content, $start, ( $end - $start + 6 ) );

		// If the server's PHP version is 8 or up, make sure to Disable the ability to load external entities
		$php_version_under_eight = version_compare( PHP_VERSION, '8.0.0', '<' );
		if ( $php_version_under_eight ) {
			$libxml_disable_entity_loader = libxml_disable_entity_loader( true ); // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated
		}
		// Suppress the errors
		$libxml_use_internal_errors = libxml_use_internal_errors( true );

		// Create DomDocument instance
		$this->svg_dom                      = new \DOMDocument();
		$this->svg_dom->formatOutput        = false;
		$this->svg_dom->preserveWhiteSpace  = false;
		$this->svg_dom->strictErrorChecking = false;

		$open_svg = $this->svg_dom->loadXML( $content );
		if ( ! $open_svg ) {
			return false;
		}

		$this->strip_doctype();
		$this->sanitize_elements();

		// Export sanitized svg to string
		// Using documentElement to strip out <?xml version="1.0" encoding="UTF-8"...
		$sanitized = $this->svg_dom->saveXML( $this->svg_dom->documentElement, LIBXML_NOEMPTYTAG );

		// Restore defaults
		if ( $php_version_under_eight ) {
			libxml_disable_entity_loader( $libxml_disable_entity_loader ); // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated
		}
		libxml_use_internal_errors( $libxml_use_internal_errors );

		return $sanitized;
	}

	/**
	 * strip_php_tags
	 *
	 * @param $string
	 *
	 * @return string
	 */
	private function strip_php_tags( $string ) {
		$string = preg_replace( '/<\?(=|php)(.+?)\?>/i', '', $string );
		// Remove XML, ASP, etc.
		$string = preg_replace( '/<\?(.*)\?>/Us', '', $string );
		$string = preg_replace( '/<\%(.*)\%>/Us', '', $string );

		if ( ( false !== strpos( $string, '<?' ) ) || ( false !== strpos( $string, '<%' ) ) ) {
			return '';
		}
		return $string;
	}

	/**
	 * strip_comments
	 *
	 * @param $string
	 *
	 * @return string
	 */
	private function strip_comments( $string ) {
		// Remove comments.
		$string = preg_replace( '/<!--(.*)-->/Us', '', $string );
		$string = preg_replace( '/\/\*(.*)\*\//Us', '', $string );
		if ( ( false !== strpos( $string, '<!--' ) ) || ( false !== strpos( $string, '/*' ) ) ) {
			return '';
		}
		return $string;
	}

}
