<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/dropbox/lib/Dropbox/autoload.php');
use \Dropbox as dbx;

function ciniki_products_dropboxDownload(&$ciniki, $business_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'insertFromDropbox');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dropboxParseRTFToText');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dropboxOpenTXT');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'productLoad');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'dropboxDownloadAudio');

    //
    // Check to make sure the dropbox flag is enabled for this business
    //
    if( !isset($ciniki['business']['modules']['ciniki.products']['flags'])
        || ($ciniki['business']['modules']['ciniki.products']['flags']&0x01) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2899', 'msg'=>'Dropbox integration not enabled'));
    }

    //
    // Get the settings for products
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_products_settings', 
        'business_id', $business_id, 'ciniki.products', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']['dropbox-products']) || $rc['settings']['dropbox-products'] == '') {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2902', 'msg'=>'Dropbox products not setup.'));
    }
    $products = $rc['settings']['dropbox-products'];
    if( $products[0] != '/' ) {
        $products = '/' . $products;
    }
    rtrim($products, '/');
    $dropbox_cursor = null;
    if( isset($rc['settings']['dropbox-cursor']) && $rc['settings']['dropbox-cursor'] != '') {
        $dropbox_cursor = $rc['settings']['dropbox-cursor'];
    }

    //
    // Check if we should ignore the old cursor and start from scratch
    //
    if( isset($ciniki['config']['ciniki.products']['ignore.cursor']) 
        && ($ciniki['config']['ciniki.products']['ignore.cursor'] == 1 || $ciniki['config']['ciniki.products']['ignore.cursor'] == 'yes') 
        ) {
        $dropbox_cursor = null;
    }

    //
    // Get the settings for dropbox
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_business_details', 
        'business_id', $business_id, 'ciniki.businesses', 'settings', 'apis');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']['apis-dropbox-access-token']) 
        || $rc['settings']['apis-dropbox-access-token'] == ''
        ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2905', 'msg'=>'Dropbox not configured.'));
    }
    $access_token = $rc['settings']['apis-dropbox-access-token'];

    $client = new dbx\Client($access_token, 'Ciniki');

    //
    // Get the latest changes from Dropbox
    //
    $rc = $client->getDelta($dropbox_cursor, $products);
    if( !isset($rc['entries']) ) {
        // Nothing to update, return
        return array('stat'=>'ok');
    }
    // If there is more
    $dropbox_cursor = $rc['cursor'];
    if( count($rc['entries']) == 0 && $rc['has_more'] == 1 ) {
        error_log('delta again');
        $rc = $client->getDelta($dropbox_cursor, $products);
        if( !isset($rc['entries']) ) {
            // Nothing to update, return
            return array('stat'=>'ok');
        }
    }
    $updates = array();
    $new_dropbox_cursor = $rc['cursor'];
    $entries = $rc['entries'];
    foreach($entries as $entry) {
        //
        // Entries look like:
        //      [0] => /website/products/canada/rivett-andrew/primary_image/img_0610.jpg
        //      [1] => Array
        //          (
        //              [rev] => 230d1f249e
        //              [thumb_exists] => 1
        //              [path] => /website/products/canada/rivett-andrew/primary_image/IMG_0610.jpg
        //              [is_dir] =>
        //              [client_mtime] => Wed, 15 Jan 2014 13:37:06 +0000
        //              [icon] => page_white_picture
        //              [read_only] =>
        //              [modifier] =>
        //              [bytes] => 114219
        //              [modified] => Sat, 14 Mar 2015 19:23:45 +0000
        //              [size] => 111.5 KB
        //              [root] => dropbox
        //              [mime_type] => image/jpeg
        //              [revision] => 35
        //          )
        //
        // Check for a match in the specified directory and path matches valid path list information
        //
        if( preg_match("#^($products)/([^/]+)/([^/]+)/((audio)/(.*))$#i", $entry[0], $matches) ) {
            $product_code = $matches[3];
            if( !isset($updates[$product_code]) ) {
                $updates[$product_code] = array('audio'=>array());
            }
            if( isset($matches[5]) ) {
                switch($matches[5]) {
                    case 'audio': 
                        if( $entry[1]['mime_type'] == 'audio/wav' || $entry[1]['mime_type'] == 'audio/x-wav' ) {
                            $updates[$product_code][$matches[5]]['audio'] = array(
                                'path'=>$entry[1]['path'], 
                                'modified'=>$entry[1]['modified'], 
                                'mime_type'=>$entry[1]['mime_type'],
                                ); 
                            break;
                        }
                }
            }
        }
    }

    //
    // Update Ciniki
    //
    foreach($updates as $product_code => $product) {
        error_log("Updating: " . $product_code);

        //  
        // Turn off autocommit
        //  
        $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.products');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
        
        //
        // Lookup the product
        //
        $strsql = "SELECT id "
            . "FROM ciniki_products "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND code = '" . ciniki_core_dbQuote($ciniki, $product_code) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
            return $rc;
        }
      
        if( !isset($rc['product']['id']) ) {
            error_log('Product does not exist: ' . $product_code);
            continue;
        }

        $product_id = $rc['product']['id'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'productLoad');
        $rc = ciniki_products_productLoad($ciniki, $business_id, $product_id, array('images'=>'yes', 'audio'=>'yes', 'links'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
            return $rc;
        }
        $ciniki_product = $rc['product'];

        //
        // Decide what needs to be updated
        //
        $update_args = array();

        //
        // Go through the updated items
        //
        foreach($product as $field => $details) {
            if( $field == 'audio' ) {
                $rc = ciniki_products_dropboxDownloadAudio($ciniki, $business_id, $client, $ciniki_product, $details);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
                    return $rc;
                }
            }
        }

        //  
        // Commit the changes
        //  
        $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.products');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
    }

    //
    // Update the dropbox cursor
    //
    $strsql = "INSERT INTO ciniki_products_settings (business_id, detail_key, detail_value, date_added, last_updated) "
        . "VALUES ('" . ciniki_core_dbQuote($ciniki, $business_id) . "'"
        . ", '" . ciniki_core_dbQuote($ciniki, 'dropbox-cursor') . "'"
        . ", '" . ciniki_core_dbQuote($ciniki, $new_dropbox_cursor) . "'"
        . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
        . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $new_dropbox_cursor) . "' "
        . ", last_updated = UTC_TIMESTAMP() "
        . "";
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return $rc;
    }
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 'ciniki_product_history', $business_id, 
        2, 'ciniki_products_settings', 'dropbox-cursor', 'detail_value', $new_dropbox_cursor);
    $ciniki['syncqueue'][] = array('push'=>'ciniki.products.setting', 
        'args'=>array('id'=>'dropbox-cursor'));

    return array('stat'=>'ok');
}
?>