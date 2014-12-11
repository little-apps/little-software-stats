<?php
/**
 * Little Software Stats
 *
 * An open source program that allows developers to keep track of how their software is being used
 *
 * @package		Little Software Stats
 * @author		Little Apps
 * @copyright   Copyright (c) 2011, Little Apps
 * @license		http://www.gnu.org/licenses/gpl.html GNU General Public License v3
 * @link		http://little-software-stats.com
 * @since		Version 0.1
 * @filesource
 */

session_start();

$text = "";

for ( $i = 0; $i < 6; $i++ ) {
    $text .= chr( rand( 65, 90 ) );
}

$_SESSION['Captcha'] = md5( $text );

$img_width = 200;
$img_height = 75;

$image = imagecreatetruecolor( $img_width, $img_height );

$bg_color = imagecolorallocate( $image, 238,239,239 );
$border_color = imagecolorallocate( $image, 208,208,208 );
$text_color = imagecolorallocate( $image, rand( 70,90 ), rand( 50,70 ), rand( 120,140 ) );

imagefilledrectangle( $image, 0, 0, $img_width, $img_height, $bg_color );
imagerectangle( $image, 0, 0, $img_width-1, $img_height-1, $border_color );

$font = dirname( __FILE__ ) . "/captcha_fonts/" . rand( 1, 2 ) . ".ttf";
$font_size = $img_height / 2.2;
$angle = rand( -15, 15 );

$box = imagettfbbox( $font_size, $angle, $font, $text );

$x = (int)( $img_width - $box[4] ) / 2;
$y = (int)( $img_height - $box[5] ) / 2;

imagettftext( $image, $font_size, $angle, $x, $y, $text_color, $font, $text );

header( "Content-type: image/png" );

imagepng( $image );

imagedestroy( $image );