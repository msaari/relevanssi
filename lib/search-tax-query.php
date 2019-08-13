<?php
/**
 * /lib/search-tax-query.php
 *
 * Responsible for converting tax_query parameters to MySQL query restrictions.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Processes the tax query to formulate a query restriction to the MySQL query.
 *
 * Tested.
 *
 * @uses relevanssi_process_tax_query_row()
 *
 * @param string $tax_query_relation The base tax query relation. Default 'and'.
 * @param array  $tax_query          The tax query array.
 *
 * @return string The query restrictions for the MySQL query.
 */
function relevanssi_process_tax_query( $tax_query_relation, $tax_query ) {
	$query_restrictions = '';
	if ( ! isset( $tax_query_relation ) ) {
		$tax_query_relation = 'and';
	}
	$tax_query_relation = relevanssi_strtolower( $tax_query_relation );
	$term_tax_ids       = array();
	$not_term_tax_ids   = array();
	$and_term_tax_ids   = array();
	$exist_queries      = array();

	$is_sub_row = false;
	foreach ( $tax_query as $row ) {
		if ( isset( $row['terms'] ) || ( isset( $row['operator'] ) && ( 'not exists' === strtolower( $row['operator'] ) || 'exists' === strtolower( $row['operator'] ) ) ) ) {
			list( $query_restrictions, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids, $exist_queries ) =
			relevanssi_process_tax_query_row( $row, $is_sub_row, $tax_query_relation, $query_restrictions, $tax_query_relation, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids, $exist_queries );
		} else {
			$row_tax_query_relation = $tax_query_relation;
			if ( isset( $row['relation'] ) ) {
				$row_tax_query_relation = relevanssi_strtolower( $row['relation'] );
			}
			if ( is_array( $row ) ) {
				foreach ( $row as $subrow ) {
					$is_sub_row = true;
					if ( isset( $subrow['terms'] ) ) {
						list( $query_restrictions, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids, $exist_queries ) =
						relevanssi_process_tax_query_row( $subrow, $is_sub_row, $tax_query_relation, $query_restrictions, $row_tax_query_relation, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids, $exist_queries );
					}
				}
				if ( 'or' === $row_tax_query_relation ) {
					$query_restrictions .= relevanssi_process_term_tax_ids(
						$term_tax_ids,
						$not_term_tax_ids,
						$and_term_tax_ids,
						$exist_queries
					);
				}
			}
		}
	}

	if ( 'or' === $tax_query_relation ) {
		$query_restrictions .= relevanssi_process_term_tax_ids(
			$term_tax_ids,
			$not_term_tax_ids,
			$and_term_tax_ids,
			$exist_queries
		);
	}

	return $query_restrictions;
}

/**
 * Processes one tax_query row.
 *
 * Tested.
 *
 * @global object $wpdb The WordPress database interface.
 *
 * @param array   $row                The tax_query row array.
 * @param boolean $is_sub_row         True if this is a subrow.
 * @param string  $global_relation    The global tax_query relation (AND or OR).
 * @param string  $query_restrictions The MySQL query restriction.
 * @param string  $tax_query_relation The tax_query relation.
 * @param array   $term_tax_ids       Array of term taxonomy IDs.
 * @param array   $not_term_tax_ids   Array of excluded term taxonomy IDs.
 * @param array   $and_term_tax_ids   Array of AND term taxonomy IDs.
 * @param array   $exist_queries      MySQL queries for EXIST subqueries.
 *
 * @return array Returns an array where the first item is the updated
 * $query_restrictions, then $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids
 * and $exist_queries.
 */
