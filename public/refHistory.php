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
// ref_id:				The ID of the product reference to get the history for.
// field:				The field to get the history for.
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
function ciniki_products_refHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'ref_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reference'), 
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
	$rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.refHistory', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $args['field'] == 'object_id' ) {
		//
		// Get the reference for the object
		//
		$strsql = "SELECT ciniki_product_refs.id, "
			. "ciniki_product_refs.product_id, "
			. "ciniki_product_refs.object, "
			. "ciniki_product_refs.object_id "
			. "FROM ciniki_product_refs "
			. "WHERE ciniki_product_refs.id = '" . ciniki_core_dbQuote($ciniki, $args['ref_id']) . "' "
			. "AND ciniki_product_refs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";

		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'ref');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1545', 'msg'=>'Unable to find reference', 'err'=>$rc['err']));
		}
		if( !isset($rc['ref']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1546', 'msg'=>'Reference does not exist'));
		}
		$ref = $rc['ref'];
		//
		// Get the reference
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFkId');
		return ciniki_core_dbGetModuleHistoryFkId($ciniki, 'ciniki.products', 'ciniki_product_history', 
			$args['business_id'], 'ciniki_product_refs', 
			$args['ref_id'], $args['field'], 'ciniki_recipes', 'id', 'ciniki_recipes.name');
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.products', 'ciniki_product_history', 
		$args['business_id'], 'ciniki_product_refs', $args['ref_id'], $args['field']);
}
?>
