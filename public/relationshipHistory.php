<?php
//
// Description
// -----------
// This method will return the history for a field that is part of a relationship.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the history for.
// relationship_id:		The ID of the relationship to get the history for.
// field:				The field to get the history for.
//
//						relationship_type
//						related_id
//						date_started
//						date_ended
//						notes
//
// Returns
// -------
//	<history>
//		<action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//		...
//	</history>
//	<users>
//		<user id="1" name="users.display_name" />
//		...
//	</users>
//
function ciniki_products_relationshipHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'relationship_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Relationship'), 
		'product_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Product'),
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
	$rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.relationshipHistory', $args['relationship_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $args['field'] == 'date_started'
		|| $args['field'] == 'date_ended' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
		return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.products', 'ciniki_product_history', $args['business_id'], 
			'ciniki_product_relationships', $args['relationship_id'], $args['field'], 'date');
	}

	//
	// The related_id field requires it's own special query, because the history should come
	// from either the product_id or related_id depending on which the product_id is set
	// to.  This means any responses where the product_id is the history are filtered out,
	// and only the other product_id is returned
	//
	if( $args['field'] == 'related_id' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
		$datetime_format = ciniki_users_datetimeFormat($ciniki);
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbParseAge');

		$strsql = "SELECT user_id, DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as date, "
			. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
			. "new_value as value "
			. "FROM ciniki_product_history "
			. "LEFT JOIN ciniki_products ON (ciniki_product_history.new_value = ciniki_products.id "
				. " AND ciniki_products.business_id ='" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
			. " WHERE ciniki_product_history.business_id ='" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. " AND table_name = 'ciniki_product_relationships' "
			. " AND table_key = '" . ciniki_core_dbQuote($ciniki, $args['relationship_id']) . "' "
			. " AND ((table_field = 'related_id' AND new_value != '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "') "
				. "OR (table_field = 'product_id' AND new_value != '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "')) "
			. " ORDER BY log_date DESC "
			. " ";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQueryPlusDisplayNames');
		$rc = ciniki_core_dbRspQueryPlusDisplayNames($ciniki, $strsql, 'ciniki.products', 'history', 'action', array('stat'=>'ok', 'history'=>array(), 'users'=>array()));
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.products', 'ciniki_product_history', $args['business_id'], 'ciniki_product_relationships', $args['relationship_id'], $args['field']);
}
?>
