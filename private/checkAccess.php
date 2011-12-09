<?php
//
// Description
// -----------
// This function will validate the user making the request has the 
// proper permissions to access or change the data.  This function
// must be called by all public API functions to ensure security.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// business_id: 		The ID of the business the request is for.
// 
// Returns
// -------
//
function ciniki_products_checkAccess($ciniki, $business_id, $method, $product_id) {
	//
	// All products module functions require authentication, so users 
	// are authenicated before they reach this function
	//

	//
	// Check if the module is enabled for this business, don't really care about the ruleset
	//
	$strsql = "SELECT ruleset FROM ciniki_businesses, ciniki_business_modules "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_businesses.status = 1 "														// Business is active
		. "AND ciniki_businesses.id = ciniki_business_modules.business_id "
		. "AND ciniki_business_modules.package = 'ciniki' "
		. "AND ciniki_business_modules.module = 'products' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'module');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Sysadmins are allowed full access
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok');
	}

	//
	// Check the session user is a business owner
	//
	if( $business_id > 0 ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
		//
		// Find any users which are owners of the requested business_id
		//
		$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND (groups&0x03) > 0 " //	Check for business owner or employee
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
		$rsp = ciniki_core_dbRspQuery($ciniki, $strsql, 'businesses', 'perms', 'perm', array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'398', 'msg'=>'Access denied')));
		if( $rsp['stat'] != 'ok' ) {
			return $rsp;
		}
		if( $rsp['num_rows'] != 1 
			|| $rsp['perms'][0]['perm']['business_id'] != $business_id
			|| $rsp['perms'][0]['perm']['user_id'] != $ciniki['session']['user']['id'] ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'399', 'msg'=>'Access denied'));
		}
	} else {
		// If no business_id specified, then fail
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'400', 'msg'=>'Access denied'));
	}

	//
	// Check the session user is a business owner
	//
	if( $product_id > 0 ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
		//
		// Make sure the product is attached to the business
		//
		$strsql = "SELECT business_id, id FROM ciniki_products "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
		$rsp = ciniki_core_dbRspQuery($ciniki, $strsql, 'products', 'products', 'product', array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'401', 'msg'=>'Access denied')));
		if( $rsp['stat'] != 'ok' ) {
			return $rsp;
		}
		if( $rsp['num_rows'] != 1 
			|| $rsp['products'][0]['product']['business_id'] != $business_id
			|| $rsp['products'][0]['product']['id'] != $product_id ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'402', 'msg'=>'Access denied'));
		}
	}

	//
	// All checks passed, return ok
	//
	return array('stat'=>'ok');
}
?>
