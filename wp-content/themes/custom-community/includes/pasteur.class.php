<?php
/**
 * Custom Community Sanitization class
 *
 * Handles all the default and also the more fine-tuned sanitization of values, mostly for the Theme Customization API thou.
 * @author Fabian Wolf
 * @package cc2
 * @since 2.0.20
 */

if( !class_exists('cc2_Pasteur') ) :


class cc2_Pasteur {

	/**
	 * Generic sanitizer for the Theme Customizer
	 */

	public static function sanitize_value( $value, $wp_settings_instance = false ) {
		$return = $value;
		
		if( !empty( $value ) ) { 
			$return = self::sanitize_text( $value );
		}
		
		return $return;
	}
	
	/**
	 * Truethy + falsey
	 */
	
	public static function sanitize_boolean( $value, $wp_settings_instance = false ) {
		$return = true;
		
		if( empty( $value ) != false ) { // 0 = false = null = empty; everything else IS NOT empty and thus true ^^
			$return = false;
		}
		
		return $return;
	}
	
	/**
	 * Alias for @method sanitize_boolean
	 */
	
	public static function sanitize_bool( $value, $wp_settings_instance = false ) {
		return self::sanitize_boolean( $value, $wp_settings_instance );
	}
	
	/**
	 * NOTE: Basically a wrapper for sanitize text field
	 */
	public static function sanitize_text( $value, $wp_settings_instance = false ) {
		$return = $value;
		
		if( !empty( $value ) ) { 
			$return = sanitize_text_field( $value );
		}
		
		return $return;
	}
	
	/**
	 * HEXadecimal = sanitize_hex_color_no_hash
	 */
	
	public static function sanitize_hex( $value, $wp_settings_instance = false ) {
		$return = sanitize_hex_color_no_hash( $value );
		
		return $return;
	}
	
	/**
	 * HEXadecimal COLOR = sanitize_hex_color
	 */
	
	public static function sanitize_hex_color( $value, $wp_settings_instance = false ) {
		return sanitize_hex_color( $value );
	}

	public static function sanitize_scheme_slug( $value, $wp_settings_instance = false ) {
		return sanitize_key( $value );
		
	}
	
	public static function sanitize_hex_with_transparency( $value, $wp_settings_instance = false ) {
		$return = $value;
		
		if( is_string( $value ) && strtolower( trim( $value ) ) == 'transparent' ) {
			$return = 'transparent';
		} else {
			$return = sanitize_hex( $value, $wp_settings_instance );	
		}
		
		return $return;
	}
	
	public static function is_hex( $value, $default = false ) {
		$return = $default;
		
		if( substr( $value, 0, 1) == '#' ) {
			$value = substr( $value, 1 );
		}
		
		if( ctype_xdigit( $value ) != false ) {
			$return = true;
		}
		
		return $return;
	}
	
	public static function maybe_hex( $value ) {
		$return = $value;
		
		
		if( !empty( $return ) && strpos( $return, '#' ) === false && self::is_hex( $value ) != false ) {
			$return = '#' . $return;
		}
	
		return $return;
	}
	
	public static function sanitize_hex_color_with_transparency( $value, $wp_settings_instance = false ) {
		$return = $value;
		
		if( !empty( $value ) ) {
		
			if( strtolower( trim( $value ) ) == 'transparent' ) {
				$return = 'transparent';
			
			} elseif( self::is_hex( $value ) != false ) {
				$return = self::maybe_hex( $value );
			}
		}
		
		return $return;
	}
		
	
	/**
	 * The literal "do nothing" filter
	 */
	
	public static function passthrough( $value, $wp_settings_instance = false ) {
		return $value;
	}
	
	public static function sanitize_raw( $value, $wp_settings_instance = false ) {
		return self::passthrough( $value, $wp_settings_instance );
	}
	
	/**
	 * The even MORE literal "do nothing" filter - takes value, returns void(). Primarily reserved for Customizer Labels, Descriptions and other only cleary information-only, dysfunctional "form fields"
	 * @param [mixed]$value
	 * @return (void)
	 */
	public static function none( $value, $wp_settings_instance = false ) {
		return;
	}

}


endif; // class_exists

/**
 * NOTE: Fallbacks - because of the rather short-sighted structure of the Theme Customization API functions.
 */


if( !function_exists( 'sanitize_hex_color' ) ) {
/**
 * Sanitizes a hex color.
 *
 * Returns either '', a 3 or 6 digit hex color (with #), or null.
 * For sanitizing values without a #, see sanitize_hex_color_no_hash().
 *
 * @since 3.4.0
 *
 * @param string $color
 * @return string|null
 */
	function sanitize_hex_color( $color ) {
		if ( '' === $color )
			return '';

		// 3 or 6 hex digits, or the empty string.
		if ( preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) )
			return $color;

		return null;
	}

}

if( !function_exists( 'sanitize_hex_color_no_hash' ) ) {

/**
 * Sanitizes a hex color without a hash. Use sanitize_hex_color() when possible.
 *
 * Saving hex colors without a hash puts the burden of adding the hash on the
 * UI, which makes it difficult to use or upgrade to other color types such as
 * rgba, hsl, rgb, and html color names.
 *
 * Returns either '', a 3 or 6 digit hex color (without a #), or null.
 *
 * @since 3.4.0
 * @uses sanitize_hex_color()
 *
 * @param string $color
 * @return string|null
 */
	function sanitize_hex_color_no_hash( $color ) {
		$color = ltrim( $color, '#' );

		if ( '' === $color )
			return '';

		return sanitize_hex_color( '#' . $color ) ? $color : null;
	}

};

if( !function_exists( 'maybe_hash_hex_color' ) ) {

/**
 * Ensures that any hex color is properly hashed.
 * Otherwise, returns value untouched.
 *
 * This method should only be necessary if using sanitize_hex_color_no_hash().
 *
 * @since 3.4.0
 *
 * @param string $color
 * @return string
 */
	function maybe_hash_hex_color( $color ) {
		if ( $unhashed = sanitize_hex_color_no_hash( $color ) ) {
			return '#' . $unhashed;
		}
		return $color;
	}
}

