<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_products_sapos_itemAdd($ciniki, $tnid, $invoice_id, $item) {

    //
    // A product was added to an invoice item, get the details and see if we need to 
    // create a registration for this product
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.products.product' && isset($item['object_id']) ) {
        //
        // Check the product exists
        //
        $strsql = "SELECT id, name, "
            . "ciniki_products.flags AS product_flags, "
            . "inventory_flags, "
            . "inventory_current_num "
            . "FROM ciniki_products "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['product']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.145', 'msg'=>'Unable to find product'));
        }
        $product = $rc['product'];

        $rsp = array('stat'=>'ok');
        if( ($product['inventory_flags']&0x01) > 0 ) {
            if( ($product['inventory_flags']&0x02) > 0 ) {
                $rsp['flags'] = 0x46;   // Shipped item and inventory control and backorderable
                if( $product['inventory_current_num'] <= 0 ) {
                    $rsp['flags'] |= 0x0100;
                }
            } else {
                $rsp['flags'] = 0x42; // shipped item, inventoried but no backorder
            }
        }

        // Check if product is a promotional item
        if( ($product['product_flags']&0x04) > 0 ) {
            $rsp['flags'] |= 0x4000;
        }

        //
        // Check if inventory needs to be updated
        //
//      if( ($product['inventory_flags']&0x01) > 0 && isset($item['quantity']) && $item['quantity'] > 0 ) {
            //
            // Update inventory. It's done with a direct query so there isn't race condition on update.
            //
//          $strsql = "UPDATE ciniki_products "
//              . "SET inventory_current_num = inventory_current_num - '" . ciniki_core_dbQuote($ciniki, $item['quantity']) . "' "
//              . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//              . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
//              . "";
//          ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
//          $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.products');
//          if( $rc['stat'] != 'ok' ) {
//              return $rc;
//          }
//
//          // Get the new value
//          $strsql = "SELECT id, name, "
//              . "inventory_flags, "
//              . "inventory_current_num "
//              . "FROM ciniki_products "
//              . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//              . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
//              . "";
//          $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
//          if( $rc['stat'] != 'ok' ) { 
//              return $rc;
//          }
//          if( !isset($rc['product']) ) {
//              return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.146', 'msg'=>'Unable to find product'));
//          }
//          $product = $rc['product'];
        
            //
            // Update the history
            //
//          ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
//          ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 'ciniki_product_history', $tnid,
//              2, 'ciniki_products', $product['id'], 'inventory_current_num', $product['inventory_current_num']);
//      }

        return $rsp;
    }

    return array('stat'=>'ok');
}
?>
