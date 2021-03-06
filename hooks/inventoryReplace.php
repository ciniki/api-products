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
function ciniki_products_hooks_inventoryReplace($ciniki, $tnid, $args) {

    if( !isset($args['history_notes']) ) {
        $args['history_notes'] = '';
    }

    //
    // Remove product inventory
    //
    if( isset($args['object']) && $args['object'] == 'ciniki.products.product' && isset($args['object_id']) ) {
        //
        // Check the product exists
        //
        $strsql = "SELECT id, name, "
            . "inventory_flags, "
            . "inventory_current_num "
            . "FROM ciniki_products "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['product']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.9', 'msg'=>'Unable to find product'));
        }
        $product = $rc['product'];

        $rsp = array('stat'=>'ok');
        if( ($product['inventory_flags']&0x01) > 0 ) {
            //
            // Reduce the amount in the inventory
            //
            if( $args['quantity'] < 0 ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.10', 'msg'=>'Unable to find product'));
            }
            $new_quantity = $product['inventory_current_num'] + $args['quantity'];
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.products.product', $product['id'], 
                array('inventory_current_num'=>$new_quantity), 0x04, $args['history_notes']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            //
            // Hook into other modules and update for backordered quantities
            //
            foreach($ciniki['tenant']['modules'] as $module => $m) {
                list($pkg, $mod) = explode('.', $module);
                $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryUpdated');
                if( $rc['stat'] == 'ok' ) {
                    $fn = $rc['function_call'];
                    $rc = $fn($ciniki, $tnid, array(
                        'object'=>'ciniki.products.product',
                        'object_id'=>$product['id'],
                        'new_inventory_level'=>$new_quantity,
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.11', 'msg'=>'Unable to update inventory levels.', 'err'=>$rc['err']));
                    }
                }
            }
        }
        
        return $rsp;
    }

    return array('stat'=>'ok');
}
?>