function relevanssi_process_tax_query_row( $row, $is_sub_row, $global_relation, $query_restrictions, $tax_query_relation, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids, $exist_queries ) {
	global $wpdb;

	$local_term_tax_ids     = array();
	$local_not_term_tax_ids = array();
	$local_and_term_tax_ids = array();
	$term_tax_id            = array();

	$exists_query = false;
	if ( isset( $row['operator'] ) && ( 'exists' === strtolower( $row['operator'] ) || 'not exists' === strtolower( $row['operator'] ) ) ) {
		$exists_query = true;
	}

	if ( $exists_query ) {
		$row['field'] = 'exists';
	}
	if ( ! isset( $row['field'] ) ) {
		$row['field'] = 'term_id'; // In case 'field' is not set, go with the WP default of 'term_id'.
	}
	$row['field'] = strtolower( $row['field'] ); // In some cases, you can get 'ID' instead of 'id'.

	if ( in_array( $row['field'], array( 'slug', 'name', 'id', 'term_id' ), true ) ) {
		$term_tax_id = relevanssi_term_tax_id_from_row( $row );
	}

	if ( 'term_taxonomy_id' === $row['field'] ) {
		if ( ! is_array( $row['terms'] ) ) {
			$row['terms'] = array( $row['terms'] );
		}
		$term_tax_id = array_filter( $row['terms'], 'is_numeric' );
	}

	if ( ! $exists_query && ( ! isset( $row['include_children'] ) || true === $row['include_children'] ) ) {
		foreach ( $term_tax_id as $t_id ) {
			$t_term = get_term_by( 'term_taxonomy_id', $t_id, $row['taxonomy'] );
			$t_id   = $t_term->term_id;
			$kids   = get_term_children( $t_id, $row['taxonomy'] );
			foreach ( $kids as $kid ) {
				$kid_term_tax_id = relevanssi_get_term_tax_id( $kid, $row['taxonomy'] );
				if ( $kid_term_tax_id ) {
					// In some weird cases, this may be null. See: https://wordpress.org/support/topic/childrens-of-chosen-product_cat-not-showing-up/.
					$term_tax_id[] = $kid_term_tax_id;
				}
			}
		}
	}

	$term_tax_id = array_unique( $term_tax_id );
	if ( ! empty( $term_tax_id ) ) {
		$n           = count( $term_tax_id );
		$term_tax_id = implode( ',', $term_tax_id );

		$tq_operator = 'IN'; // Assuming the default operator "IN", unless something else is provided.
		if ( isset( $row['operator'] ) ) {
			$tq_operator = strtoupper( $row['operator'] );
		}
		if ( ! in_array( $tq_operator, array( 'IN', 'NOT IN', 'AND' ), true ) ) {
			$tq_operator = 'IN';
		}
		if ( 'and' === $tax_query_relation ) {
			if ( 'AND' === $tq_operator ) {
				$query_restrictions .= " AND relevanssi.doc IN (
					SELECT ID FROM $wpdb->posts WHERE 1=1
					AND (
						SELECT COUNT(1)
						FROM $wpdb->term_relationships AS tr
						WHERE tr.term_taxonomy_id IN ($term_tax_id)
						AND tr.object_id = $wpdb->posts.ID ) = $n
					)";
				// Clean: $term_tax_id and $n are Relevanssi-generated.
			} else {
				$query_restrictions .= " AND relevanssi.doc $tq_operator (
					SELECT DISTINCT(tr.object_id)
						FROM $wpdb->term_relationships AS tr
						WHERE tr.term_taxonomy_id IN ($term_tax_id))";
				// Clean: all variables are Relevanssi-generated.
			}
		} else {
			if ( 'IN' === $tq_operator ) {
				$local_term_tax_ids[] = $term_tax_id;
			}
			if ( 'NOT IN' === $tq_operator ) {
				$local_not_term_tax_ids[] = $term_tax_id;
			}
			if ( 'AND' === $tq_operator ) {
				$local_and_term_tax_ids[] = $term_tax_id;
			}
		}
	} else {
		global $wp_query;
		$wp_query->is_category = false;
	}

	$copy_term_tax_ids = false;
	if ( ! $is_sub_row ) {
		$copy_term_tax_ids = true;
	}
	if ( $is_sub_row && ( 'or' === $global_relation || 'or' === $tax_query_relation ) ) {
		$copy_term_tax_ids = true;
	}

	if ( $copy_term_tax_ids ) {
		$term_tax_ids     = array_merge( $term_tax_ids, $local_term_tax_ids );
		$not_term_tax_ids = array_merge( $not_term_tax_ids, $local_not_term_tax_ids );
		$and_term_tax_ids = array_merge( $and_term_tax_ids, $local_and_term_tax_ids );
	}

	if ( $exists_query ) {
		$taxonomy = $row['taxonomy'];
		$operator = 'IN';
		if ( 'not exists' === strtolower( $row['operator'] ) ) {
			$operator = 'NOT IN';
		}
		$exist_query_sql = " relevanssi.doc $operator (
				SELECT DISTINCT(tr.object_id)
				FROM $wpdb->term_relationships AS tr, $wpdb->term_taxonomy AS tt
				WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
				AND tt.taxonomy = '$taxonomy' )";
		$exist_queries[] = $exist_query_sql;
		if ( 'and' === $tax_query_relation ) {
			$query_restrictions .= ' AND ' . $exist_query_sql;
		}
	}

	return array( $query_restrictions, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids, $exist_queries );
}

/**
 * Generates query restrictions from the term taxonomy ids.
 *
 * Combines different term tax ID arrays into a set of query restrictions that can be
 * used in an OR query.
 *
 * @global object $wpdb The WP database interface.
 *
 * @param array $term_tax_ids     The regular terms.
 * @param array $not_term_tax_ids The NOT terms.
 * @param array $and_term_tax_ids The AND terms.
 * @param array $exist_queries    The EXIST queries.
 *
 * @return string The MySQL query restrictions.
 */
