<?php
//
// Description
// ===========
// This method will return the file in it's binary form.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the requested file belongs to.
// file_id:			The ID of the file to be downloaded.
//
// Returns
// -------
// Binary file.
//
function ciniki_products_fileDownload($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.fileDownload', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the uuid for the file
	//
	$strsql = "SELECT ciniki_product_files.id, "
		. "ciniki_product_files.name, ciniki_product_files.extension, "
		. "ciniki_product_files.binary_content "
		. "FROM ciniki_product_files "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'file');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['file']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1484', 'msg'=>'Unable to find file'));
	}
	$filename = $rc['file']['name'] . '.' . $rc['file']['extension'];

	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
	header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: no-cache');

	if( $rc['file']['extension'] == 'pdf' ) {
		header('Content-Type: application/pdf');
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1485', 'msg'=>'Unsupported file type'));
	}
	// Specify Filename
	header('Content-Disposition: attachment;filename="' . $filename . '"');
	header('Content-Length: ' . strlen($rc['file']['binary_content']));
	header('Cache-Control: max-age=0');

	print $rc['file']['binary_content'];
	exit();
	
	return array('stat'=>'binary');
}
?>