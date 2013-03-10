<?php
//
// Description
// -----------
// This method will return a list of potential duplicates
// 
// Returns
// -------
//
function ciniki_products_duplicatesFind($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
	$ac = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.duplicatesWineKitsFind', 0);
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Search for any potential duplicate products
	//
	$strsql = "SELECT p1.id AS p1_id, p1.name AS p1_name, "	
		. "p2.id AS p2_id, p2.name AS p2_name "
		. "FROM ciniki_products AS p1, ciniki_products AS p2 "
		. "WHERE p1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND p2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND p1.id < p2.id "
		. "AND SOUNDEX(p1.name) = SOUNDEX(p2.name) "
		. "ORDER BY p1_name, p1.id "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'matches', 'fname'=>'p1_id', 'name'=>'match',
			'fields'=>array('p1_id', 'p1_name', 'p2_id', 'p2_name'),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return $rc;
}
?>
