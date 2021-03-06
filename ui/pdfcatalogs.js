//
// This is the UI to edit pdf catalogs
//
function ciniki_products_pdfcatalogs() {
    //
    // The edit panel
    //
    this.catalog = new M.panel('Catalog',
        'ciniki_products_pdfcatalogs', 'catalog',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.products.catalogs.catalog');
    this.catalog.data = {};
    this.catalog.catalog_id = 0;
    this.catalog.sections = {
        '_image':{'label':'Image', 'aside':'yes', 'type':'imageform', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'general':{'label':'Catalog', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}, '2':{'name':'Downloadable'}}},
            }},
        '_synopsis':{'label':'Synopsis', 'aside':'yes', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_description':{'label':'Description', 'aside':'yes', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_file':{'label':'File', 'active':function() { return (M.ciniki_products_pdfcatalogs.catalog.catalog_id == 0 ? 'yes' : 'no'); }, 'fields':{
            'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes', 'history':'no'},
            }},
        'images':{'label':'Pages', 'type':'simplethumbs',
            'visible':function() { return (this.catalog_id > 0 ? 'yes' : 'yes'); },
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_products_pdfcatalogs.catalog.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_products_pdfcatalogs.catalog.remove();'},
             }},
    };
    this.catalog.sectionData = function(s) { return this.data[s]; }
    this.catalog.fieldValue = function(s, i, d) {
        if( this.data[i] != null ) { return this.data[i]; }
        return '';
    };
    this.catalog.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.products.pdfcatalogHistory', 'args':{'tnid':M.curTenantID,
            'catalog_id':this.catalog_id, 'field':i}};
    }
    this.catalog.thumbFn = function(s, i, d) {
        return 'M.ciniki_products_pdfcatalogs.image.edit(\'M.ciniki_products_pdfcatalogs.catalog.edit();\',\'' + d.id + '\');';
    };
    this.catalog.addDropImage = function(iid) {
        M.ciniki_products_pdfcatalogs.catalog.setFieldValue('primary_image_id', iid, null, null);
        return true;
    };
    this.catalog.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.catalog.edit = function(cb, cid) {
        this.reset();
        if( cid != null) { this.catalog_id = cid; }
        this.sections._buttons.buttons.delete.visible = (this.catalog_id > 0 ? 'yes' : 'no');
        M.api.getJSONCb('ciniki.products.pdfcatalogGet', {'tnid':M.curTenantID, 'catalog_id':this.catalog_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_products_pdfcatalogs.catalog;
            p.data = rsp.catalog;
            p.refresh();
            p.show(cb);
        });
    }
    this.catalog.save = function() {
        if( this.catalog_id > 0 ) {
            this.sections._buttons.buttons.delete.visible = 'yes';
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.products.pdfcatalogUpdate', {'tnid':M.curTenantID, 'catalog_id':this.catalog_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_products_pdfcatalogs.catalog.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeFormData('yes');
            M.api.postJSONFormData('ciniki.products.pdfcatalogAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_products_pdfcatalogs.catalog.close();
            });
        }
    }
    this.catalog.remove = function() {
        M.confirm("Are you sure you want to remove this catalog?",null,function() {
            M.api.getJSONCb('ciniki.products.pdfcatalogDelete', {'tnid':M.curTenantID,
                'catalog_id':M.ciniki_products_pdfcatalogs.catalog.catalog_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_products_pdfcatalogs.catalog.close();
                });
        });
    }
    this.catalog.addButton('save', 'Save', 'M.ciniki_products_pdfcatalogs.catalog.save();');
    this.catalog.addClose('Cancel');

    //
    // The edit image panel
    //
    this.image = new M.panel('Image',
        'ciniki_products_pdfcatalogs', 'image',
        'mc', 'medium', 'sectioned', 'ciniki.products.catalogs.image');
    this.image.data = {};
    this.image.catalog_image_id = 0;
    this.image.sections = {
        '_image':{'label':'Image', 'type':'imageform', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'general':{'label':'', 'fields':{
            'page_number':{'label':'Page', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_products_pdfcatalogs.image.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_products_pdfcatalogs.image.remove();'},
             }},
    };
    this.image.sectionData = function(s) { return this.data[s]; }
    this.image.fieldValue = function(s, i, d) {
        if( this.data[i] != null ) { return this.data[i]; }
        return '';
    };
    this.image.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.products.pdfcatalogImageHistory', 'args':{'tnid':M.curTenantID,
            'catalog_image_id':this.catalog_image_id, 'field':i}};
    }
    this.image.addDropImage = function(iid) {
        M.ciniki_products_pdfcatalogs.image.setFieldValue('image_id', iid, null, null);
        return true;
    };
    this.image.edit = function(cb, iid) {
        this.reset();
        if( iid != null) { this.catalog_image_id = iid; }
        this.sections._buttons.buttons.delete.visible = (this.catalog_image_id > 0 ? 'yes' : 'no');
        M.api.getJSONCb('ciniki.products.pdfcatalogImageGet', {'tnid':M.curTenantID, 'catalog_image_id':this.catalog_image_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_products_pdfcatalogs.image;
            p.data = rsp.image;
            p.refresh();
            p.show(cb);
        });
    }
    this.image.save = function() {
        if( this.catalog_image_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.products.pdfcatalogImageUpdate', {'tnid':M.curTenantID, 'catalog_image_id':this.catalog_image_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_products_pdfcatalogs.image.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.products.pdfcatalogImageAdd', {'tnid':M.curTenantID, 'catalog_image_id':this.catalog_image_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                M.ciniki_products_pdfcatalogs.image.close();
                });
        }
    }
    this.image.remove = function() {
        M.confirm("Are you sure you want to remove this image?",null,function() {
            M.api.getJSONCb('ciniki.products.pdfcatalogImageDelete', {'tnid':M.curTenantID,
                'catalog_image_id':M.ciniki_products_pdfcatalogs.image.catalog_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_products_pdfcatalogs.image.close();
                });
        });
    }
    this.image.addButton('save', 'Save', 'M.ciniki_products_pdfcatalogs.image.save();');
    this.image.addClose('Cancel');

    this.start = function(cb, aP, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }
        var aC = M.createContainer(aP, 'ciniki_products_pdfcatalogs', 'yes');
        if( aC == null ) {
            M.alert('App Error');
            return false;
        }

        this.catalog.edit(cb, args.catalog_id);
    }
}
