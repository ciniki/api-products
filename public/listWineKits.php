<?php
//
// Description
// -----------
// This function will return a list of wine kits
//
// Info
// ----
// Status: 			started
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <products>
//		<product id="1" name="CC Merlot" type="red" kit_length="4"
// </products>
//
function ciniki_products_listWineKits($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'type'=>array('required'=>'no', 'default'=>'', 'name'=>'Type'),
		'sorting'=>array('required'=>'no', 'default'=>'', 'name'=>'Sort Method'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.listWineKits', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// FIXME: Add timezone information from business settings
	//
	date_default_timezone_set('America/Toronto');
	$todays_date = strftime("%Y-%m-%d");

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

	$strsql = "SELECT ciniki_products.id, ciniki_products.name, "
		. "IFNULL(d1.detail_value, '') AS wine_type, "
		. "IFNULL(d2.detail_value, '') AS kit_length "
		. "FROM ciniki_products "
		. "LEFT JOIN ciniki_product_details AS d1 ON (ciniki_products.id = d1.product_id AND d1.detail_key = 'wine_type') "
		. "LEFT JOIN ciniki_product_details AS d2 ON (ciniki_products.id = d2.product_id AND d2.detail_key = 'kit_length') "
		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_products.type = 64 "
		. "";

	if( $args['sorting'] != 'name' ) {
		$strsql .= "ORDER BY ciniki_products.name, wine_type DESC ";
	} else {
		$strsql .= "ORDER BY ciniki_products.name DESC ";
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.products', 'products', 'product', array('stat'=>'ok', 'products'=>array()));
	if( $rc != 'ok' ) {
		return $rc;
	}

	if( !isset($rc['orders']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'435', 'msg'=>'Unable to find any orders'));
	}

	return $rc;
}
?>
