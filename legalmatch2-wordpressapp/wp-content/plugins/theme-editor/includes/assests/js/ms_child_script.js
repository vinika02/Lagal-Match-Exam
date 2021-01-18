( function( $ ) {
    'use strict';
    $.ms_themeeditor = {
        
        escquo: function( str ) {
            var self = this;
            return self.is_empty( str ) ? str : str.toString().replace( /"/g, '&quot;' );
        },
                
        getxt: function( key, merge ){
            var text = window.ms_ajax[ key + '_txt' ];
            if ( text ) {
                if ( merge ) {
                    text = text.replace( /%s/, merge );
                }
                return text;
            }
            return '';
        },
        
        getname: function( themetype ){
					
			var self = this,
                stylesheet  = ( 'child' === themetype ? $.ms_themeeditor.currchild : $.ms_themeeditor.currparnt );
				
			if ( self.is_empty( window.ms_ajax.themes[ themetype ][ stylesheet ] ) )
			{
                return '';
            } 
			else 
			{
                return window.ms_ajax.themes[ themetype ][ stylesheet ].Name;
            }
        },
        
        frascii: function( str ) {
            var ascii = parseInt( str ),
                chr = String.fromCharCode( ascii );
            return chr;
        },
                
        toascii: function( str ) {
            var ascii = str.charCodeAt( 0 );
            return ascii;
        },
        
        is_empty: function( obj, zeros ) {
            // first bail when definitely empty or undefined ( true ) NOTE: numeric zero returns false !
            if ( 'undefined' === typeof obj || false === obj || null === obj || '' === obj ) { 
                // console.log( 'matched empty' ); 
                return true; 
            }
            // if zeros flag is set, return true for 0 or '0'
            if ( 'undefined' !== typeof zeros && '0' === obj || 0 === obj ) { 
                // console.log( 'matched zero literal:' + obj ); 
                return true; 
            }
            // then, if this is bool, string or number it must not be empty ( false )
            if ( true === obj || "string" === typeof obj || "number" === typeof obj ) { 
                return false; 
            }
            // check for object type to be safe
            if ( "object" === typeof obj ) {    
                // Use a standard for in loop
                for ( var x in obj ) {
                   
                    if ( obj.hasOwnProperty( x ) ) {
                        
                        return false;
                    }
                }
                
                return true;
            } 
           
            return false; 
        },
        
        
        theme_exists: function( testslug, testtype ) {
            var exists = false;
            $.each( window.ms_ajax.themes, function( type, theme ) {
                $.each( theme, function( slug, data ) {
                    data = null;
                    if ( slug.toLowerCase() === testslug.toLowerCase() && ( 'parnt' === type || 'new' === testtype ) ) {
                        exists = true;  
                        return false;   
                    }
                } );
                if ( exists ) {        
                    return false;       
                }
            } );
            return exists;
        },
        
        validate: function() {
            var self    = this,
                regex   = /[^\w\-]/g,
                newslug = $( '#ctc_child_template' ).length ? $( '#ctc_child_template' )
                    .val().toString().replace( regex ) : '',
                slug    = $( '#ctc_theme_child' ).length && !self.is_empty( $( '#ctc_theme_child' ).val() ) ? $( '#ctc_theme_child' )
                    .val().toString().replace( regex ) : newslug,
                type    = $( '#ms_theme_editor_action' ).val(),
                errors  = [];
            if ( 'new' === type ) {
                slug = newslug;
            }
            if ( self.theme_exists( slug, type ) ) {
                errors.push( self.getxt( 'theme_exists' ).toString().replace( /%s/, slug ) );
            }
            if ( self.is_empty( slug ) ) {
                errors.push( self.getxt( 'inval_theme' ) );
            }
            
            if ( errors.length ) {
                self.set_notice( { 'error': errors } );
                return false;
            }
            if ( 'reset' === type ) {
                if ( confirm( self.getxt( 'load' ) ) ) { 
                    return true; 
                }
                return false;
            }
            return true;
        },
        
        autogen_slugs: function() {
            if ( $( '#ctc_theme_parnt' ).length ) {
                var self    = this,
                    parent  = $( '#ctc_theme_parnt' ).val(),
                    child   = $( '#ctc_theme_child' ).length ? $( '#ctc_theme_child' ).val() : '',
                    slugbase= ( '' !== child && $( '#ctc_child_type_duplicate' ).is( ':checked' ) ) ? child : parent + '-child',
                    slug    = slugbase,
                    name    = ( '' !== child && $( '#ctc_child_type_duplicate' ).is( ':checked' ) ) ? $.ms_themeeditor.getname( 'child' ) : $.ms_themeeditor.getname( 'parnt' ) + ' Child',
                    suffix  = '',
                    padded  = '',
                    pad     = '00';
                while ( self.theme_exists( slug, 'new' ) ) {
                    suffix  = ( self.is_empty( suffix ) ? 2 : suffix + 1 );
                    padded  = pad.substring( 0, pad.length - suffix.toString().length ) + suffix.toString();
                    slug    = slugbase + padded;
                }
			
                self.testslug = slug;
				
				var action = $('#ms_theme_editor_action').val();
				
				var ctestname=  $('#testname').val();
				
				if(action == 'new' && ctestname != '')
				{
					self.testname = $('#testname').val() +' Child'+( padded.length ? ' ' + padded : '' );
				}
				else
				{
					self.testname = name + ( padded.length ? ' ' + padded : '' );
				}
				
            }
        },
        
        focus_panel: function( id ) {
            var panelid = id + '_panel';
            $( '.nav-tab' ).removeClass( 'nav-tab-active' );
            $( '.ms-option-panel' ).removeClass( 'ms-option-panel-active' );
            //$( '.ctc-selector-container' ).hide();
            $( id ).addClass( 'nav-tab-active' );
            $( '.ms-option-panel-container' ).scrollTop( 0 );
            $( panelid ).addClass( 'ms-option-panel-active' );
        },
        
        
        maybe_show_rewrite: function(){
            var self = this,
                inputtype,
                value;
            $( '.ctc-rewrite-toggle' ).each( function( ndx, el ){
                inputtype = $( el ).hasClass( 'rewrite-query' ) ? 'query' : 'selector';
                value = $( '#ctc_sel_ovrd_' + inputtype + '_selected' ).text();
                //console.log( 'maybe_show_rewrite inputtype: ' + inputtype + ' value: ' + value );
                if ( value.match( /^[\s\u00A0]*$/ ) ){
                    $( el ).hide();
                } else {
                    $( el ).text( self.getxt( 'rename' ) );
                    $( el ).show();
                }
            } );
        },
        
        
        selector_input_toggle: function( obj ) {
            //console.log( 'selector_input_toggle: ' + $( obj ).attr( 'id' ) );
            var self = this,
                origval,
                inputtype = $( obj ).hasClass( 'rewrite-query' ) ? 'query' : 'selector',
                input = 'ctc_rewrite_' + inputtype,
                orig = 'ctc_sel_ovrd_' + inputtype + '_selected';
            if ( $( '#' + input ).length ) {
                origval = $( '#' + input + '_orig' ).val();
                $( '#' + orig ).empty().text( origval );
                $( obj ).text( self.getxt( 'rename' ) );
            } else {
                origval = $( '#' + orig ).text();
                $( '#' + orig ).html( 
                    '<textarea id="' + input + '"' +
                    ' name="' + input + '" autocomplete="off"></textarea>' +
                    '<input id="' + input + '_orig" name="' + input + '_orig"' +
                    ' type="hidden" value="' + self.escquo( origval ) + '"/>' );
                $( '#' + input ).val( origval );
                $( obj ).text( self.getxt( 'cancel' ) );
            }
        },
            
        coalesce_inputs: function( obj ) {
           
            var self        = this,
                id          = $( obj ).attr( 'id' ),
                regex       = /^(ctc_(ovrd|\d+)_(parent|child)_([0-9a-z\-]+)_(\d+?)(_(\d+))?)(_\w+)?$/,
                container   = $( obj ).parents( '.ctc-selector-row, .ctc-parent-row' ).first(),
                swatch      = container.find( '.ctc-swatch' ).first(),
                cssrules    = { 'parent': {}, 'child': {} },
                gradient    = { 
                    'parent': {
                        'origin':   '',
                        'start':    '',
                        'end':      ''
                    }, 
                    'child': {
                        'origin':   '',
                        'start':    '',
                        'end':      ''
                    } 
                },
                has_gradient    = { 'child': false, 'parent': false },
                postdata        = {};
            // set up objects for all neighboring inputs
            container.find( '.ctc-parent-value, .ctc-child-value' ).each( function() {
                var inputid     = $( this ).attr( 'id' ),
                    inputparts  = inputid.toString().match( regex ),
                    inputseq    = inputparts[ 2 ],
                    inputtheme  = inputparts[ 3 ],
                    inputrule   = ( 'undefined' === typeof inputparts[ 4 ] ? '' : inputparts[ 4 ] ),
                    rulevalid   = inputparts[ 7 ],
                    qsid        = inputparts[ 5 ],
                    rulepart    = ( 'undefined' === typeof inputparts[ 7 ] ? '' : inputparts[ 8 ] ),
                    value       = ( 'parent' === inputtheme ? $( this ).text().replace( /!$/, '' ) : 
                                    ( 'seq' !== inputrule && 'ctc_delete_query_selector' === id ? '' : 
                                        $( this ).val() ) ), // clear values if delete was clicked
                    important   = ( 'seq' === inputrule ? false : 'ctc_' + inputseq + '_child_' + inputrule + '_i_' + qsid + '_' + rulevalid ),
                    parts, subparts;
                //**console.log( inputparts );
                //**console.log( 'value: ' + value );
                if ( 'child' === inputtheme ) {
                    if ( !self.is_empty( $( this ).data( 'color' ) ) ) {
                        value = self.color_text( $( this ).data( 'color' ) );
                        $( this ).data( 'color', null );
                    }
                    postdata[ inputid ]     = value;
                    if ( important ) {
                        postdata[ important ]   = ( $( '#' + important ).is( ':checked' ) ) ? 1 : 0;
                    }
                }
                if ( '' !== value ) {
                    // handle specific inputs
                    if ( !self.is_empty( rulepart ) ) {
                        switch( rulepart ) {
                            case '_border_width':
                                cssrules[ inputtheme ][ inputrule + '-width' ] = ( 'none' === value ? 0 : value );
                                break;
                            case '_border_style':
                                cssrules[ inputtheme ][ inputrule + '-style' ] = value;
                                break;
                            case '_border_color':
                                cssrules[ inputtheme ][ inputrule + '-color' ] = value;
                                break;
                            case '_background_url':
                                cssrules[ inputtheme ][ 'background-image' ] = self.image_url( inputtheme, value );
                                break;
                            case '_background_color':
                                cssrules[ inputtheme ][ 'background-color' ] = value; // was obj.value ???
                                break;
                            case '_background_color1':
                                gradient[ inputtheme ].start   = value;
                                has_gradient[ inputtheme ] = true;
                                break;
                            case '_background_color2':
                                gradient[ inputtheme ].end     = value;
                                has_gradient[ inputtheme ] = true;
                                break;
                            case '_background_origin':
                                gradient[ inputtheme ].origin  = value;
                                has_gradient[ inputtheme ] = true;
                                break;
                        }
                    } else {
                        // handle borders
                        if ( ( parts = inputrule.toString().match( /^border(\-(top|right|bottom|left))?$/ ) && !value.match( /none/ ) ) ) {
                            var borderregx = new RegExp( self.border_regx + self.color_regx, 'i' );
                            subparts = value.toString().match( borderregx );
                         
                            if ( !self.is_empty( subparts ) ) {
                                subparts.shift();
                                cssrules[ inputtheme ][ inputrule + '-width' ] = subparts.shift() || '';
                                subparts.shift();
                                cssrules[ inputtheme ][ inputrule + '-style' ] = subparts.shift() || '';
                                cssrules[ inputtheme ][ inputrule + '-color' ] = subparts.shift() || '';
                            }
                        // handle background images
                        } else if ( 'background-image' === inputrule && !value.match( /none/ ) ) {
                            if ( value.toString().match( /url\(/ ) ) {
                                cssrules[ inputtheme ][ 'background-image' ] = self.image_url( inputtheme, value );
                            } else {
                                var gradregex = new RegExp( self.grad_regx + self.color_regx + self.color_regx, 'i' );
                                subparts = value.toString().match( gradregex );
                          
                                if ( !self.is_empty( subparts ) && subparts.length > 2 ) {
                                    subparts.shift();
                                    gradient[ inputtheme ].origin = subparts.shift() || 'top';
                                    gradient[ inputtheme ].start  = subparts.shift() || 'transparent';
                                    gradient[ inputtheme ].end    = subparts.shift() || 'transparent';
                                    has_gradient[ inputtheme ] = true;
                                } else {
                                    cssrules[ inputtheme ][ 'background-image' ] = value;
                                }
                            }
                        } else if ( 'seq' !== inputrule ) {
                            cssrules[ inputtheme ][ inputrule ] = value;
                        }
                    }
                }
            } );
           
            if ( 'undefined' !== typeof swatch && !self.is_empty( swatch.attr( 'id' ) ) ) {
                swatch.removeAttr( 'style' );
                if ( has_gradient.parent ) {
                    swatch.ctcgrad( gradient.parent.origin, [ gradient.parent.start, gradient.parent.end ] );
                }
                
                swatch.css( cssrules.parent );  
                if ( !( swatch.attr( 'id' ).toString().match( /parent/ ) ) ) {
                    if ( has_gradient.child ) {
                        swatch.ctcgrad( gradient.child.origin, [ gradient.child.start, gradient.child.end ] );
                    }
                    
                    swatch.css( cssrules.child );
                }
                swatch.css( {'z-index':-1} );
            }
            return postdata;
        },
        
        decode_value: function( rule, value ) {
           
            value = ( 'undefined' === typeof value ? '' : value );
            var self = this,
                obj = { 
                    'orig':     value, 
                    'names':    [ '' ],
                    'values':   [ value ]
                },
                params;
            if ( rule.toString().match( /^border(\-(top|right|bottom|left))?$/ ) ) {
                var regex = new RegExp( self.border_regx + '(' + self.color_regx + ')?', 'i' ),
                    orig;
                params = value.toString().match( regex );
                if ( self.is_empty( params ) ) {
                    params = [];
                }
                obj.names = [
                    '_border_width',
                    '_border_style',
                    '_border_color',
                ];
                orig = params.shift();
               
                obj.values[ 0 ] = params.shift() || '';
                params.shift();
                obj.values[ 1 ] = params.shift() || '';
                params.shift();
                obj.values[ 2 ] = params.shift() || '';
            } else if ( rule.toString().match( /^background\-image/ ) ) {
                obj.names = [
                    '_background_url',
                    '_background_origin', 
                    '_background_color1', 
                    '_background_color2'
                ];
                obj.values = [ '', '', '', '' ];
                if ( !self.is_empty( value ) && !( value.toString().match( /(url|none)/ ) ) ) {
                    var    stop1, stop2;
                    params = value.toString().split( /:/ );
          
                    obj.values[ 1 ] = params.shift() || '';
                    obj.values[ 2 ] = params.shift() || '';
                    stop1 = params.shift() || '';
                    obj.values[ 3 ] = params.shift() || '';
                    stop2 = params.shift() || '';
                    obj.orig = [ 
                        obj.values[ 1 ],
                        obj.values[ 2 ],
                        obj.values[ 3 ] 
                    ].join( ' ' );
                } else {
                    obj.values[ 0 ] = value;
                }
            }
          
            return obj;
        },
        
        image_url: function( theme, value ) {
            var self = this,
                parts = value.toString().match( /url\(['" ]*(.+?)['" ]*\)/ ),
                path = self.is_empty( parts ) ? null : parts[ 1 ],
                url = window.ms_ajax.theme_uri + '/' + ( 'parent' === theme ? window.ms_ajax.parnt : window.ms_ajax.child ) + '/',
                image_url;
            if ( !path ) { 
                return false; 
            } else if ( path.toString().match( /^(data:|https?:|\/)/ ) ) { 
                image_url = value; 
            } else { 
                image_url = 'url(' + url + path + ')'; 
            }
            return image_url;
        },
    
        setup_menus: function() {
            var self = this;
           
            self.setup_query_menu();
            self.setup_selector_menu();
            self.setup_rule_menu();
            self.setup_new_rule_menu();
            self.load_queries();
            self.load_rules();
           
            self.set_query( self.currquery );
        },
        
        load_queries: function() {
            var self = this;
          
            self.query_css( 'queries', null );
        },
        
        load_selectors: function() {
            var self = this;
           
            self.query_css( 'selectors', self.currquery );
        },
        
        load_rules: function() {
            var self = this;
          
            self.query_css( 'rules', null );
        },
        
        load_selector_values: function() {
            var self = this;
           
            self.query_css( 'qsid', self.currqsid );
        },
        
        get_queries: function( request, response ) {
           
            var //self = this,
                arr = [], 
                matcher = new RegExp( $.ui.autocomplete.escapeRegex( request.term ), "i" );
            if ( $.ms_themeeditor.is_empty( this.element.data( 'menu' ) ) ) {
                arr.push( { 'label': window.ms_ajax.nosels_txt, 'value': null } );
            } else {
                // note: key = ndx, value = query name
                $.each( this.element.data( 'menu' ), function( key, val ) {
                    if ( matcher.test( val ) ) {
                        arr.push( { 'label': val, 'value': val } );
                    }
                } );
            }
            response( arr );
        },
        
        get_selectors: function( request, response ) {
			
				var //self = this,
				arr = [], 
				matcher = new RegExp( $.ui.autocomplete.escapeRegex( request.term ), "i" );
					
				if ( $.ms_themeeditor.is_empty( this.element.data( 'menu' ) ) ) {
					arr.push( { 'label': window.ms_ajax.nosels_txt, 'value': null } );
				} else {
				   
				$.each( this.element.data( 'menu' ), function( key, val ) {
				
					if ( matcher.test( key ) ) {
						arr.push( { 'label': key, 'value': val } );
					}
				
				} );
            }
            response( arr );
        },
        
        get_rules: function( request, response ) {
           
            var //self = this,
                arr = [], 
                matcher = new RegExp( $.ui.autocomplete.escapeRegex( request.term ), "i" );
            if ( $.ms_themeeditor.is_empty( this.element.data( 'menu' ) ) ) {
                arr.push( { 'label': window.ms_ajax.nosels_txt, 'value': null } );
            } else {
                // note: key = ruleid, value = rule name
                $.each( this.element.data( 'menu' ), function( key, val ) {
                    if ( matcher.test( key ) ) {
                        arr.push( { 'label': key, 'value': val } );
                    }
                } );
            }
            response( arr );
        },
                
        get_filtered_rules: function( request, response ) {
            //console.log( 'get_filtered_rules' );
            var arr = [],
                matcher = new RegExp( $.ui.autocomplete.escapeRegex( request.term ), "i" ); //,
            $.each( $( '#ctc_rule_menu' ).data( 'menu' ), function( key, val ) {
                //multiple versions of rule ok
                if ( matcher.test( key ) ) {
                    arr.push( { 'label': key, 'value': val } );
                }
            } );
            response( arr );
        },
        
        merge_ruleval_arrays: function( rule, value, isnew ) {
            //**console.log( 'merge_ruleval_arrays' );
            var self = this,
                valarr = {},
                nextval = isnew ? value.child.pop() : null; // if new rule, pop off the top before counting
            //**console.log( value );
            $.each( [ 'parnt', 'child' ], function( ndx, themetype ) {
                // iterate through parent and child val arrays and populate new assoc array with parent/child for each rulevalid
                if ( !self.is_empty( value[ themetype ] ) ) {
                    $.each( value[ themetype ], function( ndx2, val ) {
                        if ( isnew ) {
                            // if new rule, increment new rulevalid but do not add to parent/child assoc array
                            if ( parseInt( val[ 2 ] ) >= parseInt( nextval[ 2 ] ) ) {
                                nextval[ 2 ] = parseInt( val[ 2 ] ) + 1;
                            }
                        } else {
                            // add to parent/child assoc array with rulevalid as key
                            if ( self.is_empty( valarr[ val[ 2 ] ] ) ) {
                                valarr[ val[ 2 ] ] = {};
                            }
                            valarr[ val[ 2 ] ][ themetype ] = val;
                        }
                    } );
                }
            } );
            // if new rule, create new parent child assoc array element with new rulevalid as key
            if ( isnew ) {
                valarr[ nextval[ 2 ] ] = {
                    parnt: [],
                    child: nextval
                };
            }
            return valarr;
        },

        input_row: function( qsid, rule, seq, data, isnew ) {
            //console.log( 'in input_row' );
            var self = this,
                html = '';
            if ( !self.is_empty( data ) && !self.is_empty( data.value ) && !self.is_empty( data.value[ rule ] ) ) {
                var value = data.value[ rule ],
                    valarr = self.merge_ruleval_arrays( rule, value, isnew );
                $.each( valarr, function( ndx, val ) {
                    var pval = self.decode_value( rule, self.is_empty( val.parnt ) ? '' : val.parnt[ 0 ] ),
                        pimp = self.is_empty( val.parnt ) || self.is_empty( val.parnt[ 1 ], 1 ) ? 0 : 1,
                        cval = self.decode_value( rule, self.is_empty( val.child ) ? '' : val.child[ 0 ] ),
                        cimp = self.is_empty( val.child ) || self.is_empty( val.child[ 1 ], 1 ) ? 0 : 1;
                    html += '<div class="ctc-' + ( 'ovrd' === seq ? 'input' : 'selector' ) + '-row clearfix"><div class="ms-input-cell">';
                    if ( 'ovrd' === seq ) {
                        html += rule.replace( /\d+/g, self.frascii );
                    } else {
                        html += data.selector + '<br/><a href="#" class="ctc-selector-edit"' +
                            ' id="ctc_selector_edit_' + qsid + '" >' + self.getxt( 'edit' ) + '</a> ' +
                            ( self.is_empty( pval.orig ) ? self.getxt( 'child_only' ) : '' );
                    }
                    html += '</div><div class="ctc-parent-value ms-input-cell"' + ( 'ovrd' !== seq ? ' style="display:none"' : '' ) +
                        ' id="ctc_' + seq + '_parent_' + rule + '_' + qsid + '_' + ndx + '">' +
                        ( self.is_empty( pval.orig ) ? '[no value]' : pval.orig + ( pimp ? self.getxt( 'important' ) : '' ) ) +
                        '</div><div class="ms-input-cell">';
                    if ( !self.is_empty( pval.names ) ) {
                        $.each( pval.names, function( namendx, newname ) {
                            newname = ( self.is_empty( newname ) ? '' : newname );
                            html += '<div class="ctc-child-input-cell ctc-clear">';
                            var id = 'ctc_' + seq + '_child_' + rule + '_' + qsid + '_' + ndx + newname,
                                newval;
                            if ( false === ( newval = cval.values.shift() ) ) {
                                newval = '';
                            }
                                
                            html += ( self.is_empty( newname ) ? '' : self.getxt( newname ) + ':<br/>' ) +
                                '<input type="text" id="' + id + '" name="' + id + '" class="ctc-child-value' +
                                ( ( newname + rule ).toString().match( /color/ ) ? ' color-picker' : '' ) +
                                ( ( newname ).toString().match( /url/ ) ? ' ctc-input-wide' : '' ) +
                                '" value="' + self.escquo( newval ) + '" /></div>';
                        } );
                        var impid = 'ctc_' + seq + '_child_' + rule + '_i_' + qsid + '_' + ndx;
                        html += '<label style="visibility: hidden;" class="ms_hidden" for="' + impid + '"><input  type="checkbox"' +
                            ' id="' + impid + '" name="' + impid + '" value="1" ' +
                            ( cimp ? 'checked' : '' ) + ' />' +
                            self.getxt( 'important' ) + '</label>';
                    }
                    html += '</div>';
                    if ( 'ovrd' !== seq ) {
                        html += '<div class="ctc-swatch ctc-specific"' +
                            ' id="ctc_child_' + rule + '_' + qsid + '_' + ndx + '_swatch">' +
                            self.getxt( 'swatch' ) + '</div>' +
                            '<div class="ctc-child-input-cell ctc-button-cell"' +
                            ' id="ctc_save_' + rule + '_' + qsid + '_' + ndx + '_cell">' +
                            '<input type="button" class="button ctc-save-input"' +
                            ' id="ctc_save_' + rule + '_' + qsid + '_' + ndx + '"' +
                            ' name="ctc_save_' + rule + '_' + qsid + '_' + ndx + '"' +
                            ' value="Save" /></div>';
                    }
                    html += '</div><!-- end input row -->' + "\n";
                } );
            }
            return html;
        },
        
        scrolltop: function() {
            $('html, body, .ms-option-panel-container').animate( { scrollTop: 0 } );        
        },
        
        css_preview: function( theme ) {
            var self = this;
          //  console.log( 'css_preview: ' + theme );
            if ( !( theme = theme.match( /(child|parnt)/ )[ 1 ] ) ) {
                theme = 'child';
            }
           // console.log( 'css_preview: ' + theme );
            // retrieve raw stylesheet ( parent or child )
            self.query_css( 'preview', theme );
        },
        
        
        setup_iris: function( obj ) {
            // deprecated: using spectrum for alpha support
            var self = this;
            //self.setup_spectrum( obj );
        },        
        
        addhash: function( color ) {
            return color.replace( /^#?([a-f0-9]{3,6}.*)/, "#$1" );
        },
        color_text: function( color ) {
            var self = this;
            if ( self.is_empty( color ) ) {
                return '';
            } else if ( color.getAlpha() < 1 ) {
                return color.toRgbString();
            } else {
                return color.toHexString();
            }
        },
        
        setup_query_menu: function() {
            var self = this;
            //console.log( 'setup_query_menu' );
            try {
                $( '#ctc_sel_ovrd_query' ).autocomplete( {
                    source: self.get_queries,
                    minLength: 0,
                    selectFirst: true,
                    autoFocus: true,
                    select: function( e, ui ) {
                        if ( $( '#ctc_rewrite_query' ).length ){
                            // copy selected to rewrite input if active
                            $( '#ctc_rewrite_query' ).val( ui.item.value );
                            $( '#ctc_sel_ovrd_query' ).val( '' );
                        } else {
                            // otherwise set query
                            self.set_query( ui.item.value );
                            self.reset_qsid();
                        }
						//alert(ui.item.value);
                        return false;
                    },
                    focus: function( e ) { 
                        e.preventDefault(); 
                    }
                } ).data( 'menu' , {} );
            } catch ( exn ) {
                self.jquery_exception( exn, 'Query Menu' );
            }
        },
        
        setup_selector_menu: function() {
            var self = this;
            //console.log( 'setup_selector_menu' );
            try {
                $( '#ctc_sel_ovrd_selector' ).autocomplete( {
                    source: self.get_selectors,
                    selectFirst: true,
                    autoFocus: true,
                    select: function( e, ui ) {
                        if ( $( '#ctc_rewrite_selector' ).length ){
                            // copy selected to rewrite input if active
                            $( '#ctc_rewrite_selector' ).val( ui.item.label );
                            $( '#ctc_sel_ovrd_selector' ).val( '' );
                        } else {
                            // otherwise set selector
                            self.set_selector( ui.item.value, ui.item.label );
                        }
                        return false;
                    },
                    focus: function( e ) { 
                        e.preventDefault(); 
                    }
                } ).data( 'menu' , {} );
            } catch ( exn ) {
                self.jquery_exception( exn, 'Selector Menu' );
            }
        },
        
        setup_rule_menu: function() {
            var self = this;
            //console.log( 'setup_rule_menu' );
            try {
            $( '#ctc_rule_menu' ).autocomplete( {
                source: self.get_rules,
                //minLength: 0,
                selectFirst: true,
                autoFocus: true,
                select: function( e, ui ) {
                    self.set_rule( ui.item.value, ui.item.label );
                    return false;
                },
                focus: function( e ) { 
                    e.preventDefault(); 
                }
            } ).data( 'menu' , {} );
            } catch ( exn ) {
                self.jquery_exception( exn, 'Property Menu' );
            }
        },
        
        setup_new_rule_menu: function() {
            var self = this;
            try {
            $( '#ctc_new_rule_menu' ).autocomplete( {
                source: self.get_filtered_rules,
                //minLength: 0,
                selectFirst: true,
                autoFocus: true,
                select: function( e, ui ) {
                    //console.log( 'new rule selected' );
                    e.preventDefault();
                    var newrule = ui.item.label.replace( /[^\w\-]/g, self.toascii ),
                        row,
                        first;
                    //console.log( 'current qsdata before:' );
                    //console.log( self.currdata );
                    if ( self.is_empty( self.currdata.value ) ) {
                        self.currdata.value = {};
                    }
                    if ( self.is_empty( self.currdata.value[ ui.item.label ] ) ) {
                        self.currdata.value[ ui.item.label ] = {};
                    }
                    if ( self.is_empty( self.currdata.value[ ui.item.label ].child ) ) {
                        self.currdata.value[ ui.item.label ].child = [];
                    }
                    //console.log( 'current qsdata after:' );
                    //console.log( self.currdata );
                    // seed current qsdata with new blank value with id 1
                    // this will be modified during input_row function to be next id in order
                    self.currdata.value[ ui.item.label ].child.push( [ '', 0, 1, 1 ] );
                    row = $( self.input_row( self.currqsid, newrule, 'ovrd', self.currdata, true ) );
                    $( '#ctc_sel_ovrd_rule_inputs' ).append( row );
                    $( '#ctc_new_rule_menu' ).val( '' );
                    
                    row.find( 'input[type="text"]' ).each( function( ndx, el ) {
                        if (! first) {
                            first = el;
                        }
                        if ( $( el ).hasClass( 'color-picker' ) ){
                            //self.setup_spectrum( el );
                        }
                    } );
                    if ( first ){
                        $( first ).focus();
                    }
//                    if ( self.jqueryerr.length ) {
//                        self.jquery_notice( 'setup_new_rule_menu' );
//                    }
                    return false;
                },
                focus: function( e ) { 
                    e.preventDefault(); 
                }
            } ).data( 'menu' , {} );
            } catch ( exn ) {
                self.jquery_exception( exn, 'New Property Menu' );
            }
        },
        set_theme_params: function( themetype, themedir ) {
           			
			$( '#child_author' ).val( window.ms_ajax.themes[ themetype ][ themedir ].Author );
            $( '#child_version' ).val( window.ms_ajax.themes[ themetype ][ themedir ].Version );
            $( '#child_author_uri' ).val( window.ms_ajax.themes[ themetype ][ themedir ].AuthorURI );
            $( '#child_theme_uri' ).val( window.ms_ajax.themes[ themetype ][ themedir ].ThemeURI );
            $( '#child_descr' ).val( window.ms_ajax.themes[ themetype ][ themedir ].Descr );
            $( '#child_tags' ).val( window.ms_ajax.themes[ themetype ][ themedir ].Tags );
        },
        update_form: function() {
            var self        = this,
                themedir;
            $( '#input_row_stylesheet_handling_container,#input_row_parent_handling_container,#ctc_additional_css_files_container,#input_row_new_theme_slug,#input_row_duplicate_theme_slug,#ctc_copy_theme_mods,#ctc_child_header_parameters,#ctc_configure_submit,#input_row_theme_slug' ).slideUp( 'fast' );
            $( '#ctc_configure_submit .ctc-step' ).text( '9' );
            
			var ms_child_theme_action = $('#ms_theme_editor_action').val();
						
			//if ( $( '#ctc_theme_child' ).length && !$( '#ctc_child_type_new' ).is( ':checked' ) ) {
			if ( $( '#ctc_theme_child' ).length && ms_child_theme_action !='new' ) {	
			
                themedir    = $( '#ctc_theme_child' ).val();
				
                self.existing = 1;
                self.currparnt = window.ms_ajax.themes.child[ themedir ].Template;
				
                self.autogen_slugs();
                $( '#ctc_theme_parnt' ).val( self.currparnt );                
                self.set_theme_params( 'child', themedir );
                
				if ( ms_child_theme_action == 'duplicate') {	
                    $( '#ctc_child_template' ).val( self.testslug );
                    $( '#child_name' ).val( self.testname );
                    $( '.ctc-analyze-theme, .ctc-analyze-howto' ).show();
                    $( '#ctc_load_styles' ).val( 'Duplicate Child Theme' );
                } else if ( ms_child_theme_action == 'reset') {
                    $( '#ctc_configure_submit .ctc-step' ).text( '3' );
                    $( '#ctc_configure_submit' ).slideDown( 'fast' );
                    $( '#theme_slug_container' ).text( themedir );
                    $( '.ctc-analyze-theme, .ctc-analyze-howto' ).hide();
                    //$( '#input_row_theme_slug' ).slideDown( 'fast' );
                    $( '#ctc_enqueue_none' ).prop( 'checked', true );
                    $( '#ctc_load_styles' ).val( 'Reset Child Theme' );
                } else {
                    $( '#ctc_child_template' ).val( '' );
                    $( '#theme_slug_container' ).text( themedir );
                    $( '.ctc-analyze-theme, .ctc-analyze-howto' ).show();
                    $( '#child_name' ).val( self.getname( 'child' ) );
                    $( '#ctc_load_styles' ).val( 'Configure Child Theme' );
                }
                $( '#input_row_existing_theme_option' ).slideDown( 'fast' );
                $( '#input_row_new_theme_option' ).slideUp( 'fast' );
            } else {
				
                self.existing = 0;
                self.autogen_slugs();
                themedir = $( '#ctc_theme_parnt' ).val();
				
				//new code
				self.currparnt = $( '#ctc_theme_parnt' ).val();
				
                self.set_theme_params( 'parnt', self.currparnt );
                $( '#input_row_existing_theme_option,#input_row_duplicate_theme_container,#input_row_theme_slug' ).slideUp( 'fast' );
				//console.log(self.testslug );
                $( '#input_row_new_theme_option' ).slideDown( 'fast' ); 
                $( '#child_name' ).val( self.testname );
                $( '#ctc_child_template' ).val( self.testslug );
                $( '.ctc-analyze-theme, .ctc-analyze-howto' ).show();
                $( '#ctc_load_styles' ).val( 'Create New Child Theme' );
            }
        },
        set_notice: function( noticearr ) {
            var self = this,
                errorHtml = '',
                out;
            if ( !self.is_empty( noticearr ) ) {
                $.each( noticearr, function( type, list ) {
                    errorHtml += '<div class="' + type + ' notice is-dismissible dashicons-before"><ul>' + "\n";
                    $( list ).each( function( ndx, el ) {
                        errorHtml += '<li>' + el.toString() + '</li>' + "\n";
                    } );
                    errorHtml += '</ul></div>';        
                } );
            }
            out = $( errorHtml );
            $( '#ms_error_notice' ).html( out );
            self.bind_dismiss( out );
            $( 'html, body' ).animate( { scrollTop: 0 }, 'slow' );        
        },
        
        set_parent_menu: function( obj ) {
            
            var self = this;
            self.currparnt = obj.value;
            self.update_form();
            
        },
        
        set_child_menu: function( obj ) {
            var self = this;
            self.currchild = obj.value;
            self.update_form();
        },
        
        set_query: function( value ) {
            var self = this;
            if ( self.is_empty( value ) ) {
                return false;
            }
            //console.log( 'set_query: ' + value );
            self.currquery = value;
            $( '#ctc_sel_ovrd_query' ).val( '' );
            $( '#ms_sel_ovrd_query_selected' ).text( value );
            $( '#ctc_sel_ovrd_selector' ).val( '' );
            $( '#ctc_sel_ovrd_selector_selected' ).html( '&nbsp;' );
            self.load_selectors();
            self.scrolltop();
        },
        
        reset_qsid: function(){
            //console.log( 'resetting all qsid inputs...' );
            self.currqsid = null;
            $( '#ctc_sel_ovrd_rule_inputs' ).empty();
            $( '#ctc_sel_ovrd_new_rule,#input_row_load_order,#ms_sel_ovrd_rule_inputs_container' ).hide().find( '.ctc-child-value' ).remove();
            $( '.ctc-rewrite-toggle' ).hide();
        },
        
        set_selector: function( value, label ) {
            var self = this;
            label = null;
            if ( self.is_empty( value ) ) {
                return false;
            }

            $( '#ctc_sel_ovrd_selector' ).val( '' );
            self.currqsid = value;
            self.reload = false;
            self.load_selector_values();
            self.scrolltop();
        },
        
        set_rule: function( value, label ) {
  
            var self = this;
            if ( self.is_empty( value ) ) {
                return false;
            }
            $( '#ctc_rule_menu' ).val( '' );
            $( '#ctc_rule_menu_selected' ).text( label );
            $( '.ctc-rewrite-toggle' ).text( self.getxt( 'rename' ) );
            $( '#ctc_rule_value_inputs, #ctc_input_row_rule_header' ).show();
            
            self.query_css( 'rule_val', value );
            self.scrolltop();
        },
        
        set_qsid: function( obj ) {
            var self = this;
          
            self.currqsid = $( obj ).attr( 'id' ).match( /_(\d+)$/ )[ 1 ];
            self.focus_panel( '#query_selector_options' );
            self.reload = true;
            self.load_selector_values();  
        },
        
        query_css: function( obj, key, params ) {
       
            var self = this,
                postdata = { 'ctc_query_obj' : obj, 'ctc_query_key': key },
                status_sel = '#ctc_status_' + obj + ( 'val_qry' === obj ? '_' + key : '' );
            
            if ( 'object' === typeof params ) {
                $.each( params, function( key, val ) {
                    postdata[ 'ctc_query_' + key ] = val;
                } );
            }
            $( '.query-icon,.ctc-status-icon' ).remove();
      
            $( status_sel + ' .ctc-status-icon' ).remove();
            $( status_sel ).append( '<span class="ctc-status-icon spinner is-active query-icon"></span>' );
          
            postdata.action = ( !self.is_empty( $( '#ctc_action' ).val() ) &&
                'plugin' === $( '#ctc_action' ).val() ) ? 
                    'ctc_plgqry' : 'ms_query';
            postdata._wpnonce = $( '#_wpnonce' ).val();
           
            self.ajax_post( obj, postdata );
        },
       
        save: function( obj ) {
        
            var self = this,
                postdata = {},
                $selector, 
                $query, 
                $imports,
                id = $( obj ).attr( 'id' ), 
                newsel, 
                origsel;

            $( obj ).prop( 'disabled', true );
         
            $( '.ctc-query-icon,.ctc-status-icon' ).remove();    
          				
				
            if ( id.match( /ctc_configtype/ ) ) {
                $( obj ).parents( '.ms-input-row' ).first()
                    .append( '<span class="ctc-status-icon spinner save-icon"></span>' );
                postdata.ctc_configtype = $( obj ).val();
            } else if ( ( $selector = $( '#ctc_new_selectors' ) ) && 
                'ctc_save_new_selectors' === $( obj ).attr( 'id' ) ) {
			    var custom_css =$('#ctc_new_selectors').val();
				if(custom_css !='')
				{
					var custom_msg = confirm("Are you sure all custom css in correct format?");
					if(custom_msg)
					{
						postdata.ctc_new_selectors = $selector.val();
						if ( ( $query = $( '#ms_sel_ovrd_query_selected' ) ) ) {
							postdata.ctc_sel_ovrd_query = $query.text();
						}
						self.reload = true;
						alert('All Custom Css Saved Sucessfully');
					}
				}
				else
				{
					alert('Please Enter Value');
					//return false;
					
				}
            } else if ( ( $imports = $( '#ms_child_imports' ) ) &&
                'ms_save_imports' === id ) {
				var custom_css =$('#ms_child_imports').val();
				if(custom_css !='')
				{
                  postdata.ms_child_imports = $imports.val();
				  alert('All Webfonts/css information Saved Sucessfully');
				  jQuery('#import_options_panel .import_sucess_msg').show().html('<p>All Webfonts/css information Saved Sucessfully</p>')
				}
				else
				{
					alert('Please Enter value for Webfonts/css');
				}
            } else if ( 'ctc_is_debug' === id ) {
                postdata.ctc_is_debug = $( '#ctc_is_debug' ).is( ':checked' ) ? 1 : 0;
            } else {
                // coalesce inputs
                postdata = self.coalesce_inputs( obj );
            }
            $( '.save-icon' ).addClass( 'is-active' );
            // add rename selector value if it exists
            $.each( [ 'query', 'selector' ], function( ndx, el ){
                if ( $( '#ctc_rewrite_' + el ).length ){
                    
                    newsel = $( '#ctc_rewrite_' + el ).val();
                    origsel = $( '#ctc_rewrite_' + el + '_orig' ).val();
                    if ( self.is_empty( newsel ) || !newsel.toString().match( /\w/ ) ) {
                        newsel = origsel;
                    } else {
                        postdata[ 'ctc_rewrite_' + el ] = newsel;
                        self.reload = true;
                    }
                    $( '#ctc_sel_ovrd_' + el + '_selected' ).html( newsel );
                }
                $( '.ctc-rewrite-toggle' ).text( self.getxt( 'rename' ) );
            } );
          
            postdata.action = ( !self.is_empty( $( '#ctc_action' ).val() ) &&
                'plugin' === $( '#ctc_action' ).val() ) ? 
                    'ctc_plugin' : 'ms_update';
            postdata._wpnonce = $( '#_wpnonce' ).val();
		
            self.ajax_post( 'qsid', postdata );
        },
        
        ajax_post: function( obj, data, datatype ) {
            var self = this;
            
            $.ajax( { 
                url:        window.ms_ajax.ajaxurl,  
                data:       data,
                dataType:   
              
                ( self.is_empty( datatype ) ? 'json' : datatype ), 
                type:       'POST'
            } ).done( function( response ) {
               
                self.handle_success( obj, response );
            } ).fail( function() {
                self.handle_failure( obj );
            } ).always( function() {
                if ( self.jqueryerr.length ) {
                    self.jquery_notice();
                }
            } );  
        },
        
        handle_failure: function( obj ) {
            var self = this;
            //console.log( 'handle_failure: ' + obj );
            $( '.query-icon, .save-icon' ).removeClass( 'spinner' ).addClass( 'failure' );
            $( 'input[type=submit], input[type=button], input[type=checkbox],.ctc-delete-input' ).prop( 'disabled', false );
            $( '.ajax-pending' ).removeClass( 'ajax-pending' );
            //FIXME: return fail text in ajax response
            if ( 'preview' === obj ) {
                $( '#view_parnt_options_panel,#view_child_options_panel' )
                    .text( self.getxt( 'css_fail' ) );
            }
        },
        
        handle_success: function( obj, response ) {
            var self = this;
            
            $( '.query-icon, .save-icon' ).removeClass( 'spinner' );
            $( '.ajax-pending' ).removeClass( 'ajax-pending' );
            // hide spinner
            if ( self.is_empty( response ) ) {
                self.handle_failure( obj );
            } else {
                $( '#ctc_new_selectors' ).val( '' );
                
                $( '.query-icon, .save-icon' ).addClass( 'success' );
                $( 'input[type=submit], input[type=button], input[type=checkbox],.ctc-delete-input' ).prop( 'disabled', false );
                // update ui from each response object  
                $( response ).each( function() {
                    if ( 'function' === typeof self.update[ this.obj ] ) {
                        //console.log( 'executing method update.' + this.obj );
                        self.update[ this.obj ].call( self, this );
                    } else {
                        //console.log( 'Fail: no method update.' + this.obj );
                    }
                } );
            }
        },
        
        jquery_exception: function( exn, type ) {
            var self = this,
                ln = self.is_empty( exn.lineNumber ) ? '' : ' line: ' + exn.lineNumber,
                fn = self.is_empty( exn.fileName ) ? '' : ' ' + exn.fileName.split( /\?/ )[ 0 ];
            self.jqueryerr.push( '<code><small>' + type + ': ' + exn.message + fn + ln + '</small></code>' );
            //console.log( 'jquery error detected' );
        },
        
        jquery_notice: function( fn ) {
            //console.log( fn );
            fn = null;
            var self        = this,
                culprits    = [],
                errors      = [];
            if ( self.jqueryerr.length ){
                // disable form submits
                $( 'input[type=submit], input[type=button]' ).prop( 'disabled', true );
                $( 'script' ).each( function(){
                    var url = $( this ).prop( 'src' );
                    if ( !self.is_empty( url ) && url.match( /jquery(\.min|\.js|\-?ui)/i ) &&
                        ! url.match( /load\-scripts.php/ ) ) {
                        culprits.push( '<code><small>' + url.split( /\?/ )[ 0 ] + '</small></code>' );
                    }
                } );
                errors.push( '<strong>' + self.getxt( 'js' ) + '</strong> ' + self.getxt( 'contact' ) );
                //if ( 1 == window.ms_ajax.is_debug ) {
                    errors.push( self.jqueryerr.join( '<br/>' ) );
                //}
                if ( culprits.length ) {
                    errors.push( self.getxt( 'jquery' ) + '<br/>' + culprits.join( '<br/>' ) );
                }
                errors.push( self.getxt( 'plugin' ) );
            }
            //return errors;
            self.set_notice( { 'error': errors } );
        },
            
        update: {
            // render individual selector inputs on Query/Selector tab
            qsid: function( res ) {
                var self = this,
                    id, html, val, empty;
                self.currqsid = res.key;
                self.currdata = res.data;
                //console.log( 'update: ' + self.reload );
                //console.log( 'update.qsid: ' + self.currqsid );
                $( '#ctc_sel_ovrd_qsid' ).val( self.currqsid );
                if ( self.is_empty( self.currdata.seq ) ) {
                    $( '#ctc_child_load_order_container' ).empty();
                } else {
                    id = 'ctc_ovrd_child_seq_' + self.currqsid;
                    val = parseInt( self.currdata.seq );
                    html = '<input type="hidden" id="' + id + '" name="' + id + '"' +
                        ' class="ctc-child-value" value="' + val + '" />';
                    $( '#ctc_child_load_order_container' ).html( html );
                }
                if ( self.is_empty( self.currdata.value ) ) {
                    //console.log( 'qsdata is empty' );
                    empty = true;
                    $( '#ctc_sel_ovrd_rule_inputs' ).empty();
                    // prune empty selectors after clearing data to prune
                    self.load_selectors();
                } else {
                    //console.log( 'qsdata NOT empty' );
                    empty = false;
                    html = '';
                    $.each( self.currdata.value, function( rule, value ) {
                        value = null;
                        html += self.input_row( self.currqsid, rule, 'ovrd', self.currdata );
                    } );
                    $( '#ctc_sel_ovrd_rule_inputs' ).html( html ).find( '.color-picker' ).each( function() {
                        //self.setup_spectrum( this );
                    } );
                    self.coalesce_inputs( '#ctc_child_all_0_swatch' );
                }
//                if ( self.jqueryerr.length ) {
//                    self.jquery_notice( 'update.qsid' );
//                } else {
                    //console.log( 'reload menus: ' + ( self.reload ? 'true' : 'false' ) );
                    if ( self.reload ) {
                        self.load_queries();
                        self.load_selectors();
                        self.set_query( self.currdata.query );
                        self.load_rules();
                    }
                    $( '#ctc_sel_ovrd_selector_selected' ).text( self.currdata.selector );

                    self.maybe_show_rewrite();
                    if ( empty ){
                        self.reset_qsid();
                    } else {
                        $( 
                        '#ctc_sel_ovrd_rule_header,' +
                        '#ctc_sel_ovrd_new_rule,' +
                        '#ms_sel_ovrd_rule_inputs_container,' +
                        '#ctc_sel_ovrd_rule_inputs,' +
                        '#input_row_load_order'
                        ).fadeIn();
                    }
                    //self.scrolltop();
//                }
            }, 
            // render list of unique values for given rule on Property/Value tab
            rule_val: function( res ) {
			
                var self = this,
                    rule = $( '#ctc_rule_menu_selected' ).text(), 
                    html = '<div class="ms-input-row clearfix" id="ctc_rule_row_' + rule + '">' + "\n";
                //console.log( 'rule: ' + rule );
                if ( !self.is_empty( res.data ) ) {
                    $.each( res.data, function( valid, value ) {
                        var parentObj = self.decode_value( rule, value );
                        html += '<div class="ctc-parent-row clearfix"' +
                            ' id="ctc_rule_row_' + rule + '_' + valid + '">' + "\n" +
                            '<div class="ms-input-cell ctc-parent-value"' +
                            ' id="ctc_' + valid + '_parent_' + rule + '_' + valid + '">' +
                            parentObj.orig + '</div>' + "\n" +
                            '<div class="ms-input-cell">' + "\n" +
                            '<div class="ctc-swatch ctc-specific"' +
                            ' id="ctc_' + valid + '_parent_' + rule + '_' + valid + '_swatch">' +
                            self.getxt( 'swatch' ) + '</div></div>' + "\n" +
                            '<div class="ms-input-cell">' +
                            '<a href="#" class="ctc-selector-handle"' +
                            ' id="ctc_selector_' + rule + '_' + valid + '">' +
                            self.getxt( 'selector' ) + '</a></div>' + "\n" +
                            '<div id="ctc_selector_' + rule + '_' + valid + '_container"' +
                            ' class="ctc-selector-container">' + "\n" +
                            '<a href="#" id="ctc_selector_' + rule + '_' + valid + '_close"' +
                            ' class="ctc-selector-handle ctc-exit" title="' +
                            self.getxt( 'close' ) + '"></a>' +
                            '<div id="ctc_selector_' + rule + '_' + valid + '_inner_container"' +
                            ' class="ctc-selector-inner-container clearfix">' + "\n" +
                            '<div id="ctc_status_val_qry_' + valid + '"></div>' + "\n" +
                            '<div id="ctc_selector_' + rule + '_' + valid + '_rows"></div>' + "\n" +
                            '</div></div></div>' + "\n";
                    } );
                    html += '</div>' + "\n";
                }
                $( '#ctc_rule_value_inputs' ).html( html ).find( '.ctc-swatch' ).each( function() {
                    self.coalesce_inputs( this );
                } );
            },
            // render list of selectors grouped by query for given value on Property/Value Tab
            val_qry: function( res ) {
                //console.log( 'in val_qry' );
                //console.log( res );
                var self = this,
                    html = '',
                    page_rule,
                    selector;
                if ( !self.is_empty( res.data ) ) {
                    $.each( res.data, function( rule, queries ) {
                        page_rule = rule;
                        $.each( queries, function( query, selectors ) {
                            html += '<h4 class="ctc-query-heading">' + query + '</h4>' + "\n";
                            if ( !self.is_empty( selectors ) ) {
                                $.each( selectors, function( qsid, qsdata ) {
                                    html += self.input_row( qsid, rule, res.key, qsdata );
                                } );
                            }
                        } );
                    } );
                }
                selector = '#ctc_selector_' + page_rule + '_' + res.key + '_rows';
                //console.log( selector );
                
                $( selector ).html( html ).find( '.color-picker' ).each( function() {
                    //self.setup_spectrum( this );
                } );
                $( selector ).find( '.ctc-swatch' ).each( function() {
                    self.coalesce_inputs( this );
                } );
//                if ( self.jqueryerr.length ) {
//                    self.jquery_notice( 'val_qry' );
//                }
            },
            // populate list of queries and attach to query input element
            queries: function( res ) {
                $( '#ctc_sel_ovrd_query' ).data( 'menu', res.data );
            },
            // populate list of selectors and attach to selector input element
            selectors: function( res ) {
                $( '#ctc_sel_ovrd_selector' ).data( 'menu', res.data );
            },
            // populate list of rules and attach to rule input element
            rules: function( res ) {
                $( '#ctc_rule_menu' ).data( 'menu', res.data );
            },
            // render debug output
            debug: function( res ) {
                $( '#ctc_debug_box' ).val( $( '#ctc_debug_box' ).val() + res.data );
                //console.log( 'debug:' );
                //console.log( res.data );
            },
            // render stylesheet preview on child or parent css tab
            preview: function( res ) {
                $( '#view_' + res.key + '_options_panel' ).text( res.data );
            },
            dismiss: function() { // res ) {
               
            }
            
        },
        // applies core dismiss behavior to injected elements 
        bind_dismiss: function( el ) {
           
        },
       
        reset_handling: function() {
            //console.log( '----> resetting form...' );
			$( '#parnt_analysis_notice' ).html('');
            $( '#parnt_analysis_notice .notice, #child_analysis_notice .notice' ).toggle();
            $( '#ctc_enqueue_enqueue' ).prop( 'checked', true );
            $( '#ctc_handling_primary' ).prop( 'checked', true );
            $( '#ctc_ignoreparnt' ).prop( 'checked', false );
            $( '#ctc_repairheader' ).prop( 'checked', false );
        },
        // initialize object vars, bind event listeners to elements, load menus and start plugin
        init: function() {
            
            var self = this;
           
            self.currparnt = $( '#ctc_theme_parnt' ).val();
            self.currchild = $( '#ctc_theme_child' ).length ? $( '#ctc_theme_child' ).val() : '';
			
			$( '#theme_editor_main' ).on( 'click', '.ctc-section-toggle', function( e ) {
                e.preventDefault();
                $( this ).parents( '.ms-input-row, .notice-warning, .updated, .error' ).first().find( '.ctc-section-toggle' )
                    .each( function() { 
                        $( this ).toggleClass( 'open' );
                    } );
                var id = $( this ).attr( 'id' ).replace(/\d$/, '') + '_content';
                $( '#' + id ).stop().slideToggle( 'fast' );
                return false;
            } );
            
            $( '#theme_editor_main' ).on( 'click', '.ctc-upgrade-notice .notice-dismiss', function() { 
            
                var postdata = {
                    'action': 'ctc_dismiss',
                    '_wpnonce': $( '#_wpnonce' ).val()
                };
                self.ajax_post( 'dismiss', postdata );
            } );
            
            if ( self.is_empty( self.jqueryerr ) ){
               
                $( '#theme_editor_main' ).on( 'click', '.ctc-selector-handle', function( e ) {
                    
                    e.preventDefault();
                    if ( $( this ).hasClass( 'ajax-pending' ) ) {
                        return false;
                    }
                    $( this ).addClass( 'ajax-pending' );
                   
                    var id = $( this ).attr( 'id' ).toString().replace( '_close', '' ),
                        parts = id.toString().match( /_([^_]+)_(\d+)$/ ),
                        rule,
                        valid;
                    if ( $( '#' + id + '_container' ).is( ':hidden' ) ) {
                        if ( !self.is_empty( parts[ 1 ] ) && !self.is_empty( parts[ 2 ] ) ) {
                            rule = parts[ 1 ];
                            valid = parts[ 2 ];
                            // retrieve selectors / values for individual value
                            self.query_css( 'val_qry', valid, { 'rule': rule } );
                        }
                    }
                    $( '#' + id + '_container' ).fadeToggle( 'fast' );
                    $( '.ctc-selector-container' ).not( '#' + id + '_container' ).fadeOut( 'fast' );
                } );
                
                $( '#theme_editor_main' ).on( 'click', '.ctc-save-input[type=button], .ctc-delete-input', function( e ) {
                    e.preventDefault();
                    if ( $( this ).hasClass( 'ajax-pending' ) ) {
                        return false;
                    }
                    $( this ).addClass( 'ajax-pending' );
                    self.save( this ); // refresh menus after updating data
                    return false;
                } );
                
                $( '#theme_editor_main' ).on( 'keydown', '.ctc-selector-container .ctc-child-value[type=text]', function( e ) {
				
                    if ( 13 === e.which ) { 
                        //console.log( 'return key pressed' );
                        var $obj = $( this ).parents( '.ctc-selector-row' ).find( '.ctc-save-input[type=button]' ).first();
                        if ( $obj.length ) {
                            e.preventDefault();
                            //console.log( $obj.attr( 'id' ) );
                            if ( $obj.hasClass( 'ajax-pending' ) ) {
                                return false;
                            }
                            $obj.addClass( 'ajax-pending' );
                            self.save( $obj );
                            return false;
                        }
                    }
                } );
                
                $( '#theme_editor_main' ).on( 'click', '.ctc-selector-edit', function( e ) {
                    e.preventDefault();
                    if ( $( this ).hasClass( 'ajax-pending' ) ) {
                        return false;
                    }
                    $( this ).addClass( 'ajax-pending' );
                    self.set_qsid( this );
                } );
                
                $( '#theme_editor_main' ).on( 'click', '.ctc-rewrite-toggle', function( e ) {
                    e.preventDefault();
                    self.selector_input_toggle( this );
                } );
                
                $( '#theme_editor_main' ).on( 'click', '#ctc_copy_selector', function(  ) {
                    var txt = $( '#ctc_sel_ovrd_selector_selected' ).text().trim();
                    if ( !self.is_empty( txt ) ){
                        $( '#ctc_new_selectors' ).val( $( '#ctc_new_selectors' ).val() + "\n" + txt + " {\n\n}" );
                    }
                } );
				
         
				
                $( '#ctc_configtype' ).on( 'change', function(  ) {
                    var val = $( this ).val();
                    if ( self.is_empty( val ) || 'theme' === val ) {
                        $( '.ctc-theme-only, .ms_theme_container' ).removeClass( 'ms-disabled' );
                        $( '.ctc-theme-only, .ms_theme_container input' ).prop( 'disabled', false );
                        try {
                            $( '#ctc_theme_parnt, #ctc_theme_child' ).themeMenu( 'enable' );
                        } catch ( exn ) {
                            self.jquery_exception( exn, 'Theme Menu' );
                        }
                    } else {
                        $( '.ctc-theme-only, .ms_theme_container' ).addClass( 'ms-disabled' );
                        $( '.ctc-theme-only, .ms_theme_container input' ).prop( 'disabled', true );
                        try {
                            $( '#ctc_theme_parnt, #ctc_theme_child' ).themeMenu( 'disable' );
                        } catch ( exn ) {
                            self.jquery_exception( exn, 'Theme Menu' );
                        }
                    }
                } );   
                 
                // these elements are not replaced so use direct selector events
                $( '.nav-tab' ).on( 'click', function( e ) {
                    e.preventDefault();
                    if ( $( e.target ).hasClass( 'ms-disabled' ) ) {
                        return false;
                    }
                    // clear the notice box
                    //set_notice( '' );
                    $( '.ctc-query-icon,.ctc-status-icon' ).remove();
                    var id = '#' + $( this ).attr( 'id' );
					
                    self.focus_panel( id );
                } );
                
                $( '#view_child_options, #view_parnt_options' ).on( 'click', function( e ){ 
                    if ( $( e.target ).hasClass( 'ajax-pending' ) || $( e.target ).hasClass( 'ms-disabled' ) ) {
                        return false;
                    }
                    $( e.target ).addClass( 'ajax-pending' );
                    self.css_preview( $( this ).attr( 'id' ) ); 
                } );
                
                           
                $( '#query_selector_form' ).on( 'submit', function( e ) {
                    e.preventDefault();
					var go_head = false;
					$("#ctc_sel_ovrd_rule_inputs .ctc-child-value").each(function ()
					{
						if($(this).val()!='')
						{
							go_head = true;
						}
					});
					
					if(go_head)
					{
						var $this = $( '#ctc_save_query_selector' );
						if ( $this.hasClass( 'ajax-pending' ) ) {
							return false;
						}
						$this.addClass( 'ajax-pending' );
						
						 self.save( $this ); // refresh menus after updating data
						 alert('All Information saved Sucessfully');
						 
						 $('#query_selector_options_panel .import_sucess_msg').show().html('<p>All Information saved Sucessfully</p>');
						return false;
					}
					else
					{
						alert('Please Enter Atleast one child value for saving Child Property');
					}
                } );
                
                $( '#ctc_rule_value_form' ).on( 'submit', function( e ) {
                    //console.log( 'rule value empty submit' );
                    e.preventDefault();
                    return false;
                } );                
                
                //code added by me			
				$( '#ms_theme_editor_action' ).on( 'change', function(  ) {
                  			   
				   self.reset_handling();
                   self.update_form();
                } );
				
				$( '#ctc_theme_parnt' ).on( 'change', function(  ) {
					
                   //alert($( '#ctc_theme_parnt' ).val());
				   
				   var text_data = $( '#ctc_theme_parnt option:selected' ).text();
				  // alert(text_data);
				   
				   $( '#testname' ).val(text_data);
				   self.reset_handling();
                   self.update_form();				   
				   //$( '.ctc-analyze-theme').click();
				    $('#child_analysis_notice').html('');
				   
                } );
				
				$( '#ctc_theme_child' ).on( 'change', function(  ) {
                   
				   self.reset_handling();
                   self.update_form();
				   
				   $('#child_analysis_notice').html('');
                } );
				
			
				
				$(document).on('click', '.ms_download_themes', function() {
					var theme_name = $(this).attr('data_theme_name');
					window.location.href="admin-post.php?action=mk_theme_editor_download_te_theme&theme_name="+theme_name;
				});
				// end here code me
				
                $( '#ctc_is_debug' ).on( 'change', function(  ) {
                    if ( $( this ).is( ':checked' ) ){
                        if ( !$( '#ctc_debug_box' ).length ){
                            $( '#ctc_debug_container' ).html( '<textarea id="ctc_debug_box"></textarea>' );
                        }
                    } else {
                        $( '#ctc_debug_box' ).remove();
                    }
                    self.save( this );
                } );
                
                $( '.ctc-live-preview' ).on( 'click', function( e ) {
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    document.location = $( this ).prop( 'href' );
                    return false;
                } );
               
                self.setup_menus();
                
                $( 'input[type=submit], input[type=button]' ).prop( 'disabled', false );
                self.scrolltop();
                self.update_form();
                
            }
            if ( self.jqueryerr.length ) {
                self.jquery_notice();
            }
        },
        // object properties
        testslug:       '',
        testname:       '',
        reload:         false,
        currquery:      'base',
        currqsid:       null,
        currdata:       {},
        currparnt:      '',
        currchild:      '',
        existing:       false,
        jqueryerr:      [], // stores jquery exceptions thrown during init
        color_regx:     '\\s+(\\#[a-f0-9]{3,6}|rgba?\\([\\d., ]+?\\)|hsla?\\([\\d%., ]+?\\)|[a-z]+)',
        border_regx:    '(\\w+)(\\s+(\\w+))?',
        grad_regx:      '(\\w+)'

    };
    $.ms_child_summary = {
        escrgx: function( str ) {
            return str.replace(/([.*+?^${}()|\[\]\/\\])/g, "\\$1");
        },
        
        trmcss: function( str ) {
        
            return 'undefined' === typeof str ? '' : str.replace( /\-css$/, '' );
        },
        
        setssl: function( url ){
            return url.replace( /^https?/, window.ms_ajax.ssl ? 'https' : 'http' );
        },      
		
        analyze_theme: function( themetype ) { 
		
			$('.ctc-analysis').html('');
            var self        = this,
                now         = Math.floor( $.now() / 1000 ),
                stylesheet  = ( 'child' === themetype ? $.ms_themeeditor.currchild : $.ms_themeeditor.currparnt ),
                testparams  = '&template=' + encodeURIComponent( $.ms_themeeditor.currparnt ) + '&stylesheet=' + encodeURIComponent( stylesheet ) + '&now=' + now,
                homeurl     = self.setssl( window.ms_ajax.homeurl ), 
                url         = homeurl + testparams;            
			
				self.analysis[ themetype ].url = url;

            
            $.get( url, function( data ) {
				
            self.parse_page( themetype, data );
                $( document ).trigger( 'analysisdone' );
            } ).fail( function( xhr, status, err ){
               
                self.analysis[ themetype ].signals.xhrgeterr = err;
                $.ajax( { 
                    url:        window.ms_ajax.ajaxurl,  
                    data:       {
                        action:     'ms_theme_summary',
                        stylesheet: stylesheet,
                        template:   $.ms_themeeditor.currparnt,
                        _wpnonce:   $( '#_wpnonce' ).val(),
                    },
                    dataType:   'json',
                    type:       'POST'
                } ).done( function( data ) {
                    if ( data.signals.httperr ) {
                        
                        self.analysis[ themetype ].signals.failure = 1;
                        self.analysis[ themetype ].signals.httperr = data.signals.httperr;
                    } else {
                        self.parse_page( themetype, data.body );
                    }
                    $( document ).trigger( 'analysisdone' );
                } ).fail( function( xhr, status, err ){
                    
                    self.analysis[ themetype ].signals.failure = 1;
                    self.analysis[ themetype ].signals.xhrajaxerr = err;
                    $( document ).trigger( 'analysisdone' );
                } );
            } );
        },
        parse_page: function( themetype, body ){
            var self        = this,
                themepath   = window.ms_ajax.theme_dir,                
                stylesheet  = ( 'child' === themetype ? $.ms_themeeditor.currchild : $.ms_themeeditor.currparnt ),
                escaped     = self.escrgx( $.ms_themeeditor.currparnt ) + ( 'child' === themetype ? '|' + self.escrgx( stylesheet ) : '' ),
                regex_link  = new RegExp( "<link( rel=[\"']stylesheet[\"'] id=['\"]([^'\"]+?)['\"])?[^>]+?" +
                    self.escrgx( themepath ) + '/(' + escaped + ')/([^"\']+\\.css)(\\?[^"\']+)?["\'][^>]+>', 'gi' ),
                regex_err   = /<br \/>\n[^\n]+?(fatal|strict|notice|warning|error)[\s\S]+?<br \/>/gi,
                themeloaded = 0, 
                testloaded  = 0, 
                msg,
                queue,
                csslink;

            // console.log( 'parsing page: ' + themetype );
            if ( 'child' === themetype ) {
                var parts = body.match( /^[\s\S]*?<head>([\s\S]*?)<\/head>/ );
                if ( parts ){
                
                }
            }
   
            if ( ( queue = body.match( /BEGIN WP QUEUE\n([\s\S]*?)\nEND WP QUEUE/ ) ) ) {
                self.analysis[ themetype ].queue = queue[ 1 ].split(/\n/);
             
            } else {
                self.analysis[ themetype ].queue = [];
                self.analysis[ themetype ].signals.thm_noqueue = 1;
            
            }
            if ( ( queue = body.match( /BEGIN CTC IRREGULAR\n([\s\S]*?)\nEND CTC IRREGULAR/ ) ) ) {
                self.analysis[ themetype ].irreg = queue[ 1 ].split(/\n/);
            } else {
                self.analysis[ themetype ].irreg = [];
            }
            if ( body.match( /CHLD_THM_CFG_IGNORE_PARENT/ ) ) {
                self.analysis[ themetype ].signals.thm_ignoreparnt = 1;
             
            }
            if ( body.match( /IS_CTC_THEME/ ) ) {
                self.analysis[ themetype ].signals.thm_is_ctc = 1;
                
            }

            if ( body.match( /NO_CTC_STYLES/ ) ) {
                self.analysis[ themetype ].signals.thm_no_styles = 1;
               
            }
            if ( body.match( /HAS_CTC_IMPORT/ ) ) {
                self.analysis[ themetype ].signals.thm_has_import = 1;
               
            }

            if ( body.match( /HAS_WP_CACHE/ ) ) {
                self.analysis[ themetype ].signals.thm_has_cache = 1;
               
            }

            if ( body.match( /HAS_WP_ROCKET/ ) ) {
                self.analysis[ themetype ].signals.thm_has_wprocket = 1;
              
            }

            if ( body.match( /HAS_AUTOPTIMIZE/ ) ) {
                self.analysis[ themetype ].signals.thm_has_autoptimize = 1;
                
            }

           
            body = body.replace( /<!\-\-[\s\S]*?\-\->/g, '' );
         
            while ( ( msg = regex_err.exec( body ) ) ) {
                var errstr = msg[ 0 ].replace( /<.*?>/g, '' );
                self.phperr[ themetype ].push( errstr );
                self.analysis[ themetype ].signals.err_php = 1;
                if ( errstr.match( /Fatal error/i ) ) {
                    self.analysis[ themetype ].signals.err_fatal = 1;
                   
                } 
                
            }
            while ( ( csslink = regex_link.exec( body ) ) ) {
                var stylesheetid    = self.trmcss( csslink[ 2 ] ),
                    stylesheettheme = csslink[ 3 ], 
                    stylesheetpath  = csslink[ 4 ],
                    linktheme       = $.ms_themeeditor.currparnt === stylesheettheme ? 'parnt' : 'child',
                    noid            = 0;
                   
                if ( '' === stylesheetid || -1 === $.inArray( stylesheetid, self.analysis[ themetype ].queue ) ) {
                    noid = 1;
                   
                } else if ( 0 === stylesheetid.indexOf( 'chld_thm_cfg' ) ) { 
                    if ( stylesheetpath.match( /^ctc\-style.*?\.css$/ ) ) {
                       
                        themeloaded = 1;
                        self.analysis[ themetype ].signals.ctc_sep_loaded = 1; 
                    } else if ( stylesheetpath.match( /^ctc\-genesis([\-\.]min)?\.css$/ ) ) {
                        
                        themeloaded = 1;
                        self.analysis[ themetype ].signals.ctc_gen_loaded = 1; 
                    } else if ( stylesheetid.match( /^chld_thm_cfg_ext/ ) ) {
                       
                        if ( stylesheetpath.match( /rtl.*?\.css$/ ) ) {
                           
                            self.analysis[ themetype ].signals.thm_rtl = 1;
                           
                        } else {
                           
                            self.analysis[ themetype ].signals.ctc_ext_loaded = 1; 
                            self.analysis[ themetype ].deps[ themeloaded ].push( [ stylesheetid, stylesheetpath ] );                            
                        }
                    } else if ( 'chld_thm_cfg_child' === stylesheetid ) {
                        self.analysis[ themetype ].signals.ctc_child_loaded = 1; 
                        self.analysis[ themetype ].deps[ themeloaded ].push( [ stylesheetid, stylesheetpath ] );
                        
                    } else if ( 'chld_thm_cfg_parent' === stylesheetid ) {
                        self.analysis[ themetype ].signals.ctc_parnt_loaded = 1; 
                        self.analysis[ themetype ].deps[ themeloaded ].push( [ stylesheetid, stylesheetpath ] );
                       
                        if ( themeloaded ){                            
                            self.analysis[ themetype ].signals.ctc_parnt_reorder = 1; 
                        }
                    }
                    continue;
                }
              
                if ( stylesheetpath.match( /^style.*?\.css$/ ) ) {
              
                    themeloaded = 1; 
                  
                    if ( 'parnt' === linktheme ) {
                        if ( noid ) {
                            self.analysis[ themetype ].signals.thm_parnt_loaded = 'thm_unregistered';
                           
                        } else {
                            self.analysis[ themetype ].signals.thm_parnt_loaded = stylesheetid;
                           
                            if ( 'child' === themetype && self.analysis[ themetype ].signals.thm_child_loaded ) {
                                self.analysis[ themetype ].signals.ctc_parnt_reorder = 1;
                                
                            }
                        }
                    } else {
                        self.analysis[ themetype ].signals.thm_child_loaded = noid ? 'thm_unregistered' : stylesheetid;
                  
                    }
                    if ( noid ) {
                        if ( testloaded ) {
                            self.analysis[ themetype ].signals.thm_past_wphead = 1;
                            self.analysis[ themetype ].deps[ themeloaded ].push( [ 'thm_past_wphead', stylesheetpath ] );
                         
                        } else {
                            self.analysis[ themetype ].signals.thm_unregistered = 1;
                            self.analysis[ themetype ].deps[ themeloaded ].push( [ 'thm_unregistered', stylesheetpath ] );
                          
                        }
                    } else {
                        self.analysis[ themetype ].deps[ themeloaded ].push( [ stylesheetid, stylesheetpath ] );
                
                    }

                } else if ( stylesheetpath.match( /rtl.*?\.css$/ ) ) {
                    self.analysis[ themetype ].signals.thm_rtl = 1;
                } else if ( 'ctc-test.css' === stylesheetpath ) { 
                  
                    testloaded = 1; 
                } else {
                    var err = null;
                
                    if ( noid ) {
                        err = 'dep_unregistered';
                    }
                    if ( testloaded ) {
                        if ( themeloaded ) {
                           
                            err = 'css_past_wphead';
                        } else {
                            err = 'dep_past_wphead';
                        }
                    }
                    // Flag stylesheet links that have no id and are loaded after main theme stylesheet. 
                    // This indicates loading outside of wp_head()
                    if ( err ) {
                        self.analysis[ themetype ].signals[ err ] = 1;
                        stylesheetid = err;
                    } else {
                        self.dependencies[ stylesheetid ] = stylesheetpath;
                    }
                    self.analysis[ themetype ].deps[ themeloaded ].push( [ stylesheetid, stylesheetpath ] );
                }
            }
            if ( ! themeloaded ){
                self.analysis[ themetype ].signals.thm_notheme = 1; // flag that no theme stylesheet has been detected
            }
            // console.log( 'analysis of ' + themetype + ':' );
            // console.log( self.analysis[ themetype ] );
        },

        
        css_notice: function() {
            //console.log( 'in css_notice' );
            var self        = this,
                themetype    = $.ms_themeeditor.existing ? 'child' : 'parnt',
                name        = $.ms_themeeditor.getname( themetype ),
                hidden      = '',
                notice      = { 
                    notices:    [],
                },
                errnotice   = {
                    style:      'notice-warning',
                    headline:   'The theme'+ name +' generated unexpected PHP debug output.',
                    errlist:    '',
                    msg:        'Oops! some error occur.'
                },
                resubmit    = 0,
                resubmitdata= {},
                anlz,
                debugtxt    = '',
                dep_inputs,
                errflags    = {};
				
            var error_txt ='%1Click Here%2 to view the theme as viewed by the Analyzer.';
            if ( self.analysis[ themetype ].signals.failure || 
                ( self.analysis[ themetype ].signals.thm_noqueue && !self.phperr[ themetype ].length ) ) {
                //if ( $( '#ctc_is_debug' ).is( ':checked' ) ) {
                    debugtxt = error_txt.replace(/%1/, '<a href="' + self.analysis[ themetype ].url + '" target="_new">' ).replace( /%2/, '</a>' );
                //}
                notice.notices.push( {
                    headline:  'The theme'+ name +'could not be analyzed because the preview did not render correctly',
                    msg: /*$.ms_themeeditor.getxt( 'anlz5' ) +*/ debugtxt,
                    style: 'notice-warning'
                } );
            } else {
                // test errors
                if ( self.phperr[ themetype ].length ) {
                    $.each( self.phperr[ themetype ], function( index, err ) {
                        if ( err.match( /Fatal error/i ) ) {
                            errflags.fatal = 1;
                        }
                        if ( err.match( /Constant \w+ already defined in .+?wp-config.php/i ) ){
                            errflags.config = 1;
                        }
                        errnotice.errlist += err + "\n"; 
                    } );
                    if ( errflags.fatal ){
                        errnotice.style    = 'error';
                        errnotice.headline =  'Please don\'t used '+ name +' theme because PHP Fatal Error has been Found';
                    }
                    if ( errflags.config ){
                        errnotice.msg = 'The WordPress configuration file has been modified incorrectly.' + errnotice.msg;
                    }
                    errnotice.msg = '<div>' +                       
                       '<div id="ms_notice_error">' + 
                        errnotice.errlist + '</div></div>' 
                        /*+ errnotice.msg*/;
						
                    notice.notices.push( errnotice );
                }
                
                if ( self.analysis[ themetype ].signals.thm_has_wprocket && self.analysis[ themetype ].signals.thm_has_autoptimize ){
                    notice.notices.push( {
                        headline: 'Both WP Rocket and Autoptimize plugins are enabled.',
                        style: 'notice-warning',
                        msg: 'The combination of these two plugins interferes with the Analysis results. Please temporarily deactivate one of them and Analyze again.'
                    } );
                } else if ( !self.analysis[ themetype ].signals.thm_noqueue ) { 
                    if ( self.analysis[ themetype ].signals.thm_past_wphead || self.analysis[ themetype ].signals.dep_past_wphead ) { 
                       
                        notice.notices.push( {
                            headline: 'This theme loads stylesheets after the wp_styles queue.',
                            style: 'notice-warning',
                            msg: 'This makes it difficult for plugins to override these styles. You can try to resolve this using the  "Repair header template" option (Step 6, "Additional handling options", below)'
                        } );
                        $( '#ctc_repairheader' ).prop( 'checked', true );
                        $( '#ctc_repairheader_container' ).show();
                    }
                    if ( self.analysis[ themetype ].signals.thm_unregistered ) {
                        if (
                            !self.analysis[ themetype ].signals.ctc_child_loaded &&
                            !self.analysis[ themetype ].signals.ctc_sep_loaded ){
                        
                            notice.notices.push( {
                                headline: 'This theme loads the parent theme\'s <code>style.css</code> file outside the wp_styles queue.',
                                style: 'notice-warning',
                                msg: 'This is common with older themes but requires the use of <code>@import</code>, which is no longer recommended. You can try to resolve this using the "Repair header template" option (see step 6, "Additional handling options", below).'
                            } );
                            $( '#ctc_repairheader_container' ).show();
                            $( '#ctc_repairheader' ).prop( 'checked', true );
                        }
                    }
                    if ( 'child' === themetype ) {
                        if ( self.analysis.child.signals.ctc_parnt_reorder ) {
                          
                            resubmit = 1;
                        }
                        if ( !self.analysis.child.signals.ctc_child_loaded &&
                            !self.analysis.child.signals.ctc_sep_loaded &&
                            !self.analysis.child.signals.thm_child_loaded ){
                            notice.notices.push( {
                                headline: 'This child theme does not load a Configurator stylesheet.',
                                style: 'notice-warning',
                                msg: 'If you want to customize styles using this plugin, please click "Configure Child Theme" again to add this to the settings.'
                            } );
                            resubmit = 1;
                        }
                      
                        if ( !self.analysis.parnt.signals.thm_no_styles &&
                            !self.analysis.child.signals.ctc_gen_loaded &&
                            !self.analysis.child.signals.thm_parnt_loaded &&
                            !self.analysis.child.signals.ctc_parnt_loaded &&
                            !self.analysis.child.signals.thm_ignoreparnt &&
                            !self.analysis.child.signals.thm_has_import ){
                            notice.notices.push( {
                                headline: 'This child theme uses the parent stylesheet but does not load the parent theme\'s <code>style.css</code> file.',
                                style: 'notice-warning',
                                msg: 'Please select a stylesheet handling method or check "Ignore parent theme stylesheets" (see step 6, below).'
                            } );
                            resubmit = 1;
                        }
                        if ( self.analysis.child.signals.thm_unregistered &&
                            self.analysis.child.signals.thm_child_loaded &&
                            'thm_unregistered' === self.analysis.child.signals.thm_child_loaded &&
                            self.analysis.child.signals.ctc_child_loaded &&
                            self.analysis.child.signals.ctc_parnt_loaded ) {
                            notice.notices.push( {
                                headline: 'This Child Theme was configured to accomodate a hard-coded stylesheet link.',
                                style: 'notice-warning',
                                msg: 'This workaround was used in earlier versions of CTC and can be eliminated by using the "Repair header template" option (see step 6, "Additional handling options", below).'
                            } );
                            $( '#ctc_repairheader_container' ).show();
                            $( '#ctc_repairheader' ).prop( 'checked', true );
                        }

                        if ( !self.analysis.child.signals.thm_is_ctc &&
                            !$( '#ctc_child_type_duplicate' ).is( ':checked' ) ) {
                            notice.notices.push( {
                                headline: 'This Child Theme has not been configured for this plugin',
                                msg: 'The Configurator makes significant modifications to the child theme, including stylesheet changes and additional php functions. Please consider using the DUPLICATE child theme option (see step 1, above) and keeping the original as a backup.',
                                style: 'notice-warning'
                            } );
                        }
                    }
                   
                    if ( self.analysis[ themetype ].signals.ctc_sep_loaded || self.analysis[ themetype ].signals.ctc_gen_loaded ){
                       
                        $( '#ctc_handling_separate' ).prop( 'checked', true );
                    }
                    if ( !notice.notices.length ) {
                        notice.notices.push( { 
                            headline: '' + ( 'child' === themetype ? 'After analyzing this child theme appears to be functioning correctly.' : 'After analyzing this parent theme is working fine so you can used as a Child theme.') + '',
                            style: 'updated',
                            msg: ''
                        } );
                    }

                    if ( 'child' === themetype && self.analysis.child.signals.thm_has_import ) {
                        notice.notices.push( {
                            headline: 'This child theme uses <code>@import</code> to load the parent theme\'s <code>style.css</code> file.',
                            msg: 'Please Select enqueue option from step number 6.',
                            style: 'notice-warning'
                        } );
                        
                        $( '#ctc_enqueue_import' ).prop( 'checked', true );
                    }
                    if ( self.analysis[ themetype ].signals.thm_ignoreparnt || self.analysis[ themetype ].signals.ctc_gen_loaded ){
                       
                        $( '#ctc_ignoreparnt' ).prop( 'checked', true );
                        if ( !$( '#ctc_enqueue_none' ).is( ':checked' ) ) {
                            $( '#ctc_enqueue_none' ).prop( 'checked', true );
                            resubmit = 1;
                            resubmitdata.ctc_enqueue = 'none';
                        }
                    } else {
                        $( '#ctc_ignoreparnt' ).prop( 'checked', false );
                    }
                    if ( !self.analysis[ themetype ].signals.ctc_sep_loaded && 
                        !self.analysis[ themetype ].signals.ctc_gen_loaded && 
                        !self.analysis[ themetype ].signals.ctc_child_loaded && 
                        !self.analysis[ themetype ].signals.thm_unregistered && 
                        !self.analysis[ themetype ].signals.thm_past_wphead && 
                        self.analysis[ themetype ].deps[ 1 ].length ) {
                        var sheets = '';
                        $.each( self.analysis[ themetype ].deps[ 1 ], function( ndx, el ) {
                            if ( el[ 1 ].match( /^style.*?\.css$/ ) ) { return; }
                            sheets += '<li>' + el[ 1 ] + "</li>\n";
                        } );
                        if ( '' !== sheets ) {
                        sheets = "<ul class='howto' style='padding-left:1em'>\n" + sheets + "</ul>\n";
                        notice.notices.push( {
                            headline: 'This theme loads additional stylesheets after the <code>style.css</code> file:',
                            msg: sheets + '',
                            style: 'updated'
                        } );
                        }
                    }
                    if ( 'child' === themetype && self.analysis[ themetype ].signals.thm_parnt_loaded ) {
                        
                            notice.notices.push( {
                                headline: 'The parent theme\'s <code>style.css</code> file is being loaded automatically.',
                                msg: 'The Configurator selected "Do not add any parent stylesheet handling" for the "Parent stylesheet handling" option (see step 6, below).',
                                style: 'updated'
                            } );
                        
                        $( '#ctc_enqueue_none' ).prop( 'checked', true );
                        resubmit = 1;
                        resubmitdata.ctc_enqueue = 'none';
                    }
               
                    if ( self.analysis.parnt.signals.thm_no_styles ) {
                        
                            notice.notices.push( {
                                headline: 'This theme does not require the parent theme\'s <code>style.css</code> file for its appearance.',
                                msg: 'The Configurator selected "Do not add any parent stylesheet handling" for the "Parent stylesheet handling" option (see step 6, below)',
                                style: 'updated'
                            } );
                       
                        $( '#ctc_enqueue_none' ).prop( 'checked', true );
                        resubmit = 1;
                        resubmitdata.ctc_enqueue = 'none';
                    }
                }
            }
            
            
			hidden = encodeURIComponent( JSON.stringify( self.analysis ) );
            $( 'input[name="ms_theme_child_analysis"]' ).val( hidden );
			resubmitdata.ms_theme_child_analysis = hidden;
            if ( self.is_success() && resubmit && !self.resubmitting ){
                self.resubmitting = 1;
                self.resubmit( resubmitdata );
                return;
            } else {
                self.resubmitting = 0;
              
                $.each( notice.notices, function( ndx, notice ){
					
                    var $out = $( '<div class="' + notice.style + ' notice is-dismissible dashicons-before" >' + 
                    '<h4>' + notice.headline + '</h4>' +
                    notice.msg +
                    '</div>' );
                    $.ms_themeeditor.bind_dismiss( $out );
                    $out.hide().appendTo( '#' + themetype + '_analysis_notice' ).slideDown();
					
                } );
                
             
                dep_inputs = '';
               
                $.each( self.dependencies, function( ndx, el ){
                    
                    if ( el ) {
                        dep_inputs += '<label><input class="ctc_checkbox ctc-themeonly" id="ctc_forcedep_' + ndx +
                        '" name="ctc_forcedep[]" type="checkbox" value="' + ndx + '" autocomplete="off" ' +
                        ( -1 !== $.inArray( ndx, window.ms_ajax.forcedep ) ? 'checked' : '' ) +
                        ' />' + ndx + "</label><br/>\n";
                    }
                });
              
                if ( dep_inputs.length ){
                    $( '#ctc_dependencies' ).html( dep_inputs );
                    $( '#ctc_dependencies_container' ).show();
                } else {
                    $( '#ctc_dependencies' ).empty();
                    $( '#ctc_dependencies_container' ).hide();                    
                }
                var ms_theme_editor_action = jQuery('#ms_theme_editor_action').val();
                
					if(ms_theme_editor_action !="reset")
					{
						$( '#input_row_stylesheet_handling_container,#input_row_parent_handling_container,#ctc_child_header_parameters,#ctc_configure_submit' ).slideDown( 'fast' );
                    if (ms_theme_editor_action=='duplicate') {
                        $( '#ctc_configure_submit .ctc-step' ).text( '8' );
                        $( '#ctc_copy_theme_mods' ).find( 'input' ).prop( 'checked', false );
                    } else {
                        $( '#ctc_configure_submit .ctc-step' ).text( '9' );
                        $( '#ctc_copy_theme_mods' ).slideDown( 'fast' );
                    }
                    if ( ms_theme_editor_action=='duplicate'||ms_theme_editor_action=='new' ) {
                        $( '#input_row_theme_slug' ).hide();
                        $( '#input_row_new_theme_slug' ).slideDown( 'fast' );
                    } else {
                        $( '#input_row_new_theme_slug' ).hide();
                        $( '#input_row_theme_slug' ).slideDown( 'fast' );
                    }
                }
            
                
            }
            
        },
        resubmit: function( data ) {
            var self = this;            
            data.action = 'ms_update';
            data._wpnonce = $( '#_wpnonce' ).val();
            
            $.ajax( { 
                url:        window.ms_ajax.ajaxurl,  
                data:       data,                
                type:       'POST'
            } ).done( function() {                
              
                self.do_analysis();
            } ).fail( function() {               
               
               
            } );  
        },
        do_analysis: function() {
            var self            = this;
            self.analysis    = {
                parnt: {
                    deps: [[],[]],
                    signals: {
                        failure: 0
                    },
                    queue: [],
                    irreg: []
                },
                child: {
                    deps: [[],[]],
                    signals: {
                        failure: 0
                    },
                    queue: [],
                    irreg: []
                }
            };
            self.phperr         = { parnt: [], child: [] };
            self.dependencies   = {};
            self.done           = 0;
           
            self.analyze_theme( 'parnt' );
            if ( $.ms_themeeditor.existing ) {
                self.analyze_theme( 'child' );
            }
            
        },
       
        init: function() {
           
            var self = this;
           
            $( document ).on( 'analysisdone', function(){
                self.done++;
                
               
                if ( self.done > $.ms_themeeditor.existing ){
                   
                    self.done = 0;
                    self.css_notice();
                }
            } );
            // run analyzer on demand
            $( '#theme_editor_main' ).on( 'click', '.ctc-analyze-theme', function() {
				$('#child_analysis_notice').html('');
				
			    var ms_theme_editor_action = $('#ms_theme_editor_action').val();
				
				if(ms_theme_editor_action == 'new')
				{
					self.do_analysis();
				}
				else if(ms_theme_editor_action == 'existing')
				{
					if($(this).attr('data_configuartion')=='Yes')
					{
						self.do_analysis();
					}
					else
					{
						alert('You don\'t have the permission to change configure existing Child Theme.');
					}
				}
				else if(ms_theme_editor_action == 'duplicate')
				{
					if($(this).attr('data_duplication')=='Yes')
					{
						self.do_analysis();
					}
					else
					{
						alert('You don\'t have the permission to duplicate the Child Theme');
					}
				}
			} );
				$( '#theme_editor_main' ).on( 'click', '.no-analyze-theme', function() {
				alert('You don\'t have the permission to create new Child Theme.');
            });
           
            if ( self.is_success() && !window.ms_ajax.pluginmode ) {
              
            }
        },
        analysis: {}, 
        done: 0, 
        resubmitting: 0, 
        dependencies: {}, 
        is_success: function(){
            return $( '.ms-success-response' ).length;
        }
    };
   
   
	$.ms_themeeditor.init();
	$.ms_child_summary.init();
   
} ( jQuery ) );