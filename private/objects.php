<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_objects($ciniki) {
	
	$objects = array();
	$objects['product'] = array(
		'name'=>'Product',
		'sync'=>'yes',
		'table'=>'ciniki_products',
		'fields'=>array(
			'name'=>array(),
			'type'=>array(),
			'category'=>array(),
			'permalink'=>array(),
			'source'=>array(),
			'flags'=>array(),
			'status'=>array(),
			'barcode'=>array(),
			'supplier_business_id'=>array(),
			'supplier_product_id'=>array(),
			'price'=>array(),
			'cost'=>array(),
			'msrp'=>array(),
			'primary_image_id'=>array('ref'=>'ciniki.images.image'),
			'short_description'=>array(),
			'long_description'=>array(),
			'start_date'=>array(),
			'end_date'=>array(),
			'webflags'=>array(),
			),
		'details'=>array('key'=>'product_id', 'table'=>'ciniki_product_details'),
		'history_table'=>'ciniki_product_history',
		);
	$objects['image'] = array(
		'name'=>'Image',
		'sync'=>'yes',
		'table'=>'ciniki_product_images',
		'fields'=>array(
			'product_id'=>array('ref'=>'ciniki.products.product'),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			),
		'history_table'=>'ciniki_product_history',
		);
	$objects['file'] = array(
		'name'=>'File',
		'sync'=>'yes',
		'table'=>'ciniki_product_files',
		'fields'=>array(
			'product_id'=>array('ref'=>'ciniki.products.product'),
			'extension'=>array(),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'description'=>array(),
			'org_filename'=>array(),
			'publish_date'=>array(),
			'binary_content'=>array('history'=>'no'),
			),
		'history_table'=>'ciniki_product_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>