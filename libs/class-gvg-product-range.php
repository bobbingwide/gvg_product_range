<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2023
 * @package gvg_product_range
 *
 */

class GVG_Product_Range {


	function __construct() {

	}


	/**
	 * Gets the product_range term name from the Product's post title.
	 * @param $product
	 *
	 * @return void
	 */
	function get_product_range_term_name( $product ) {
		$post_title = str_replace( '   ', ' ', $product->post_title);
		$post_title = str_replace( '  ', ' ', $post_title);
		$post_title = str_replace( "'", '', $post_title );
		$post_title = str_replace( '"', '', $post_title );
		$title_words = explode( ' ',  $post_title);
		$product_range_words = [];
		foreach ( $title_words as $index => $title_word ) {
			$title_word = trim( $title_word );
			if ( is_numeric( $title_word )) {
				// ignore, unless the next word is numeric
				if ( isset( $title_words[$index+1 ] ) && is_numeric( $title_words[$index+1 ] ) ) {
					$product_range_words[] = $title_word;
				}
			} elseif ( $this->ignore_word( $title_word ) ) {
				// ignore
			} else {
				$product_range_words[] = $title_word;
			}

		}
		$term_name = implode( ' ', $product_range_words);
		return $term_name;
	}

	function ignore_word( $title_word ) {
		$ignore = false;
		switch ( $title_word ) {
			case '':
			case 'x':
			case 'ft':
				$ignore = true;
		}
		if ( !$ignore ) {

			if ( $this->str_ends_with( $title_word, 'mm')) {
				$ignore=true;
			} elseif( $this->str_ends_with( $title_word, 'ft' ) ) {
				$ignore = true;
			}
		}
		return $ignore;
	}

	/**
	 * Polyfill for str_ends_with() in PHP 7
	 *
	 * @param $haystack
	 * @param $needle
	 *
	 * @return bool
	 */
	function str_ends_with( $haystack, $needle ) {
		return empty($needle) || substr($haystack, -strlen($needle)) === $needle;
	}

	/**
	 * Gets the product range term given the $term_name
	 * @param $term_name
	 *
	 * @return void
	 */
	function get_product_range_term( $term_name ) {

		return $term;
	}


	function run_batch() {
		echo "GVG product range run_batch" , PHP_EOL;
		$args = [ 'post_type' => 'product'
				, 'numberposts' => -1
				, 'orderby' => 'title'
				, 'order' => 'ASC' ];

		$products = get_posts( $args );
		echo count($products ), PHP_EOL;
		foreach ( $products as $product ) {
			$term_name = $this->get_product_range_term_name( $product );
			$csv = [];
			$csv[] = $product->ID;
			$csv[] = $product->post_title;
			$csv[] = $term_name;
			echo '<br />';
			echo implode( ',', $csv);
			echo PHP_EOL;

		}
	}

	function bulk_update() {
		add_filter( "bw_nav_tabs_gvg_bulk_update", [ $this, "nav_tabs" ], 11, 2 );
		add_action( 'gvg_nav_tab_product_range', [ $this, 'nav_tab_product_range']);
	}

	/**
	 * Implements bw_nav_tabs_gvg filter.
	 *
	 */
	function nav_tabs( $nav_tabs, $tab ) {
		$nav_tabs['product_range'] = 'Product Ranges';
		return $nav_tabs;
	}

	function nav_tab_product_range() {
		BW_::oik_menu_header( __( "Product Ranges", "gvg_product_range" ), "w100pc" );
		bw_flush();

		$this->run_batch();
		oik_menu_footer();
		bw_flush();



	}

}