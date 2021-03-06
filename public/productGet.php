<?php
//
// Description
// -----------
// This function will retreive the information about a product.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_products_productGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        'prices'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Prices'),
        'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
        'audio'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Audio'),
        'similar'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Similar Products'),
        'recipes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recommended Recipes'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'All Categories'),
        'subcategories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sub Categories'),
        'tags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'All Tags'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.productGet', $args['product_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteList');

    //
    // Load the product
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'productLoad');
    $rc = ciniki_products_productLoad($ciniki, $args['tnid'], $args['product_id'], $args); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $rsp = array('stat'=>'ok', 'product'=>$rc['product']);

    //
    // Check if all categories should be returned
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        //
        // Get the available tags
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
        $rc = ciniki_core_tagsList($ciniki, 'ciniki.products', $args['tnid'], 
            'ciniki_product_tags', 10);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.94', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
        }
        if( isset($rc['tags']) ) {
            $rsp['categories'] = $rc['tags'];
        }
    }

    //
    // Check if all subcategories should be returned
    //
    if( isset($args['subcategories']) && $args['subcategories'] == 'yes' ) {
        //
        // Get the available tags, but only for the subcategories of the categories the product is part of.
        //
        $strsql = "SELECT DISTINCT t2.tag_type, t2.tag_name "
            . "FROM ciniki_product_tags AS t1, ciniki_product_tags AS t2 "
            . "WHERE t1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND t1.tag_type = 10 "
            . "AND t1.tag_name IN (" . ciniki_core_dbQuoteList($ciniki, explode('::', (isset($rsp['product']['categories'])?$rsp['product']['categories']:''))) . ") "
            . "AND t1.product_id = t2.product_id "
            . "AND t2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND t2.tag_type > 10 AND t2.tag_type < 30 "
            . "ORDER BY t2.tag_type, t2.tag_name "
            . "";
        
/*      $strsql = "SELECT DISTINCT tag_type, tag_name "
            . "FROM ciniki_product_tags "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND tag_type > 10 AND tag_type < 30 "
            . "ORDER BY tag_type, tag_name "
            . ""; */
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'types', 'fname'=>'tag_type', 'name'=>'type',
                'fields'=>array('tag_type')),
            array('container'=>'tags', 'fname'=>'tag_name', 'name'=>'tag', 
                'fields'=>array('type'=>'tag_type', 'name'=>'tag_name')),
            ));
        if( isset($rc['types']) ) {
            foreach($rc['types'] as $type) {
                $rsp['subcategories-' . $type['type']['tag_type']] = $type['type']['tags'];
            }
        }
    }

    //
    // Check if all tags should be returned
    //
    if( isset($args['tags']) && $args['tags'] == 'yes' ) {
        //
        // Get the available tags
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
        $rc = ciniki_core_tagsList($ciniki, 'ciniki.products', $args['tnid'], 
            'ciniki_product_tags', 40);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.95', 'msg'=>'Unable to get list of tags', 'err'=>$rc['err']));
        }
        if( isset($rc['tags']) ) {
            $rsp['tags'] = $rc['tags'];
        }
    }

    return $rsp;
}
?>
