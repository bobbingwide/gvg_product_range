<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2023
 * @package gvg_product_range
 *
 */

class GVG_Product_Range {


	private $dimensions_words;

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
		//$post_title = str_replace( "'", '', $post_title );
		//$post_title = str_replace( '"', '', $post_title );
		$title_words = explode( ' ',  $post_title);
		$product_range_words = [];
		$dimensions_words = [];
		foreach ( $title_words as $index => $title_word ) {
			$title_word = trim( $title_word );
			if ( $this->is_numeric( $title_word )) {
				// ignore, unless the next word is numeric
				if ( isset( $title_words[$index+1 ] ) && is_numeric( $title_words[$index+1 ] ) ) {
					$product_range_words[] = $title_word;
				} else {
					$dimensions_words[] = $title_word;
				}
			} elseif ( $this->ignore_word( $title_word ) ) {
				// ignore
				$dimensions_words[] = $title_word;
			} else {
				$product_range_words[] = $title_word;
			}

		}
		$term_name = implode( ' ', $product_range_words);
		$this->dimensions_words = $dimensions_words;
		return $term_name;
	}

	function get_dimensions() {
		$dimensions = implode( ' ', $this->dimensions_words );
		return $dimensions;
	}

	function is_numeric( $title_word ) {
		$title_word = str_replace( "'", '', $title_word );
		$title_word = str_replace( '"', '', $title_word );
		return is_numeric( $title_word);

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
			$term = $this->fetch_term( $term_name );
			$this->set_post_terms( $product, $term );
			$dimensions = $this->get_dimensions();
			$csv = [];
			$csv[] = $product->ID;
			$csv[] = $product->post_title;
			$csv[] = $term_name;
			$csv[] = $dimensions;
			$csv[] = $term->term_id;
			echo '<br />';
			echo implode( ',', $csv);
			echo PHP_EOL;

		}
	}

	/**
	 * Fetches the term given the term name.
	 *
	 * Creates the term if it doesn't already exist.
	 *
	 * @param $term_name
	 * @return mixed
	 */
	function fetch_term( $term_name ) {
		$term_object = get_term_by( 'name', $term_name, 'product_range');
		if ( false === $term_object) {

			$term_object = $this->create_term( $term_name );
		}
		//print_r( $term_object);
		return $term_object;
	}

	/**
	 * Creates a product_range term.
	 *
	 * @param $term_name
	 *
	 * @return array|false|WP_Error|WP_Term|null
	 */
	function create_term( $term_name ) {
		//echo "Creating term: " . $term_name . PHP_EOL;
		$term_array = wp_insert_term( $term_name, 'product_range');
		if ( is_wp_error( $term_array )) {
			bw_trace2( $term_array, "WP Error");
		} else {
			$term_object=get_term_by( 'ID', $term_array['term_id'], 'product_range' );
		}
		return $term_object;
	}

	function set_post_terms( $product, $term) {
		//echo "Setting product_range: " .$term->term_id . PHP_EOL;
		wp_set_post_terms( $product->ID, [ $term->term_id ], 'product_range');
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

	/**
	 * Displays the product range for the currently selected product.
	 *
	 * @param WC_Product_Simple $product
	 *
	 * @return void
	 */
	function display_product_range( $product ) {
		$id = $product->get_id();
		//echo '<p>Product range for ' . $id . '</p>';
		$terms = wp_get_post_terms( $id, 'product_range' );
		if ( is_wp_error( $terms ) ) {
			//gob();
			return;
		}
		//print_r( $terms );

		if ( count( $terms) ) {
			$term = $terms[0];
		}
		/*
		echo "term";
		echo $term->term_id;
		echo $term->name;
		echo "mert";
		*/
		$args = [ 'post_type' => 'product',
		'numberposts' => -1,
		'tax_query' => array(
			array (
				'taxonomy' => 'product_range',
				'field' => 'term_id',
				'terms' => $term->term_id,
			))
		];
		$posts = get_posts( $args );
		//echo count( $posts);
		if ( count( $posts ) > 1 ) {
			$this->display_product_range_links( $posts, $id );
		}
	}

	function display_product_range_links( $posts, $id ) {
		//echo '<ul class="product_range">';
		$post_dimensions = [];
		foreach ( $posts as $post ) {
			$term = $this->get_product_range_term_name( $post );
			$dimensions = $this->get_dimensions();
			$post_dimensions[ $post->ID ] = $dimensions;
		}
		// sort by dimensions using natural sort.
		natsort( $post_dimensions );

		foreach ( $post_dimensions as $post => $dimensions ) {
			echo $this->get_product_range_link($post,$dimensions, $id) ;
		}
		//echo '</ul>';
	}

	function get_product_range_link( $post, $dimensions, $id ) {

		$current_class = ( $id === $post ) ? 'btn-outline-primary disabled' : 'btn-primary';
		$link = '<a class="btn btn-sml ';
		$link .= $current_class;
		$link .= '" href="';
		if ( $id === $post ) {
			$link.='#';
		} else {
			$link.=get_permalink( $post );
		}
		$link .= '"';
		$link .= '>';
		$link .= $dimensions;
		$link .= '</a>';
		return $link;
	}

	/**
	 * Sets the product_range taxonomy term for the product.
	 *
	 * @param $post_ID
	 * @param $post
	 * @param $update
	 *
	 * @return void
	 */
	function set_product_range( $post_ID, $post, $update ) {
		$term_name = $this->get_product_range_term_name( $post );
		$term = $this->fetch_term( $term_name );
		$this->set_post_terms( $post, $term );
	}

}