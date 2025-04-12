<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2023
 * @package gvg_product_range
 *
 */

class GVG_Product_Range {


	private $dimensions_words;
    private $product; // WooCommerce Product object

    private $sfpro_registered_sizes = null;

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
        stag( "table");
        bw_tablerow( bw_as_array( 'ID,Title,Term,TermID,Visibility,Dimensions,Size,Brand,Building-Type,Material' ), 'tr', 'td');
		foreach ( $products as $product ) {
			$term = $this->get_post_term( $product->ID );
			$term_name = $this->get_product_range_term_name( $product );
			if ( null === $term || $term->name !== $term_name ) {
				$term = $this->fetch_term( $term_name );
				$this->set_post_terms( $product, $term );
			}
			$dimensions = $this->get_dimensions();
			$csv = [];
			$csv[] = $this->edit_link( $product->ID );
			$csv[] = $product->post_title;
			$csv[] = $term_name;
			$csv[] = $term->term_id;
            $this->load_product( $product->ID );
            $csv[] = $this->product_visibility();
            $csv[] = $dimensions;

            $csv[] = $this->size( $product->ID, $dimensions);
            $csv[] = $this->brand( $product->ID);

            $csv[] = $this->building_type();
            $csv[] = $this->material();



            /*
			echo '<br />';
			echo implode( ',', $csv);
			echo PHP_EOL;
            */
            bw_tablerow( $csv );

		}
        etag( "table");
        bw_flush();
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
    /**
     * Returns an edit link for a post
     * @param $ID
     * @return string
     */
    function edit_link($ID) {
        $url = get_edit_post_link($ID);
        $link_wrapper_attributes = 'href=' . esc_url($url);
        $html = sprintf(
            '<a %1$s>%2$s</a>',
            $link_wrapper_attributes,
            $ID
        );
        return $html;
    }

    function load_product( $ID ) {
        $this->product = wc_get_product( $ID );
    }

    function product_visibility() {
        $visibility = $this->product->get_catalog_visibility();
        return $visibility;

    }

    function get_wxl( $string ) {
        $wxl = str_replace( "' x", 'x', $string );
        $wxl = str_replace( "'", 'ft', $wxl );
        $wxl = str_replace(' ', '', $wxl );
        return $wxl;
    }

    function match_size( $size, $dimensions) {
        //$size_wxl = $size$this->get_wxl( $size );
        $dimensions_wxl = $this->get_wxl( $dimensions );
        $match = 0 === strcmp( $size,  $dimensions_wxl );
        return $match;
    }

    function size($ID, $dimensions) {
        $size = get_post_meta( $ID, 'size', true);
        $html = $size;
        if (  !$this->match_size( $size, $dimensions ) ) {
            $html = '<strong style="color:darkgoldenrod;">';
            $html .= $size;
            $html .= ' ?</strong>';
        }
        if ( !$this->is_an_sfpro_size( $size )) {
            $html .= '<strong style="color:red">X</strong>';
        }
        return $html;
    }

    function get_sfpro_registered_sizes() {
        $search_filter_fields = get_post_meta( 6288, '_search-filter-fields', true );
        bw_trace2( $search_filter_fields, '_search-filter-fields', true );
        $this->sfpro_registered_sizes = [];
        foreach ( $search_filter_fields as $field ) {
            if ( 'post_meta' === $field['type']
                && 'size' === $field['choice_meta_key']
                && 'manual' === $field['choice_get_option_mode']) {
                bw_trace2( $field, "field", false );
                $meta_options = $field['meta_options'];
                foreach ( $meta_options as $meta_option ) {
                    $this->sfpro_registered_sizes[$meta_option['option_value']] = $meta_option['option_label'];

                }
            }
        }

    }

    function is_an_sfpro_size( $size ) {
        if ( null === $this->sfpro_registered_sizes ) {
            $this->get_sfpro_registered_sizes();
        }
        bw_trace2( $this->sfpro_registered_sizes, "registered sizes");
        $is_a_size = isset( $this->sfpro_registered_sizes[$size] );
        return $is_a_size;
    }