function relevanssi_process_term_tax_ids( $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids, $exist_queries ) {
	global $wpdb;

	$query_restriction_parts = array();
	$query_restrictions      = '';

	$term_tax_ids = array_unique( $term_tax_ids );
	if ( count( $term_tax_ids ) > 0 ) {
		$term_tax_ids              = implode( ',', $term_tax_ids );
		$query_restriction_parts[] = " relevanssi.doc IN (
			SELECT DISTINCT(tr.object_id)
			FROM $wpdb->term_relationships AS tr
			WHERE tr.term_taxonomy_id IN ($term_tax_ids)
		)";
		// Clean: all variables are Relevanssi-generated.
	}
	if ( count( $not_term_tax_ids ) > 0 ) {
		$not_term_tax_ids           = implode( ',', $not_term_tax_ids );
		$query_restriction_parts[] .= " relevanssi.doc NOT IN (
			SELECT DISTINCT(tr.object_id)
			FROM $wpdb->term_relationships AS tr
			WHERE tr.term_taxonomy_id IN ($not_term_tax_ids)
		)";
		// Clean: all variables are Relevanssi-generated.
	}
	if ( count( $and_term_tax_ids ) > 0 ) {
		$and_term_tax_ids           = implode( ',', $and_term_tax_ids );
		$n                          = count( explode( ',', $and_term_tax_ids ) );
		$query_restriction_parts[] .= " relevanssi.doc IN (
			SELECT ID FROM $wpdb->posts WHERE 1=1
			AND (
				SELECT COUNT(1)
				FROM $wpdb->term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($and_term_tax_ids)
				AND tr.object_id = $wpdb->posts.ID ) = $n
			)";
		// Clean: all variables are Relevanssi-generated.
	}

	if ( $exist_queries ) {
		$query_restriction_parts = array_merge( $query_restriction_parts, $exist_queries );
	}

	if ( count( $query_restriction_parts ) > 1 ) {
		$query_restrictions .= '(';
	}
	$query_restrictions .= implode( ' OR', $query_restriction_parts );
	if ( count( $query_restriction_parts ) > 1 ) {
		$query_restrictions .= ')';
	}

	if ( $query_restrictions ) {
		$query_restrictions = ' AND ' . $query_restrictions;
	}

	return $query_restrictions;
}

/**
 * Gets and sanitizes the taxonomy name and slug parameters.
 *
 * Checks parameters: if they're numeric, pass them for term_id filtering, otherwise
 * sanitize and create a comma-separated list.
 *
 * @param string $terms_parameter The 'terms' field from the tax_query row.
 * @param string $taxonomy        The taxonomy name.
 * @param string $field_name      The field name ('slug', 'name').
 *
 * @return array An array containing numeric terms and the list of sanitized term
 * names.
 */
function relevanssi_get_term_in( $terms_parameter, $taxonomy, $field_name ) {
	$numeric_terms = array();
	$names         = array();

	if ( ! is_array( $terms_parameter ) ) {
		$terms_parameter = array( $terms_parameter );
	}
	foreach ( $terms_parameter as $name ) {
		$term = get_term_by( $field_name, $name, $taxonomy );
		if ( ! $term && ctype_digit( strval( $name ) ) ) {
			$numeric_terms[] = $name;
		} else {
			if ( isset( $term->term_id ) && in_array( $field_name, array( 'slug', 'name' ), true ) ) {
				$names[] = "'" . esc_sql( $name ) . "'";
			} else {
				$numeric_terms[] = $name;
			}
		}
	}

	return array(
		'numeric_terms' => implode( ',', $numeric_terms ),
		'term_in'       => implode( ',', $names ),
	);
}

/**
 * Gets the term_tax_id from a row with 'field' set to 'slug' or 'name'.
 *
 * If the slugs or names are all numeric values, will switch the 'field' parameter
 * to 'term_id'.
 *
 * @param array $row The taxonomy query row.
 *
 * @return array An array of term taxonomy IDs.
 */
function relevanssi_term_tax_id_from_row( $row ) {
	global $wpdb;

	$type = $row['field'];

	$term_in_results = relevanssi_get_term_in( $row['terms'], $row['taxonomy'], $type );
	$numeric_terms   = $term_in_results['numeric_terms'];
	$term_in         = $term_in_results['term_in'];
	$term_tax_id     = array();

	if ( ! empty( $numeric_terms ) ) {
		$type    = 'term_id';
		$term_in = $numeric_terms;
	}

	if ( ! empty( $term_in ) ) {
		$row_taxonomy = sanitize_text_field( $row['taxonomy'] );

		$tt_q = "SELECT tt.term_taxonomy_id
				  FROM $wpdb->term_taxonomy AS tt
				  LEFT JOIN $wpdb->terms AS t ON (tt.term_id=t.term_id)
				  WHERE tt.taxonomy = '$row_taxonomy' AND t.$type IN ($term_in)";
		// Clean: $row_taxonomy is sanitized, each term in $term_in is sanitized.
		$term_tax_id = $wpdb->get_col( $tt_q ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	return $term_tax_id;
}
