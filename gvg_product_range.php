<?php
/*
Plugin Name: GVG Product Range
Plugin URI: https://github.com/bobbingwide/gvg_product_range
Description: GVG Product Range
Version: 0.0.0
Author: bobbingwide
Author URI: https://bobbingwide.com/about-bobbing-wide
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

    Copyright 2023 Bobbing Wide (email : herb@bobbingwide.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/

function gvgpr_gvg_loaded() {
	// GVG_bulk_update has been loaded so we can use shared libraries
	// and hook into GVG_Bulk_Update if necessary.
	require_once 'libs/class-gvg-product-range.php';
	$gvgpr = new GVG_Product_Range();
	$gvgpr->bulk_update();
}

function gvgpr_plugin_loaded() {
	add_action( "gvg_loaded", "gvgpr_gvg_loaded" );
	add_action( 'display_gvg_product_range', 'gvgpr_display_gvg_product_range');
	add_action( 'run_gvg_product_range.php', "gvgpr_run_gvg_product_range" );
	add_action( "wp_insert_post", "gvgpr_wp_insert_post", 10, 3 );
}

function gvgpr_run_gvg_product_range() {
	echo "Setting product_range taxonomy", PHP_EOL;
	require_once 'libs/class-gvg-product-range.php';
	$gvgpr = new GVG_Product_Range();
	$gvgpr->run_batch();

}

function gvgpr_display_gvg_product_range( $product ) {
	require_once 'libs/class-gvg-product-range.php';
	$gvgpr = new GVG_Product_Range();
	$gvgpr->display_product_range( $product );

}

/**
 * Implements 'wp_insert_post' to automatically set the product_range taxonomy term
 *
 * @param ID $post_ID
 * @param object $post
 * @param bool $update
 */
function gvgpr_wp_insert_post( $post_ID, $post, $update ) {
	$status =  $post->post_status;
	$post_type = $post->post_type;
	if ( $status !== "auto-draft" && $post_type === "product" ) {
		require_once 'libs/class-gvg-product-range.php';
		$gvgpr = new GVG_Product_Range();
		$gvgpr->set_product_range( $post_ID, $post, $update );
	}
}

gvgpr_plugin_loaded();