    function brand($ID) {
        $brands = get_field( 'brand', $ID);
        bw_trace2( $brands, "brands", true);
        //$brands = is_array( $brands) ? $brands : [ $brands ];

        //$brand = is_array( $brand) ?
        $titles = [];
        if ( $brands && count( $brands)) {
            foreach ($brands as $brand) {
                $titles[] = $brand->post_title;
            }
        }
        return implode( ',', $titles );
    }

    function building_type() {
        $attributes = $this->product->get_attribute( 'building-type');
        return $attributes;
    }

    function material() {
        $attributes = $this->product->get_attribute( 'material');
        return $attributes;
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

    /**
     * Displays the Product Ranges tab.
     * 
     * @return void
     */
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
   	 * There's no need to display the list when there's only one product in the range.
     *
	 * @param WC_Product_Simple $product
	 *
	 * @return void
	 */
	function display_product_range( $product ) {
        $id = $product->get_id();
		$posts= $this->get_product_range_posts( $id );
		if (count($posts) > 1) {
            $this->display_product_range_links($posts, $id);
        }
	}

    /**
     * Displays the product range as a dropdown list.
     *
     * Displayed for any number of products, including one.
     *
     * @param WC_Product_Simple $product
     * @return void
    */
    function display_product_range_dropdown( $product) {
        $id = $product->get_id();
        $posts= $this->get_product_range_posts( $id );
        $this->display_product_range_dropdown_list( $product, $posts, $id);
    }

    /**
     * Gets all the posts for the selected product range term.
     *
     * @param $id
     * @return int[]|WP_Post[]|null
     */
    function get_product_range_posts( $id ) {
        $term = $this->get_post_term( $id );
        $posts = null;
        if ( null !== $term ) {
            $args = [
                'post_type' => 'product',
                'numberposts' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_range',
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                    )
                )
            ];
            $posts = get_posts($args);
        }
        return $posts;

    }

    /**
     * Gets the product range term for the selected product.
     *
     * Note: There should only be one product_range term for each product.
     *
     * @param $id
     * @return mixed|null
     */
	function get_post_term( $id ) {
		$term = null;
		$terms = wp_get_post_terms( $id, 'product_range' );
		if ( is_wp_error( $terms ) ) {
			return null;
		}
		if ( count( $terms) ) {
			$term = $terms[0];
		}
		return $term;
	}

    /**
     * Displays product range links
     *
     * - The product range links are displayed horizontally.
     * - The link to the current product is disabled.
     * - Bootstrap styling allows for the links to wrap
     *
     * @param $posts
     * @param $id
     * @return void
     */
	function display_product_range_links( $posts, $id ) {
        $post_dimensions = $this->get_post_dimensions( $posts );
		echo '<div class="d-flex flex-wrap">';
		foreach ( $post_dimensions as $post => $dimensions ) {
            echo '<div class="me-2">';
			echo $this->get_product_range_link($post,$dimensions, $id) ;
            echo $this->product_from_price( $post );
            echo '</div>';
		}
        echo '</div>';
		//echo '</ul>';
	}

    /**
     * Returns the dimensions for each product in the range, sorted naturally.
     *
     * @param $posts
     * @return array
     */
    function get_post_dimensions( $posts) {
        $post_dimensions = [];
        foreach ( $posts as $post ) {
            $term = $this->get_product_range_term_name( $post );
            $dimensions = $this->get_dimensions();
            $post_dimensions[ $post->ID ] = $dimensions;
        }
        // sort by dimensions using natural sort.
        natsort( $post_dimensions );
        return $post_dimensions;
    }

    /**
     * Returns a link for the specific product in the range.
     *
     * Set the $id to 0 for the product range dropdown list.
     * This ensures that the current item is enabled
     * and sets the classes on the button to slightly smaller.
     *
     * @param $post
     * @param $dimensions
     * @param $id
     * @return string
     */
	function get_product_range_link( $post, $dimensions, $id )
    {
        $disabled = ($id === 0) ? '' : 'disabled';
        $current_class = ($id === 0) ? 'btn btn-sml px-2 py-1 ms-1 ' : 'btn btn-sml px-4 ';
        $current_class .= ($id === $post) ? "btn-outline-primary $disabled" : 'btn-primary';

        $link = '<a class="';
        $link .= $current_class;
        $link .= '" href="';
        if ($id === $post) {
            $link .= '#';
        } else {
            $link .= get_permalink($post);
        }
        $link .= '"';
        $link .= '>';
        if ($dimensions) {
            $link .= $dimensions;
        } else {
            $link .= '&nbsp;';
        }
		$link .= '</a>';
		return $link;
	}

    /**
     * Displays the product's from price.
     *
     * Prefixes the from price with SALE as required.
     *
     * @param $post
     * @return void
     */
    function product_from_price( $post ) {
        $product = wc_get_product( $post);
        //echo "<div>From price</div>";
        if ( $product->is_on_sale() )  {
            ?>
            <p class="mb-1 font-colour-primary fw-medium" style="font-size: .9rem"><span style='background:#c31313;color:#fff;padding: 3px; display:inline-block;'>SALE</span> from: £<?php echo number_format(  $product->get_sale_price(), 2, '.', '' ); ?></p>
            <?php
        } else {
            $price = number_format( $product->get_price(), 2 );
            echo '<p class="mb-1 font-colour-primary fw-medium" style="font-size: .9rem">From: £' . $price . '</p>';
        }
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

    /**
     * Filters duplicates of posts in the same product range.
     *
     * @return void
     */
    function filter_duplicates() {
        global $wp_query;
        //bw_trace2( $wp_query->posts, "posts", false );
        $filtered = [];
        $product_ranges = [];
        foreach ( $wp_query->posts as $post ) {
            $product_range = $this->get_post_term( $post->ID );
            if ( !isset( $product_ranges[ $product_range->term_id ]) ) {
                $product_ranges[ $product_range->term_id ] = $product_range;
                $post->post_title = $product_range->name;
                $post->product_range = $product_range;
                $filtered[] = $post;
            }
        }
        $wp_query->posts = $filtered;
        // Reduce the post count to control the loop.
        $wp_query->post_count = count( $filtered );
    }

    /**
     * Displays the product range sizes as a dropdown list.
     *
     * See https://getbootstrap.com/docs/5.3/components/dropdowns/
     *
     * @param $product
     * @param $posts
     * @return void
     */
    function display_product_range_dropdown_list( $product, $posts, $id ) {
        echo '<div class="dropdown">';
        echo '<button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
        echo count( $posts );
        echo ' size';
        if (count( $posts ) > 1 )
                echo 's';
        echo '</button>';
        echo '<ul class="dropdown-menu">';
        $post_dimensions = $this->get_post_dimensions( $posts );

        foreach ( $post_dimensions as $post => $dimensions ) {
            echo '<li class="dropdown-item">';
            echo $this->get_product_range_link($post, $dimensions, 0 ) ;
            echo $this->product_from_price( $post,  );
            echo '</li>';
        }
       echo '</ul>';
    }

    /**
     * Attaches the alter_title filter function to the_title.
     *
     * @return void
     */
    function set_product_range_title() {
        static $added = false;
        if ( !$added ) {
            add_filter('the_title', [$this, "alter_title"], 10, 2);
        }
        $added = true;
    }

    /**
     * Alters the product title to the product range.
     *
     * @param $post_title
     * @param $post_id
     * @return mixed
     */
    function alter_title( $post_title, $post_id ) {
        $post = get_post( $post_id );
        if ( 'product' === $post->post_type ) {
            $product_range = $this->get_post_term( $post_id );
            $post_title = $product_range->name;
        }
        return $post_title;
    }

}