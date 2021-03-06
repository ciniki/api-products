//
function ciniki_products_main() {
    //
    // Panels
    //
    this.main = null;

    this.cb = null;
    this.toggleOptions = {'off':'Off', 'on':'On'};
    this.subscriptionOptions = {'off':'Unsubscribed', 'on':'Subscribed'};

    this.init = function() {
        //
        // The main panel, which lists the options for production
        //
        this.menu = new M.panel('Products',
            'ciniki_products_main', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.products.main.menu');
        this.menu.data = {'tools':[]};
        this.menu.sections = {
            '_tabs':{'label':'', 'type':'paneltabs', 'selected':'products', 'visible':'no', 'tabs':{}},
            'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':1, 
                'visible':function() { return M.ciniki_products_main.menu.sections._tabs.selected == 'products' ? 'yes' : 'no'; },
                'headerValues':null,
                'hint':'product name', 
                'noData':'No products found',
                },
            'tools':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'visible':function() { return (M.ciniki_products_main.menu.sections._tabs.selected == 'products' && M.modFlagSet('ciniki.products', 0x04) == 'yes') ? 'yes' : 'no'; },
                },
            'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1,
                'visible':function() { return M.ciniki_products_main.menu.sections._tabs.selected == 'products' ? 'yes' : 'no'; },
                'headerValues':null,
                'addTxt':'Add',
                'addFn':'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showMenu();\',\'mc\',{\'product_id\':\'0\'});',
                },
            'products':{'label':'Products', 'type':'simplegrid', 'num_cols':1,
                'visible':function() { return M.ciniki_products_main.menu.sections._tabs.selected == 'products' ? 'yes' : 'no'; },
                'headerValues':null,
                'addTxt':'Add',
                'addFn':'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showMenu();\',\'mc\',{\'product_id\':\'0\'});',
                },
            'catalogs':{'label':'Catalogs', 'type':'simplegrid', 'num_cols':2,
                'visible':function() { return M.ciniki_products_main.menu.sections._tabs.selected == 'catalogs' ? 'yes' : 'no'; },
                'headerValues':['Name', 'Pages', 'Status'],
                'addTxt':'Add',
                'addFn':'M.startApp(\'ciniki.products.pdfcatalogs\',null,\'M.ciniki_products_main.showMenu();\',\'mc\',{\'catalog_id\':\'0\'});',
                },
            'suppliers':{'label':'Suppliers', 'type':'simplegrid', 'num_cols':1,
                'visible':function() { return ((M.curTenant.modules['ciniki.products'].flags&0x08)>0 && M.ciniki_products_main.menu.sections._tabs.selected == 'suppliers') ? 'yes' : 'no'; },
                'headerValues':null,
                },
            };
        this.menu.liveSearchCb = function(s, i, value) {
            if( s == 'search' && value != '' ) { 
                M.api.getJSONBgCb('ciniki.products.productSearch', {'tnid':M.curTenantID, 
                    'start_needle':value, 'status':10, 'limit':'10', 'reserved':'yes'}, function(rsp) { 
                        M.ciniki_products_main.menu.liveSearchShow('search', null, M.gE(M.ciniki_products_main.menu.panelUID + '_' + s), rsp.products); 
                }); 
            return true;
            }   
        };  
        this.menu.liveSearchResultValue = function(s, f, i, j, d) {
            if( s == 'search' ) {
                switch(j) {
//                  case 0: return (d.product.category!=''?d.product.category:'Uncategorized') + ' - ' + d.product.name;
                    case 0: return d.product.name;
                    //case 1: return (d.product.inventory_current_num!=''?d.product.inventory_current_num + (d.product.inventory_reserved!=null&&d.product.inventory_current_num>=0?' <span class="subdue">[' + d.product.inventory_reserved + ']</span>':''):'');
                    case 1: return d.product.inventory_current_num + ((d.product.inventory_reserved!=null&&parseFloat(d.product.inventory_current_num)>=0)?' <span class="subdue">[' + d.product.inventory_reserved + ']</span>':'');
                }
            }
            return ''; 
        }   
        this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
            return 'M.startApp(\'ciniki.products.product\',null,\'M.ciniki_products_main.showMenu();\',\'mc\',{\'product_id\':\'' + d.product.id + '\'});';
        };  
        this.menu.liveSearchSubmitFn = function(s, search_str) {
            M.ciniki_products_main.showSearch('M.ciniki_products_main.showMenu();', search_str);
        };  
        this.menu.sectionData = function(s) { return this.data[s]; }
        this.menu.cellValue = function(s, i, j, d) {
            if( s == 'categories' ) {
                switch(j) {
                    case 0: return ((d.category.name!='')?d.category.name:'*Uncategorized') + ' <span class="count">' + d.category.product_count + '</span>';
                    }
            } else if( s == 'products' ) {
                switch(j) {
                    case 0: return (d.product.code!=''?d.product.code+' - ':'') + d.product.name;
                    case 1: return d.product.inventory_current_num + (d.product.inventory_reserved!=null?' <span class="subdue">[' + d.product.inventory_reserved + ']</span>':'');
                }
            } else if( s == 'suppliers' ) {
                switch(j) {
                    case 0: return ((d.supplier.name!='')?d.supplier.name:'*No Supplier') + ' <span class="count">' + d.supplier.product_count + '</span>';
                }
            } else if( s == 'tools' ) {
                return d.label;
            } else if( s == 'catalogs' ) {
                switch(j) {
                    case 0: return d.name;
                    case 1: return d.num_pages;
                    case 2: return d.status_text;
                }
            }
        };
        this.menu.rowFn = function(s, i, d) {
            if( s == 'categories' ) {
                if( d.category.permalink == '' ) {
                    return 'M.ciniki_products_main.showList(\'M.ciniki_products_main.showMenu();\',\'category\',\'' + escape(d.category.permalink) + '\',\'Uncategorized\');';
                } else {
                    return 'M.ciniki_products_main.showCategory(\'M.ciniki_products_main.showMenu();\',\'' + d.category.permalink + '\');';
                }
            } else if( s == 'products' ) {
                return 'M.startApp(\'ciniki.products.product\',null,\'M.ciniki_products_main.showMenu();\',\'mc\',{\'product_id\':\'' + d.product.id + '\',\'list\':M.ciniki_products_main.menu.data[\'' + s + '\']});';
            } else if( s == 'suppliers' ) {
                return 'M.ciniki_products_main.showList(\'M.ciniki_products_main.showMenu();\',\'supplier_id\',\'' + d.supplier.id + '\',\'' + escape(d.supplier.name) + '\');';
            } else if( s == 'tools' ) {
                return d.fn;
            } else if( s == 'catalogs' ) {
                return 'M.startApp(\'ciniki.products.pdfcatalogs\',null,\'M.ciniki_products_main.showMenu();\',\'mc\',{\'catalog_id\':\'' + d.id + '\'});';
            }
        };
        this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showMenu();\',\'mc\',{\'product_id\':\'0\'});');
        this.menu.addButton('tools', 'Tools', 'M.ciniki_products_main.tools.show(\'M.ciniki_products_main.showMenu();\');');
        this.menu.addClose('Back');

        //
        // The details for a category
        //
        this.category = new M.panel('Product Category',
            'ciniki_products_main', 'category',
            'mc', 'medium', 'sectioned', 'ciniki.products.main.category');
        this.category.data = {};
        this.category.category = '';
        this.category.sections = {};
        this.category.sectionData = function(s) {
            return this.data[s];
        };
        this.category.noData = function() { return 'No products found'; }
        this.category.cellValue = function(s, i, j, d) {
            if( s == 'products' ) {
                switch(j) {
                    case 0: return (d.product.code!=''?d.product.code+' - ':'') + d.product.name;
                    case 1: return d.product.inventory_current_num;
                }
            } else {
                switch(j) {
                    case 0: return (d.category.name!=null?d.category.name:'Unknown') + (d.category.num_products!=null?' <span class="count">'+d.category.num_products+'</span>':'');
                }
            }
        };
        this.category.rowFn = function(s, i, d) {
            if( s == 'products' ) {
                return 'M.startApp(\'ciniki.products.product\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'product_id\':\'' + d.product.id + '\',\'list\':M.ciniki_products_main.category.data[\'' + s + '\']});';
            } else {
                return 'M.ciniki_products_main.showList(\'M.ciniki_products_main.showCategory();\',\'subcategory\',\'' + escape(this.category_permalink) + '\',\'' + this.title + ' - ' + escape(d.category.name) + '\',\'' + escape(d.category.permalink) + '\');';
            }
        };
        this.category.addButton('add', 'Add', 'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'product_id\':\'0\',\'category\':M.ciniki_products_main.list._type});');
        this.category.addButton('edit', 'Edit', 'M.startApp(\'ciniki.products.category\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'category\':M.ciniki_products_main.category.category_permalink,\'subcategory\':\'\'});');
        this.category.addClose('Back');
    

        //
        // The list of products
        //
        this.list = new M.panel('Products',
            'ciniki_products_main', 'list',
            'mc', 'medium', 'sectioned', 'ciniki.products.main.list');
        this.list.data = {};
        this.list.category = '';
        this.list.subcategory = '';
        this.list.sections = {
            'products':{'label':'Products', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null, 
                'addTxt':'Add Product',
                'addFn':'M.ciniki_products_main.addProduct();',
                },
        };
        this.list.sectionData = function(s) {
            return this.data[s];
        };
        this.list.noData = function() { return 'No products found'; }
        this.list.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return (d.product.code!=''?d.product.code+' - ':'') + d.product.name;
                case 1: return d.product.inventory_current_num;
            }
        };
        this.list.rowFn = function(s, i, d) {
            return 'M.startApp(\'ciniki.products.product\',null,\'M.ciniki_products_main.showList();\',\'mc\',{\'product_id\':\'' + d.product.id + '\',\'list\':M.ciniki_products_main.list.data.products});';
        };
        this.list.addButton('add', 'Add', 'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showList();\',\'mc\',{\'product_id\':\'0\',\'category\':M.ciniki_products_main.list._type});');
        this.list.addClose('Back');

        //
        // The search panel will list all search results for a string.  This allows more advanced searching,
        // and will search the entire strings, not just start of the string like livesearch
        //
        this.search = new M.panel('Search Results',
            'ciniki_products_main', 'search',
            'mc', 'medium', 'sectioned', 'ciniki.products.main.search');
        this.search.data = {};
        this.search.sections = {
            'products':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'headerValues':['Product'], 
                },
        };
        this.search.sectionData = function(s) { return this.data[s]; }
        this.search.noData = function() { return 'No products found'; }
        this.search.cellValue = function(s, i, j, d) {
            switch(j) {
//              case 0: return d.product.category!=''?d.product.category:'Uncategorized';
                case 0: return (d.product.code!=''?d.product.code + ' - ':'') + d.product.name;
                case 1: return d.product.inventory_current_num + (d.product.inventory_reserved!=null?' <span class="subdue">[' + d.product.inventory_reserved + ']</span>':'');
            }
        };
        this.search.rowFn = function(s, i, d) {
            return 'M.startApp(\'ciniki.products.product\',null,\'M.ciniki_products_main.showSearch();\',\'mc\',{\'product_id\':\'' + d.product.id + '\'});';
        };
        this.search.addClose('Back');

        //
        // The tools available to work on product records
        //
        this.tools = new M.panel('Product Tools',
            'ciniki_products_main', 'tools',
            'mc', 'narrow', 'sectioned', 'ciniki.products.main.tools');
        this.tools.data = {};
        this.tools.sections = {
            'inventory':{'label':'Inventory', 'list':{
                'inventory':{'label':'Inventory', 'fn':'M.startApp(\'ciniki.products.inventory\', null, \'M.ciniki_products_main.tools.show();\');'},
            }},
            'tools':{'label':'Cleanup', 'list':{
                'duplicates_exact':{'label':'Find Exact Duplicates', 'fn':'M.startApp(\'ciniki.products.duplicates\', null, \'M.ciniki_products_main.tools.show();\',\'mc\',{\'type\':\'exact\'});'},
                'duplicates_soundex':{'label':'Find Similar Duplicates', 'fn':'M.startApp(\'ciniki.products.duplicates\', null, \'M.ciniki_products_main.tools.show();\',\'mc\',{\'type\':\'soundex\'});'},
            }},
            'audio':{'label':'Audio Download', 
                'visible':function() { return M.modFlagSet('ciniki.products', 0x1000); },
                'list':{
                    'audio':{'label':'Audio Files', 'fn':'M.ciniki_products_main.toolsaudio.open(\'M.ciniki_products_main.showTools();\');'},
            }},
            'download':{'label':'Export (Advanced)', 'list':{
                'export':{'label':'Export to Excel', 'fn':'M.ciniki_products_main.downloadExcel();'},
            }},
            };
        this.tools.addClose('Back');

        //
        // This panel displays all the audio samples for the products
        //
        this.toolsaudio = new M.panel('Audio Samples',
            'ciniki_products_main', 'toolsaudio',
            'mc', 'medium', 'sectioned', 'ciniki.products.main.toolsaudio');
        this.toolsaudio.data = {};
        this.toolsaudio.sections = {
            'audio':{'label':'', 'type':'simplegrid', 'num_cols':4,
                'sortable':'yes',
                'sortTypes':['text', '', '', ''],
                'headerValues':['Product', 'WAV', 'MP3', 'OGG'], 
                },
        };
        this.toolsaudio.sectionData = function(s) { return this.data[s]; }
        this.toolsaudio.noData = function() { return 'No products found'; }
        this.toolsaudio.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return (d.product_code!=''?d.product_code + ' - ':'') + d.product_name;
                case 1: 
                    if( d.wav_audio_id > 0 ) {
                        return '<button onclick="event.stopPropagation();M.ciniki_products_main.toolsaudio.download(\'' + d.wav_audio_id + '\',\'' + escape(d.wav_audio_filename) + '\');">WAV</button>';
                    }
                    return '';
                case 2: 
                    if( d.mp3_audio_id > 0 ) {
                        return '<button onclick="event.stopPropagation();M.ciniki_products_main.toolsaudio.download(\'' + d.mp3_audio_id + '\',\'' + escape(d.mp3_audio_filename) + '\');">MP3</button>';
                    }
                    return '';
                case 3: 
                    if( d.ogg_audio_id > 0 ) {
                        return '<button onclick="event.stopPropagation();M.ciniki_products_main.toolsaudio.download(\'' + d.ogg_audio_id + '\',\'' + escape(d.ogg_audio_filename) + '\');">OGG</button>';
                    }
                    return '';
            }
        };
        this.toolsaudio.rowFn = function(s, i, d) {
            return 'M.startApp(\'ciniki.products.product\',null,\'M.ciniki_products_main.toolsaudio.open();\',\'mc\',{\'product_id\':\'' + d.product_id + '\'});';
        };
        this.toolsaudio.open = function(cb) {
            M.api.getJSONCb('ciniki.products.audioList', {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_products_main.toolsaudio;
                p.data = {'audio':rsp.audio};
                p.refresh();
                p.show(cb);
            });
        };
        this.toolsaudio.download = function(aid, name) {
            M.api.openFile('ciniki.audio.download', {'tnid':M.curTenantID, 'audio_id':aid});
        };
        this.toolsaudio.addClose('Back');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_products_main', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.menu.sections._tabs.visible = 'no';
        this.menu.sections._tabs.tabs = {
            'products':{'label':'Products', 'fn':'M.ciniki_products_main.showMenu(null,"products");'},
            };
        if( M.modFlagSet('ciniki.products', 0x08) == 'yes' ) {
            this.menu.sections._tabs.visible = 'yes';
            this.menu.sections._tabs.tabs['suppliers'] = {'label':'Suppliers', 'fn':'M.ciniki_products_main.showMenu(null,"suppliers");'};
        }
        if( M.modFlagSet('ciniki.products', 0x80) == 'yes' ) {
            this.menu.sections._tabs.visible = 'yes';
            this.menu.sections._tabs.tabs['catalogs'] = {'label':'Catalogs', 'fn':'M.ciniki_products_main.showMenu(null,"catalogs");'};
        }

        // Check if inventory enabled
        if( (M.curTenant.modules['ciniki.products'].flags&0x04) > 0 ) {
            this.menu.sections.products.num_cols = 2;
            this.menu.sections.products.headerValues = ['Product', 'Inv [Rsv]'];
            this.menu.sections.search.livesearchcols = 2;
            this.menu.sections.search.headerValues = ['Product', 'Inv [Rsv]'];
            this.list.sections.products.num_cols = 2;
            this.list.sections.products.headerValues = ['Product', 'Inv [Rsv]'];
            this.search.sections.products.num_cols = 2;
            this.search.sections.products.headerValues = ['Product', 'Inv [Rsv]'];
        } else {
            this.menu.sections.products.num_cols = 1;
            this.menu.sections.products.headerValues = null;
            this.menu.sections.search.livesearchcols = 1;
            this.menu.sections.search.headerValues = null;
            this.list.sections.products.num_cols = 1;
            this.list.sections.products.headerValues = null;
        }

        this.menu.data.tools = {};
