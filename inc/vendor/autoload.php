<?php
/**
 * Încărcare automată pentru bibliotecile PDF incluse în temă (Dompdf și dependențele lui).
 * Fiecare bibliotecă își păstrează licența în folderul ei.
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register( function ( $class ) {
	static $map = null;
	if ( null === $map ) {
		$b   = __DIR__;
		$map = array(
			'Dompdf\\'         => $b . '/dompdf/src/',
			'FontLib\\'        => $b . '/php-font-lib/src/FontLib/',
			'Svg\\'            => $b . '/php-svg-lib/src/Svg/',
			'Masterminds\\'    => $b . '/html5/src/',
			'Sabberworm\\CSS\\' => $b . '/css-parser/src/',
		);
	}
	if ( 'Dompdf\\Cpdf' === $class ) {
		require_once __DIR__ . '/dompdf/lib/Cpdf.php';
		return;
	}
	foreach ( $map as $prefix => $dir ) {
		if ( 0 === strpos( $class, $prefix ) ) {
			$file = $dir . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
			if ( is_file( $file ) ) {
				require_once $file;
			}
			return;
		}
	}
} );