//      this.menu.sections.tools.visible = 'no';
        if( (M.curTenant.modules['ciniki.products'].flags&0x04) > 0 ) {
//          this.menu.sections.tools.visible = 'yes';
            this.menu.data.tools['duplicates_exact'] = {'label':'Inventory', 'fn':'M.startApp(\'ciniki.products.inventory\', null, \'M.ciniki_products_main.showMenu();\');'};
        }

        if( args.search != null && args.search != '' ) {
            this.showSearch(cb, args.search);
        } else {
            this.showMenu(cb);
        }
    }

    //
    // Grab the stats for the tenant from the database and present the list of products.
    //
    this.showMenu = function(cb, category) {
        if( category != null ) { this.menu.sections._tabs.selected = category; }
        if( this.menu.sections._tabs.selected == 'catalogs' ) {
            M.api.getJSONCb('ciniki.products.pdfcatalogList', {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_products_main.menu;
                p.data.catalogs = rsp.catalogs;
                p.sections.search.visible = 'no';
                p.sections.categories.visible = 'no';
                p.sections.products.visible = 'no';
                p.refresh();
                p.show(cb);
            });
        } else {
            M.api.getJSONCb('ciniki.products.productStats', {'tnid':M.curTenantID, 'status':10, 'reserved':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_products_main.menu;
                if( p.sections._tabs.selected == 'products' ) {
                    if( rsp.products != null ) {
                        p.data.products = rsp.products;
                        p.data.categories = {};
                        p.sections.search.visible = 'no';
                        p.sections.categories.visible = 'no';
                        p.sections.products.visible = 'yes';
                    } else {
                        p.data.categories = rsp.categories
                        p.sections.search.visible = 'yes';
                        p.sections.categories.visible = 'yes';
                        p.sections.products.visible = 'no';
                    }
                } else {
                    p.sections.search.visible = 'no';
                    p.sections.categories.visible = 'no';
                    p.sections.products.visible = 'no';
                }
                p.data.suppliers = rsp.suppliers;
                p.refresh();
                p.show(cb);
            });
        }
    };

    //
    // Show the details about a category
    //
    this.showCategory = function(cb, c) {
        if( c != null ) { this.category.category_permalink = c; }
        M.api.getJSONCb('ciniki.products.categoryDetails', {'tnid':M.curTenantID,
            'category':this.category.category_permalink}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_products_main.category;
                if( rsp.details.category_title != null ) {
                    p.title = rsp.details.category_title;
                }
                p.data = {};
                p.sections = {};
                var plist_label = 'Products';
                if( rsp.subcategorytypes != null ) {
                    for(i in rsp.subcategorytypes) {
                        plist_label = 'Uncategorized Products';
                        p.sections[i] = {'label':rsp.subcategorytypes[i].type.name, 
                            'type':'simplegrid', 'num_cols':1,
                            'headerValues':null,
                            'addTxt':'Add Product',
                            'addFn':'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'product_id\':0});',
                        };
                        p.data[i] = rsp.subcategorytypes[i].type.categories;
                    }
                }
                if( rsp.products != null && rsp.products.length > 0 ) {
                    p.sections['products'] = {'label':plist_label, 'type':'simplegrid', 'num_cols':1,
                        'headerValues':null, 
                        'addTxt':'Add Product',
                        'addFn':'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'product_id\':0});',
                        };
                    p.data.products = rsp.products;
                }
                p.refresh();
                p.show(cb);
        });
    };

    this.showSubCategory = function(cb, category, subcat) {
        
    };

    //
    // Show the list of products for a category
    //
    this.showList = function(cb, listtype, type, title, type2) {
        var args = {'tnid':M.curTenantID};
        if( listtype != null ) {
            this.list._listtype = listtype;
            this.list._type = unescape(type);
            if( type2 != null ) { this.list._type2 = unescape(type2); } else { this.list._type2 = ''; }
            this.list._title = unescape(title);
        }
        this.list.sections.products.label = 'Products';
        this.list.delButton('edit');
        if( this.list._listtype == 'category' ) {
            args['category'] = this.list._type;
            this.list.sections.products.label = unescape(this.list._title);
            this.list.addButton('edit', 'Edit', 'M.startApp(\'ciniki.products.category\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'category\':M.ciniki_products_main.list._type,\'subcategory\':\'\'});');
        } else if( this.list._listtype == 'subcategory' ) {
            args['category'] = this.list._type;
            args['subcategory'] = this.list._type2;
            this.list.sections.products.label = unescape(this.list._title);
            this.list.addButton('edit', 'Edit', 'M.startApp(\'ciniki.products.category\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'category\':M.ciniki_products_main.list._type,\'subcategory\':M.ciniki_products_main.list._type2});');
        } else if( this.list._listtype == 'supplier_id' ) {
            args['supplier_id'] = this.list._type;
            this.list.sections.products.label = unescape(this.list._title);
        } else {
            return false;
        }
        M.api.getJSONCb('ciniki.products.productList', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_products_main.list;
            p.data = {'products':rsp.products};
            p.refresh();
            p.show(cb);
        });
    };

    this.addProduct = function() {
        if( this.list._listtype == 'category' ) {
            M.startApp('ciniki.products.edit',null,'M.ciniki_products_main.showList();','mc',{'product_id':'0','category':M.ciniki_products_main.list._type});
        } else if( this.list._listtype == 'subcategory' ) {
            M.startApp('ciniki.products.edit',null,'M.ciniki_products_main.showList();','mc',{'product_id':'0','category':M.ciniki_products_main.list._type});
        } else if( this.list._listtype == 'supplier_id' ) {
            M.startApp('ciniki.products.edit',null,'M.ciniki_products_main.showList();','mc',{'product_id':'0','supplier_id':M.ciniki_products_main.list._type,'supplier_name':escape(M.ciniki_products_main.list.sections.products.label)});
        }
    };

    //
    // Show the full search results for active and inactive products
    //
    this.showSearch = function(cb, search_str) {
        if( search_str != null ) { this.search.search_str = search_str; }
        M.api.getJSONCb('ciniki.products.productSearch', {'tnid':M.curTenantID, 
            'start_needle':this.search.search_str, 'limit':'101', 'reserved':'yes'}, function(rsp) { 
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_products_main.search;
                p.data = {'products':rsp.products};
                p.refresh();
                p.show(cb);
            });
    };

    this.downloadExcel = function() {
        M.api.openFile('ciniki.products.productExportExcel', {'tnid':M.curTenantID});
    };

}
