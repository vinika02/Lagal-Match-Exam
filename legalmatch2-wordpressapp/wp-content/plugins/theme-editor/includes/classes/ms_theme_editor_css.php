<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class ms_theme_editor_css {    
    // data dictionaries
    var $dict_query;        // @media queries and 'base'
    var $dict_sel;          // selectors  
    var $dict_qs;           // query/selector lookup
    var $dict_rule;         // css rules
    var $dict_val;          // css values
    var $dict_seq;          // child load order (priority)
    var $dict_token;
    // hierarchies
    var $val_ndx;           // selector => rule => value hierarchy
    // key counters
    var $qskey;             // counter for dict_qs
    var $querykey;          // counter for dict_query
    var $selkey;            // counter for dict_sel
    var $rulekey;           // counter for dict_rule
    var $valkey;            // counter for dict_val
    var $tokenkey;          // counter for dict_token

    // from parent/child form
    var $child;             // child theme slug
    var $parnt;             // parent theme slug
    var $configtype;        // legacy plugin slug
    var $handling;          // child stylesheet handling option
    var $enqueue;           // whether or not to load parent theme
    var $ignoreparnt;       // no not parse or enqueue parent
    var $qpriority;
    var $hasstyles;
    var $parntloaded;
    var $childloaded;
    var $parnt_deps;        // 
    var $child_deps;        //
    var $forcedep;
    var $addl_css;
    var $cssunreg;
    var $csswphead;
    var $cssnotheme;
    var $reorder;

    // header settings
    var $child_name;        // child theme name
    var $child_author;      // child theme author
    var $child_authoruri;   // child theme author website
    var $child_themeuri;    // child theme website
    var $child_descr;       // child theme description
    var $child_tags;        // child theme tags
    var $child_version;     // stylesheet version
    
    // miscellaneous properties
    var $fsize;             // used to check if styles changed since last update
    var $converted;         // @imports coverted to <link>?
    var $version;           // version of last saved data
    var $templates;         // cache of parent template files
    var $imports;           // @import rules
    var $recent;            // history of edited styles
    var $max_sel;
    var $memory;
    var $styles;            // temporary update cache
    var $temparray;
    var $instances = array();
    var $vendorrule = array(
        'box\-sizing',
        'font\-smoothing',
        'border(\-(top|right|bottom|left))*\-radius',
        'box\-shadow',
        'transition',
        'transition\-property',
        'transition\-duration',
        'transition\-timing\-function',
        'transition\-delay',
        'hyphens',
        'transform',
        'columns',
        'column\-gap',
        'column\-count',
    );
    var $configvars = array(
        'addl_css',
        'forcedep',
        'cssunreg',
        'csswphead',
        'cssnotheme',
        'reorder',
        'parnt_deps',
        'child_deps',
        'hasstyles',
        'parntloaded',
        'childloaded',
        'ignoreparnt',
        'qpriority',
        'enqueue',
        'handling',
        'templates',
        'max_sel',
        'imports',
        'child_version',
        'child_author',
        'child_name',
        'child_themeuri',
        'child_authoruri',
        'child_descr',
        'child_tags',
        'parnt',
        'child',
        'configtype', // legacy support
        'valkey',
        'rulekey',
        'qskey',
        'selkey',
        'querykey',
        'tokenkey',
        'recent',
        'converted',
        'fsize',
        'version',
    );
    var $dicts = array(
        'dict_qs'               => 0,
        'dict_sel'              => 0,
        'dict_query'            => 0,
        'dict_rule'             => 0,
        'dict_val'              => 0,
        'dict_seq'              => 0,
        'dict_token'            => 0,
        'val_ndx'               => 0,
    );
    var $packer; // packer object
    
    function __construct() {
        $this->mem_chk();
        // scalars
        $this->querykey         = 0;
        $this->selkey           = 0;
        $this->qskey            = 0;
        $this->rulekey          = 0;
        $this->valkey           = 0;
        $this->max_sel          = 0;

        $this->child_name       = '';
        $this->child_author     = 'Child Theme Configurator';
        $this->child_themeuri   = '';
        $this->child_authoruri  = '';
        $this->child_descr      = '';
        $this->child_tags       = '';
        $this->child_version    = '1.0';
        
        $this->configtype       = 'theme'; // legacy support
        $this->child            = '';
        $this->parnt            = '';
        $this->ignoreparnt      = 0;
        $this->qpriority        = 10;
        $this->version          = '2.3.0.4';
        
        // do not set enqueue, not being set is used to flag old versions

        // multi-dim arrays
        $this->templates        = array();
        $this->imports          = array( 'child' => array(), 'parnt' => array() );

        $this->recent           = array();
        $this->packer           = new ms_theme_editor_packer();
    }
    
    // helper function to globalize ctc object
    function ctc() {
        return ms_theme_editor_controller::ctc();
    }
    function mem_chk() {
        $currmemory = $this->memory;
		if ( function_exists( 'memory_get_peak_usage' ) ) {
			$usage      = memory_get_peak_usage();
		} else {
			$usage      = memory_get_usage();
		}
        $this->memory = $usage;
        $usage -= $currmemory;
        return number_format( $this->memory / ( 1024 * 1024 ), 2 ) . ' MB diff ' . number_format( $usage / 1024, 2 ) . ' kB';
    }
    
    // loads current ctc config data 
    function load_config( $key = NULL ) {
        //list(, $callerarr) = debug_backtrace(false);
        //$caller = ( isset( $callerarr[ 'class' ] ) ? $callerarr[ 'class' ] . '::' : '' ) . $callerarr[ 'function' ];
        if ( $key && isset( $this->dicts[ $key ] ) ):
            $option = MS_CHILD_THEME_EDITOR . apply_filters( 'ms_child_theme_editor', '' );
            if ( !$this->dicts[ $key ] ): // dict not loaded yet
                //$this->ctc()->debug( 'memory before load: ' . $this->mem_chk(), __FUNCTION__, __CLASS__ );
                //$this->ctc()->debug( $option . '_' . $key . ' -- called from ' . $caller, __FUNCTION__, __CLASS__ );
                // 
                if ( !$this->ctc()->is_new && ( $config = get_site_option( $option . '_' . $key ) ) ): 
                    $this->{ $key } = $config; // if not new child theme and option exists, load it
                else:
                    $this->{ $key } = array(); // otherwise load empty array 
                endif;
            
                $this->dicts[ $key ] = 1; // flag as loaded
              
            else:
           
            endif;
        endif;
        // initial setup
        if ( empty( $key ) && !isset( $this->enqueue ) ):
            $option = MS_CHILD_THEME_EDITOR . apply_filters( 'ms_child_theme_editor', '' );
           
            if ( ( $configarray = get_site_option( $option . '_configvars' ) ) && count( $configarray ) ):
                foreach ( $this->configvars as $configkey ):
                    if ( isset( $configarray[ $configkey ] ) )
                        $this->{$configkey} = $configarray[ $configkey ];
                endforeach;
                // convert dictionaries from < 2.1.0
                if ( empty( $configarray[ 'version' ] ) || version_compare( $configarray[ 'version' ], '2.1.0', '<' ) ):
                    $this->convert_dict_arrays();
                else:
                    $this->ctc()->debug( 'dict format up to date', __FUNCTION__, __CLASS__ );
                endif;
            else:
                return FALSE;
            endif;
        endif;
        
    }
    
    // writes ctc config data to options api
    function save_config( $override = NULL ) {
        // set latest stylesheet size
        $this->get_stylesheet_path();
        global $wpdb;
        if ( isset( $override ) ) $option = $override;
        else $option = apply_filters( 'ms_child_theme_editor', '' );
        $option = MS_CHILD_THEME_EDITOR . $option;
        $configarray = array();
        foreach ( $this->configvars as $configkey )
            $configarray[ $configkey ] = empty( $this->{$configkey} ) ? NULL : $this->{ $configkey };
            //$this->ctc()->debug(  'saving option: ' . $option . '_configvars', __FUNCTION__, __CLASS__ );
        if ( is_multisite() ):
            update_site_option( $option . '_configvars', $configarray ); 
        else:
            // do not autoload ( passing false above only works if value changes
            update_option( $option . '_configvars', $configarray, FALSE ); 
        endif;
        
        foreach ( $this->dicts as $configkey => $loaded ):
            if ( $loaded ): // only save if dict is loaded
                //$this->ctc()->debug(  'saving option: ' . $option . '_' . $configkey, __FUNCTION__, __CLASS__ );
                if ( is_multisite() ):
                    update_site_option( $option . '_' . $configkey, $this->{$configkey} ); 
                else:
                    // do not autoload ( passing false for update_site_option only works if value changes )
                    update_option( $option . '_' . $configkey, $this->{$configkey}, FALSE );
                endif;
            endif;
        endforeach;
        
    }
        
    /**
     * determine effective stylesheet path and measure size
     */
    function get_stylesheet_path() {
        $stylesheet = apply_filters( 'chld_thm_cfg_target', $this->get_child_target( $this->ctc()->get_child_stylesheet() ), $this );
        $this->fsize = filesize( $stylesheet );
        $this->ctc()->debug( 'updated file size: ' . $this->fsize, __FUNCTION__, __CLASS__ );
        return $stylesheet;
    }
    /**
     * get_prop
     * Getter interface (data sliced different ways depending on objname )
     */
    function get_prop( $property, $params = NULL ) {
        switch ( $property ):
            case 'fsize':
                return empty( $this->fsize ) ? FALSE : $this->fsize;
            case 'converted':
                return !empty( $this->converted );
            case 'max_sel':
                return empty( $this->max_sel ) ? FALSE : $this->max_sel;
            case 'imports':
                
                return $this->obj_to_utf8( !empty( $this->imports[ 'child' ] ) && is_array( $this->imports[ 'child' ] ) ? 
                    ( current( $this->imports[ 'child' ] ) == 1 ? 
                        array_keys( $this->imports[ 'child' ] ) : 
                            array_keys( array_flip( $this->imports[ 'child' ] ) ) ) : 
                                array() );
								
								
            case 'queries':
                return $this->obj_to_utf8( $this->denorm_dict_qs() );
            case 'selectors':
                return empty( $params[ 'key' ] ) ? 
                    array() : $this->obj_to_utf8( $this->denorm_dict_qs( $params[ 'key' ] ) );
            case 'rule_val':
                return empty( $params[ 'key' ] ) ? array() : $this->denorm_rule_val( $params[ 'key' ] );
            case 'val_qry':
                if ( isset( $params[ 'rule' ] ) ):
                    return empty( $params[ 'key' ] ) ? 
                        array() : $this->denorm_val_query( $params[ 'key' ], $params[ 'rule' ] );
                endif;
            case 'qsid':
                return empty( $params[ 'key' ] ) ? 
                    array() : $this->obj_to_utf8( $this->denorm_sel_val( $params[ 'key' ] ) );
            case 'rules':
                $this->load_config( 'dict_rule' );
                asort( $this->dict_rule );
                //ksort( $this->dict_rule );
                //return $this->obj_to_utf8( $this->dict_rule );
                /** lookup ** -- need to flip array??? */
                return $this->obj_to_utf8( array_flip( $this->dict_rule ) );
            case 'child':
                return $this->child;
            case 'parnt':
                return $this->parnt;
            case 'configtype': // legacy plugin extension support
                return $this->configtype;
            case 'enqueue':
                return empty( $this->enqueue ) ? FALSE : $this->enqueue;
            case 'addl_css':
                return empty( $this->addl_css ) ? array() : $this->addl_css;
            case 'parnt_imp':
                return empty( $this->parnt_imp ) ? array() : $this->parnt_imp;
            case 'forcedep': // v2.1.3
                return empty( $this->forcedep ) ? array() : array_keys( $this->forcedep );
            case 'parnt_deps':
                return empty( $this->parnt_deps ) ? array() : $this->quotify_dependencies( 'parnt_deps' );
            case 'child_deps':
                return empty( $this->child_deps ) ? array() :  $this->quotify_dependencies( 'child_deps' );
            case 'templates':
                return empty( $this->templates )  ? FALSE : $this->templates;
            case 'ignoreparnt':
                return empty( $this->ignoreparnt ) ? 0 : 1;
            case 'qpriority':
                return empty( $this->qpriority ) ? 10 : $this->qpriority;
            case 'parntloaded':
                return empty( $this->parntloaded ) ? FALSE : $this->parntloaded;
            case 'childloaded':
                return empty( $this->childloaded ) ? FALSE : $this->childloaded;
            case 'hasstyles':
                return empty( $this->hasstyles ) ? 0 : 1;
            case 'cssunreg':
                return empty( $this->cssunreg ) ? 0 : 1;
            case 'csswphead':
                return empty( $this->csswphead ) ? 0 : 1;
            case 'cssnotheme':
                return empty( $this->cssnotheme ) ? 0 : 1;
            case 'reorder':
                return empty( $this->reorder ) ? 0 : 1;
            case 'handling':
                return empty( $this->handling ) ? 'primary' : $this->handling;
            case 'child_name':
                return stripslashes( $this->child_name );
            case 'author':
                return stripslashes( $this->child_author );
            case 'themeuri':
                return isset( $this->child_themeuri ) ? $this->child_themeuri : FALSE;
            case 'authoruri':
                return isset( $this->child_authoruri ) ? $this->child_authoruri : FALSE;
            case 'descr':
                return isset( $this->child_descr ) ? stripslashes( $this->child_descr ) : FALSE;
            case 'tags':
                return isset( $this->child_tags ) ? stripslashes( $this->child_tags ) : FALSE;
            case 'version':
                return $this->child_version;
            case 'preview':
                $this->styles = '';
                if ( empty( $params[ 'key' ] ) || 'child' == $params[ 'key' ] ):
                    $this->read_stylesheet( 'child', $this->ctc()->get_child_stylesheet() );
                else:
                    if ( isset( $this->addl_css ) ):
                        foreach ( $this->addl_css as $file ):
                            $this->styles .= '/*** BEGIN ' . $file . ' ***/' . LF;
                            $this->read_stylesheet( 'parnt', $file );
                            $this->styles .= '/*** END ' . $file . ' ***/' . LF;
                        endforeach;
                    endif;
                    if ( $this->get_prop( 'hasstyles' ) && !$this->get_prop( 'ignoreparnt' ) ):
                        $this->styles .= '/*** BEGIN Parent style.css ***/' . LF;
                        $this->read_stylesheet( 'parnt', 'style.css' );
                        $this->styles .= '/*** END Parent style.css ***/' . LF;
                    endif;
                    if ( 'separate' == $this->get_prop( 'handling' ) ):
                        $this->styles .= '/*** BEGIN Child style.css ***/' . LF;
                        $this->read_stylesheet( 'child', 'style.css' );
                        $this->styles .= '/*** END Child style.css ***/' . LF;
                    endif;
                endif;
                $this->normalize_css();
                return $this->styles;
                break;
            default:
                return $this->obj_to_utf8( apply_filters( 'chld_thm_get_prop', NULL, $property, $params ) );
        endswitch;
        return FALSE;
    }

    /**
     * set_prop
     * Setter interface (scalar values only)
     */
    function set_prop( $property, $value ) {
        if ( is_null( $this->{ $property } ) || is_scalar( $this->{ $property } ) )
            $this->{ $property } = $value;
        else return FALSE;
    }
    
    // formats css string for accurate parsing
    function normalize_css() {
        if ( preg_match( "/(\}[\w\#\.]|; *\})/", $this->styles ) ):                     // prettify compressed CSS
            $this->styles = preg_replace( "/\*\/\s*/s", "*/\n",     $this->styles );    // end comment
            $this->styles = preg_replace( "/\{\s*/s", " {\n    ",   $this->styles );    // open brace
            $this->styles = preg_replace( "/;\s*/s", ";\n    ",     $this->styles );    // semicolon
            $this->styles = preg_replace( "/\s*\}\s*/s", "\n}\n",   $this->styles );    // close brace
        endif;
    }
    
    function quotify_dependencies( $prop ) {
        $arr = array();
        foreach ( array_diff( $this->{ $prop }, $this->get_prop( 'forcedep' ) ) as $el )
            $arr[] = "'" . str_replace("'", "\'", $el ) . "'";
        return $arr;
    }
    
    // creates header comments for stylesheet
    function get_css_header() {
        return array(
            'Theme Name'    => $this->get_prop( 'child_name' ),
            'Theme URI'     => ( ( $attr = $this->get_prop( 'themeuri' ) ) ? $attr : '' ),
            'Template'      => $this->get_prop( 'parnt' ),
            'Author'        => $this->get_prop( 'author' ),
            'Author URI'    => ( ( $attr = $this->get_prop( 'authoruri' ) ) ? $attr : '' ),
            'Description'   => ( ( $attr = $this->get_prop( 'descr' ) ) ? $attr : '' ),
            'Tags'          => ( ( $attr = $this->get_prop( 'tags' ) ) ? $attr : '' ),
            'Version'       => $this->get_prop( 'version' ) . '.' . time(),
            'Updated'       => current_time( 'mysql' ),
        );
    }

    function get_css_header_comment( $handling = 'primary' ) {
        if ( 'separate' == $handling ):
            $contents = "/*" . LF
                . 'CTC Separate Stylesheet' . LF
                . 'Updated: ' . current_time( 'mysql' ) . LF
                . '*/' . LF;
        else:
            $contents = "/*" . LF;
            foreach ( $this->get_css_header() as $param => $value ):
                if ( $value ):
                    $contents .= $param . ': ' . $value . LF;
                endif;
            endforeach;
            $contents .= LF . "*/" . LF . $this->get_css_imports();
                    
        endif;
        return $contents;
    }
    
    function get_css_imports() {
        $newheader = '';
        if ( 'import' == $this->get_prop( 'enqueue' ) ):
            $this->ctc()->debug( 'using import ', __FUNCTION__, __CLASS__ );
            if ( ! $this->get_prop( 'ignoreparnt' ) )
                $newheader .= "@import url('../" . $this->get_prop( 'parnt' ) . "/style.css');" . LF;
        endif;
        return $newheader;
    }
    
    // formats file path for child theme file
    function get_child_target( $file = '', $theme = NULL ) {
        return trailingslashit( get_theme_root() ) . trailingslashit( $theme ? $theme : $this->get_prop( 'child' ) ) . $file;
    }
    
    // formats file path for parent theme file
    function get_parent_source( $file = 'style.css', $theme = NULL ) {
        return trailingslashit( get_theme_root() ) . trailingslashit( $theme ? $theme : $this->get_prop( 'parnt' ) ) . $file;
    }
    
    /**
     * get_dict_id
     * lookup function retrieves normalized id from string input
     * automatically adds to dictionary if it does not exist
     * incrementing key value for dictionary
     */
    function get_dict_id( $dict, $value ) {
        if ( FALSE === ( $id = $this->lookup_dict_value( $dict, $value ) ) ):
            // add value to dictionary
            $id = ++$this->{ $dict . 'key' };
            $this->set_dict_value( $dict, $value, $id );
        endif;
        return $id;
    }
    
    function lookup_dict_value( $dict, $value ){
        //$this->ctc()->debug( 'dict: ' . $dict . ' value: %' . $value . '%', __FUNCTION__, __CLASS__ );
        $property = 'dict_' . $dict;
        if ( $id = array_search( (string) $value, $this->{ $property } ) )
            return $id;
        return FALSE;
    }
    
    function get_dict_value( $dict, $id ) {
        $property = 'dict_' . $dict;
        return ( isset( $this->{ $property }[ $id ] ) )
            ? $this->{ $property }[ $id ]
            : FALSE;
    }
    
    function set_dict_value( $dict, $value, $id ) {
        $property = 'dict_' . $dict;
        $this->{ $property }[ $id ] = ( string ) $value;
    }

    /**
     * get_qsid
     * query/selector id is the combination of two dictionary values
     * also throttles parsing if memory limit is reached
     */
    function get_qsid( $query, $sel ) {
        $qs = $this->get_dict_id( 'query', $query ) . ':' . $this->get_dict_id( 'sel', $sel );
        return $this->get_dict_id( 'qs', $qs );
    }
    
    function unpack_val_ndx( $qsid ){
        if ( isset( $this->val_ndx[ $qsid ] ) ):
            try {
                $this->packer->reset( $this->packer->decode( $this->val_ndx[ $qsid ] ) ); 
                return $this->packer->unpack();
            } catch ( Exception $e ){
                $this->ctc()->debug( 'Unpack failed -- ' . $e->getMessage(), __FUNCTION__, __CLASS__ );
                return FALSE;
            }
        endif;
        return FALSE;
    }
    
    function pack_val_ndx( $qsid, $valarr ){
        try {
            $this->val_ndx[ $qsid ] = $this->packer->encode( $this->packer->pack( $valarr ) );
        } catch ( Exception $e ){
            $this->ctc()->debug( 'Pack failed -- ' . $e->getMessage(), __FUNCTION__, __CLASS__ );
        }
    }
    
    /**
     * update_arrays
     * accepts CSS properties as raw strings and normilizes into 
     * CTC object arrays, creating update cache in the process.
     * ( Update cache is returned to UI via AJAX to refresh page )
     * This has been refactored in v1.7.5 to accommodate multiple values per property.
     * @param   $template   p or c
     * @param   $query      media query 
     * @param   $sel        selector
     * @param   $rule       property (rule)
     * @param   $value      individual value ( property has array of values )
     * @param   $important  important flag for value
     * @param   $rulevalid  unique id of value for property
     * @param   $reset      clear current values to prevent multiple values from being generated from Raw CSS post input data
     * @return  $qsid       query/selector id for this entry
     */
    function update_arrays( 
        $template, 
        $query, 
        $sel, 
        $rule       = NULL, 
        $value      = NULL, 
        $important  = 0, 
        $rulevalid  = NULL, 
        $reset      = FALSE 
        ) {
        // if ( $this->max_sel ) return; // Future use
        if ( FALSE === strpos( $query, '@' ) )
            $query = 'base';
        // normalize selector styling
        $sel = implode( ', ', preg_split( '#\s*,\s*#s', trim( $sel ) ) );
        $qsid = $this->get_qsid( $query, $this->tokenize( $sel ) );
        // set data and value
        if ( $rule ):
            // get ids and quit if max is reached ( get_qsid handles )
            $ruleid = $this->get_dict_id( 'rule', $rule );
            $valid  = $this->get_dict_id( 'val', $value );
            /**
             * v2.1.0
             * pack/unpack val_ndx 
             */
            // create empty array IF value array does not exist
            if ( FALSE === ( $valarr = $this->unpack_val_ndx( $qsid ) ) )
                $valarr = array(
                    $ruleid => array(
                        $template => array(),
                    ),
                );
            // create empty array IF rule array does not exist
            if ( !isset( $valarr[ $ruleid ] ) )
                $valarr[ $ruleid ] = array(
                    $template => array(),
                );
            // create empty rule array if template is child and reset is TRUE 
            // or IF template array does not exist
            if ( ( $reset && 'child' == $template ) || !isset( $valarr[ $ruleid ][ $template ] ) )
                $valarr[ $ruleid ][ $template ] = array();

            // rulevalid passed            
            //$this->ctc()->debug( 'rule: ' . $rule . ' ' . $ruleid . ' value: ' . ( '' == $value? 'NULL' : '%' . $value . '%' ) . ' ' . ( FALSE == $valid ? 'FALSE' : $valid ) . ' valarr: ' . print_r( $valarr, TRUE ), __FUNCTION__, __CLASS__ );
            if ( isset( $rulevalid ) ):
                $this->unset_rule_value( $valarr[ $ruleid ][ $template ], $rulevalid );
                // value empty?
                if ( '' === $value ):
                // value exist?
                elseif ( $id = $this->rule_value_exists( $valarr[ $ruleid ][ $template ], $valid ) ):
                    $this->unset_rule_value( $valarr[ $ruleid ][ $template ], $id );
                    $this->update_rule_value( $valarr[ $ruleid ][ $template ], $rulevalid, $valid, $important );
                // update new value
                else:
                    $this->update_rule_value( $valarr[ $ruleid ][ $template ], $rulevalid, $valid, $important );
                endif;
            // rulevalid not passed
            else:
                // value exist?
                if ( $id = $this->rule_value_exists( $valarr[ $ruleid ][ $template ], $valid ) ):
                    $this->unset_rule_value( $valarr[ $ruleid ][ $template ], $id );
                    $this->update_rule_value( $valarr[ $ruleid ][ $template ], $id, $valid, $important );
                // get new id and update new value
                else:
                    $id = $this->get_rule_value_id( $valarr[ $ruleid ][ $template ] );
                    $this->update_rule_value( $valarr[ $ruleid ][ $template ], $id, $valid, $important );
                endif;
            endif;
        
            // moved call to prune_if_empty to parse_post_data v2.2.5
        
            $this->pack_val_ndx( $qsid, $valarr );
            // return query selector id   
            return $qsid;
        endif;
    }
    
    /**
     * rule_value_exists
     * Determine if a value already exists for a property
     * and return its id
     */
    function rule_value_exists( &$arr, $valid ) {
        foreach ( $arr as $valarr ):
            if ( isset( $valarr[ 0 ] ) && isset( $valarr[ 2 ] ) && $valid == $valarr[ 0 ] ):
                return $valarr[ 2 ];
            endif;
        endforeach;
        return FALSE;
    }
    
    /**
     * get_rule_value_id
     * Generate a new rulevalid by iterating existing ids
     * and returning the next in sequence
     */
    function get_rule_value_id( &$arr ) {
        $newid = 1;
        foreach ( $arr as $valarr )
            if ( isset( $valarr[ 2 ] ) && $valarr[ 2 ] >= $newid ) $newid = $valarr[ 2 ] + 1;
        return $newid;
    }
    
    /**
     * update_rule_value
     * Generate a new value subarray
     */
    function update_rule_value( &$arr, $id, $valid, $important ) {
        $arr[] = array(
            $valid,
            $important,
            $id,
        );
    }

    /** 
     * unset_rule_value
     * Delete (splice) old value subarray from values 
     */
    function unset_rule_value( &$arr, $id ) {
        $index = 0;
        foreach ( $arr as $valarr ):
            if ( $id == $valarr[ 2 ] ):
                array_splice( $arr, $index, 1 );
                break;
            endif;
            ++$index;
        endforeach;
    }
    
    /** 
     * prune_if_empty
     * Automatically cleans up hierarchies when no values exist 
     * in either parent or child for a given selector.
     */
    function prune_if_empty( $qsid ) {
        $empty = $this->get_dict_id( 'val', '' );
        if ( FALSE == ( $valarr = $this->unpack_val_ndx( $qsid ) ) ) return FALSE;
        foreach ( $valarr as $ruleid => $arr ):
            foreach ( array( 'c', 'p' ) as $template ):
                if ( isset( $arr[ $template ] ) ):
                    // v1.7.5: don't prune until converted to multi value format
                    if ( !is_array( $arr[ $template ] ) ) return FALSE; 
                    // otherwise check each value, if not empty return false
                    foreach ( $arr[ $template ] as $valarr ) 
                        if ( $empty != $valarr[ 0 ] ) return FALSE;
                endif;
            endforeach;
        endforeach;
        // no values, prune from sel index, val index and qs dict data ( keep other dictionary records )
        unset( $this->val_ndx[ $qsid ] );
        unset( $this->dict_qs[ $qsid ] );
        unset( $this->dict_seq[ $qsid ] );
        return TRUE;
    }
    
    
    function recurse_directory( $rootdir, $ext = 'css', $all = FALSE ) {
        // make sure we are only recursing theme and plugin files
        if ( !$this->is_file_ok( $rootdir, 'search' ) ) 
            return array(); 
        $files = array();
        $dirs = array( $rootdir );
        $loops = 0;
        if ( 'img' == $ext )
            $ext = '(' . implode( '|', array_keys( $this->ctc()->imgmimes ) ) . ')';
        while( count( $dirs ) && $loops < 2000 ): // failsafe valve
            $loops++;
            $dir = array_shift( $dirs );
            if ( $handle = opendir( $dir ) ):
                while ( FALSE !== ( $file = readdir( $handle ) ) ):
                    if ( preg_match( "/^\./", $file ) ) continue;
                    $filepath  = trailingslashit( $dir ) . $file;
                    if ( is_dir( $filepath ) ):
                        array_unshift( $dirs, $filepath );
                        if ( $all ):
                            $files[] = $filepath; 
                        endif;
                    elseif ( is_file( $filepath ) && ( $all || preg_match( "/\.".$ext."$/i", $filepath ) ) ):
                        $files[] = $filepath;
                    endif;
                endwhile;
                closedir( $handle );
            endif;
        endwhile;
        return $files;
    }
    
    /**
     * parse_post_data
     * Parse user form input into separate properties and pass to update_arrays
     * FIXME - this function has grown too monolithic - refactor and componentize
     */
    function parse_post_data() {
        $this->load_config( 'dict_query' );
        $this->load_config( 'dict_sel' );
        $this->load_config( 'dict_token' );
        $this->load_config( 'dict_rule' );
        $this->load_config( 'dict_val' );
        $this->load_config( 'val_ndx' );
        $this->load_config( 'dict_seq' );
        $this->load_config( 'dict_qs' );
        $this->cache_updates = TRUE;
        // process RAW CSS input
        if ( isset( $_POST[ 'ctc_new_selectors' ] ) ):
            $this->styles = $this->parse_css_input( LF . $_POST[ 'ctc_new_selectors' ] );
            $this->parse_css( 'child', 
                isset( $_POST[ 'ctc_sel_ovrd_query' ] ) ? trim( $_POST[ 'ctc_sel_ovrd_query' ] ) : NULL, 
                FALSE, 
                '', 
                TRUE
            );
        // process WEB FONTS & CSS inputs
        elseif ( isset( $_POST[ 'ms_child_imports' ] ) ):
            $this->imports[ 'child' ] = array();
            $this->styles = $this->parse_css_input( $_POST[ 'ms_child_imports' ] );
            $this->parse_css( 'child' );
        // process ANALYZER SIGNAL inputs
        elseif (isset( $_POST[ 'ms_theme_child_analysis' ] )):
            
            if ( $this->ctc()->cache_updates ):
                $this->ctc()->updates[] = array(
                    'obj'  => 'analysis',
                    'data' => array(),
                );
            endif;
            
            $this->ctc()->evaluate_signals( $this->get_prop( 'ignoreparnt' ) );
        // process CONFIGURE inputs
        elseif ( isset( $_POST[ 'ctc_configtype' ] ) ):
            ob_start();
            do_action( 'chld_thm_cfg_get_stylesheets' );
            $this->ctc()->updates[] = array(
                'obj'   => 'stylesheets',
                'key'   => '',
                'data'  => ob_get_contents(),
            );
            ob_end_clean();
            ob_start();
            do_action( 'chld_thm_cfg_get_backups' );
            $this->ctc()->updates[] = array(
                'obj'   => 'backups',
                'key'   => '',
                'data'  => ob_get_contents(),
            );
            ob_end_clean();
            return;
        // process SAVE inputs
        else:
            // New query added v2.3.0
            $newquery = isset( $_POST[ 'ctc_rewrite_query' ] ) ? 
                $this->sanitize( $this->parse_css_input( $_POST[ 'ctc_rewrite_query' ] ) ) : NULL;
            $newselector = isset( $_POST[ 'ctc_rewrite_selector' ] ) ? 
                $this->sanitize( $this->parse_css_input( $_POST[ 'ctc_rewrite_selector' ] ) ) : NULL;
            $newqsid = NULL;
        
            // set the custom sequence value
            foreach ( preg_grep( '#^ctc_ovrd_child_seq_#', array_keys( $_POST ) ) as $post_key ):
                if ( preg_match( '#^ctc_ovrd_child_seq_(\d+)$#', $post_key, $matches ) ):
                    $qsid = $matches[ 1 ];
                    $seq = intval( $_POST[ $post_key ] );
                    $this->ctc()->debug( 'set seq( ' . $qsid . ' ): custom: ' . $seq, __FUNCTION__, __CLASS__ );
                    if ( $seq != $qsid ):
                        $this->set_dict_value( 'seq', $seq, $qsid );
                    else:
                        unset( $this->dict_seq[ $seq ] );
                    endif;
                endif;
            endforeach;
        
            // iterate each property input
            $parts = array();
            foreach ( preg_grep( '#^ctc_(ovrd|\d+)_child#', array_keys( $_POST ) ) as $post_key ):
        
                // parse input key into individual components if it matches specific format, skip otherwise
                if ( preg_match( '#^ctc_(ovrd|\d+)_child_([\w\-]+?)_(\d+?)_(\d+?)(_(.+))?$#', $post_key, $matches ) ):
                    $valid      = $matches[ 1 ]; // this is used for inputs from property value tab
                    $rule       = $matches[ 2 ]; // property name 
                    if ( NULL == $rule || FALSE === $this->lookup_dict_value( 'rule', $rule ) )
                        continue;
                    $qsid       = $matches[ 3 ]; // query/selector id 
                    $rulevalid  = $matches[ 4 ]; // id to identify multiple values of same property
                    // normalize input value
                    $value      = $this->normalize_color( $this->sanitize( $this->parse_css_input( $_POST[ $post_key ] ) ) );
                    // set important flag
                    $important  = $this->is_important( $value ); // strip and set if !important passed in input
                    // set important if checkbox input is set 
                    if ( !empty( $_POST[ 'ctc_' . $valid . '_child_' . $rule . '_i_' . $qsid . '_' . $rulevalid ] ) ) $important = 1;
        
                    // get current values from query/selector id if it exists, skip this property otherwise
                    $selarr = $this->denorm_query_sel( $qsid );
                    if ( empty( $selarr ) ) continue;
        
                    // if there is a "rule-part" (e.g., border or gradient properties ), store in parts array and process separately.
                    if ( !empty( $matches[ 6 ] ) ):
                        $parts[ $qsid ][ $rule ][ 'values' ][ $rulevalid ][ $matches[ 6 ] ] = $value;
                        $parts[ $qsid ][ $rule ][ 'values' ][ $rulevalid ][ 'important' ]   = $important;
                        $parts[ $qsid ][ $rule ][ 'query' ]                                 = $selarr[ 'query' ];
                        $parts[ $qsid ][ $rule ][ 'selector' ]                              = $selarr[ 'selector' ];
                    // otherwise process this property
                    else:
                        $newqsid = $this->update_property(
                            $newquery,
                            $newselector,
                            $selarr[ 'query' ], 
                            $selarr[ 'selector' ], 
                            $rule, 
                            $value, 
                            $important, 
                            $rulevalid
                        );
                    endif;
                endif;
            endforeach;
            /** 
             * Inputs for border and background-image are broken into multiple "rule parts"
             * With the addition of multiple property values in v1.7.5, the parts loop 
             * has been modified to segment the parts into rulevalids under a new 'values' array. 
             * The important flag has also been moved into the parts array.
             */
            foreach ( $parts as $qsid => $rules ):
                foreach ( $rules as $rule => $rule_arr ):
                    // new 'values' array to segment parts into rulevalids
                    foreach ( $rule_arr[ 'values' ] as $rulevalid => $rule_part ):
                        if ( 'background' == $rule ):
                            $value = $rule_part[ 'background_url' ];
                        elseif ( 'background-image' == $rule ):
                            if ( empty( $rule_part[ 'background_url' ] ) ):
                                if ( empty( $rule_part[ 'background_color2' ] ) ):
                                    $value = '';
                                else:
                                    if ( empty( $rule_part[ 'background_origin' ] ) )
                                        $rule_part[ 'background_origin' ] = 'top';
                                    if ( empty( $rule_part[ 'background_color1' ] ) )
                                        $rule_part[ 'background_color1' ] = $rule_part[ 'background_color2' ];
                                    $value = implode( ':', array(
                                        $rule_part[ 'background_origin' ], 
                                        $rule_part[ 'background_color1' ], '0%', 
                                        $rule_part[ 'background_color2' ], '100%'
                                    ) );
                                endif;
                            else:
                                $value = $rule_part[ 'background_url' ];
                            endif;
                        elseif ( preg_match( '#^border(\-(top|right|bottom|left))?$#', $rule ) ):
                            if ( empty( $rule_part[ 'border_width' ] ) && !empty( $rule_part[ 'border_color' ] ) )
                                $rule_part[ 'border_width' ] = '1px';
                            if ( empty( $rule_part[ 'border_style' ] ) && !empty( $rule_part[ 'border_color' ] ) )
                                $rule_part[ 'border_style' ] = 'solid';
                            $value = implode( ' ', array(
                                $rule_part[ 'border_width' ], 
                                $rule_part[ 'border_style' ], 
                                $rule_part[ 'border_color' ]
                            ) );
                        else:
                            $value = '';
                        endif;
                        
                        $newqsid = $this->update_property(
                            $newquery,
                            $newselector,
                            $rule_arr[ 'query' ],
                            $rule_arr[ 'selector' ], 
                            $rule,
                            $value,
                            $rule_part[ 'important' ],
                            $rulevalid
                        );

                    endforeach;
                endforeach;
            endforeach;
        
            // remove if all values have been cleared - moved from update_arrays v2.2.5
            $this->prune_if_empty( $qsid );
        
            if ( $newqsid != $qsid )
                $qsid = $newqsid;
        
            // return updated qsid to browser to update form
            if ( $this->ctc()->cache_updates )
                $this->ctc()->updates[] = array(
                    'obj'   => 'qsid',
                    'key'   => $qsid,
                    'data'  => $this->obj_to_utf8( $this->denorm_sel_val( $qsid ) ),
                );
        
            do_action( 'chld_thm_cfg_update_qsid', $qsid );                
        endif;

        // update enqueue function if imports have not been converted or new imports passed
        if (  isset( $_POST[ 'ms_theme_child_analysis' ] )||isset( $_POST[ 'ms_child_imports' ] ) || !$this->get_prop( 'converted' ) )
            add_action( 'chld_thm_cfg_addl_files',   array( $this->ctc(), 'enqueue_parent_css' ), 15, 2 );
    }
    
    
    function update_property(
        $newquery,
        $newselector,
        $query,
        $selector, 
        $rule,
        $value,
        $important,
        $rulevalid
    ){
        // If this is a renamed selector, add new selector to data
        // otherwise update existing selector
        $newqsid = $this->update_arrays( 
            'c',
            $newquery ? $newquery : $query,
            $newselector ? $newselector : $selector,
            $rule,
            trim( $value ),
            $important,
            $rulevalid
        );
        // if query or selector have been renamed, 
        // clear the original selector's value:
        if ( $newquery || $newselector ):
            $qsid = $this->update_arrays(
                'c',
                $query,
                $selector,
                $rule,
                '',
                0,
                $rulevalid
            );
            // add new sequence entry
            $seq = $this->get_dict_value( 'seq', $qsid );
            $this->set_dict_value( 'seq', $newqsid, $seq );
        endif;
        return $newqsid;
    }
    
    /**
     * parse_css_input
     * Normalize raw user CSS input so that the parser can read it.
     */
    function parse_css_input( $styles ) {
        return $this->repl_octal( stripslashes( $this->esc_octal( $styles ) ) );
    }
    
    // strips non printables and potential commands
    function sanitize( $styles ) {
        return sanitize_text_field( preg_replace( '/[^[:print:]]|[\{\}].*/', '', $styles ) );
    }
    
    // escapes octal values in input to allow for specific ascii strings in content rule
    function esc_octal( $styles ){
        return preg_replace( "#(['\"])\\\\([0-9a-f]{4})(['\"])#i", "$1##bs##$2$3", $styles );
    }
    
    // unescapes octal values for writing specific ascii strings in content rule
    function repl_octal( $styles ) {
        return str_replace( "##bs##", "\\", $styles );
    }
    
    /**
     * parse_css_file
     * reads stylesheet to get WordPress meta data and passes rest to parse_css 
     */
    function parse_css_file( $template, $file = 'style.css', $cfgtemplate = FALSE ) {
        if ( '' == $file ) $file = 'style.css';
        
        $this->ctc()->cache_updates = FALSE;
        $this->styles = ''; // reset styles
        $this->read_stylesheet( $template, $file );
        // get theme name
        $regex = '#Theme Name:\s*(.+?)\n#i';
        preg_match( $regex, $this->styles, $matches );
        $child_name = $this->get_prop( 'child_name' );
        if ( !empty( $matches[ 1 ] ) && 'child' == $template && empty( $child_name ) ) $this->set_prop( 'child_name', $matches[ 1 ] );
        $this->parse_css( 
            $cfgtemplate ? $cfgtemplate : $template, 
            NULL, 
            TRUE, 
            $this->ctc()->normalize_path( dirname( $file ) )
        );
    }

    // loads raw css file into local memory
    function read_stylesheet( $template = 'child', $file = 'style.css' ) {
        
        // these conditions support revert/restore option in 1.6.0+
        if ( 'all' == $file ) return;
        elseif ( '' == $file ) $file = 'style.css';
        // end revert/restore conditions
        
        $source = $this->get_prop( $template );
        if ( empty( $source ) || !is_scalar( $source ) ) return FALSE;
        $themedir = trailingslashit( get_theme_root() ) . $source;
        $stylesheet = apply_filters( 'chld_thm_cfg_' . $template, trailingslashit( $themedir ) 
            . $file , ( $this->ctc()->is_legacy() ? $this : $file ) ); // support for plugins extension < 2.0

        // read stylesheet
        
        if ( $stylesheet_verified = $this->is_file_ok( $stylesheet, 'read' ) ):
            
            $this->styles .= @file_get_contents( $stylesheet_verified ) . "\n";
            //echo 'count after get contents: ' . strlen( $this->styles ) . LF;
        else:
            //echo 'not ok!' . LF;
        endif;
    }

    /**
     * parse_css
     * Accepts raw CSS as text and parses into individual properties.
     * FIXME - this function has grown too monolithic - refactor and componentize
     * FIXME - migrate to event parser? handle comments?
     */
    function parse_css( $template, $basequery = NULL, $parse_imports = TRUE, $relpath = '', $reset = FALSE ) {
        //$this->load_config( 'sel_ndx' );
        $this->load_config( 'val_ndx' );
        $this->load_config( 'dict_query' );
        $this->load_config( 'dict_sel' );
        $this->load_config( 'dict_token' );
        $this->load_config( 'dict_qs' );
        $this->load_config( 'dict_val' );
        $this->load_config( 'dict_rule' );
        $this->load_config( 'dict_seq' );
        if ( FALSE === strpos( $basequery, '@' ) ):
            $basequery = 'base';
        endif;
        $ruleset = array();
        // ignore commented code
        $this->styles = preg_replace( '#\/\*.*?\*\/#s', '', $this->styles );
        // space braces to ensure correct matching
        $this->styles = preg_replace( '#([\{\}])\s*#', "$1\n", $this->styles );
        // get all imports
        if ( $parse_imports ):
            
            $regex = '#(\@import\s+url\(.+?\));#';
            preg_match_all( $regex, $this->styles, $matches );
            foreach ( preg_grep( '#' . $this->get_prop( 'parnt' ) . '\/style\.css#', $matches[ 1 ], PREG_GREP_INVERT ) as $import ):
                $import = preg_replace( "#^.*?url\(([^\)]+?)\).*#", "$1", $import );
                $import = preg_replace( "#[\'\"]#", '', $import );
                $import = '@import url(' . trim( $import ) . ')';
                $this->imports[ $template ][ $import ] = 1;
            endforeach;
            if ( $this->ctc()->cache_updates ):
                $this->ctc()->updates[] = array(
                    'obj'  => 'imports',
                    'data' => array_keys( $this->imports[ $template ] ),
                );
            endif;
        endif;
        // break into @ segments
        foreach ( array(
            '#(\@media[^\{]+?)\{(\s*?)\}#', // get any placehoder (empty) media queries
            '#(\@media[^\{]+?)\{(.*?\})?\s*?\}#s', // get all other media queries
        ) as $regex ): // (((?!\@media).) backreference too memory intensive - rolled back in v 1.4.8.1
            preg_match_all( $regex, $this->styles, $matches );
            foreach ( $matches[ 1 ] as $segment ):
                $segment = $this->normalize_query( $segment );
                $ruleset[ $segment ] = array_shift( $matches[ 2 ] ) 
                    . ( isset( $ruleset[ $segment ] ) ?
                        $ruleset[ $segment ] : '' );
            endforeach;
            // stripping rulesets leaves base styles
            $this->styles = preg_replace( $regex, '', $this->styles );
        endforeach;
        $ruleset[ $basequery ] = $this->styles;
        $qsid = NULL;
        foreach ( $ruleset as $query => $segment ):
            // make sure there is a newline before the first selector
            $segment = LF . $segment;
            // make sure there is semicolon before closing brace
            $segment = preg_replace( '#(\})#', ";$1", $segment );
            // parses selectors and corresponding rules
            $regex = '#\n\s*([\[\.\#\:\w][\w\-\s\(\)\[\]\'\^\*\.\#\+\~:,"=>]+?)\s*\{(.*?)\}#s';  //[^\{] may be to expensive
            preg_match_all( $regex, $segment, $matches );
            foreach( $matches[ 1 ] as $sel ):
                $stuff  = array_shift( $matches[ 2 ] );
                $this->update_arrays(
                    'child' == $template ? 'c' : 'p',
                    $query,
                    $sel
                );
                // handle base64 data
                $stuff = preg_replace( '#data:([^;]+?);([^\)]+?)\)#s', "data:$1%%semi%%$2)", $stuff );
                // rule semaphore makes sure rules are only reset the first time they appear
                $resetrule = array(); 
                foreach ( explode( ';', $stuff ) as $ruleval ):
                    if ( FALSE === strpos( $ruleval, ':' ) ) continue;
                    list( $rule, $value ) = explode( ':', $ruleval, 2 );
                    $rule   = trim( $rule );
                    $rule   = preg_replace_callback( "/[^\w\-]/", array( $this, 'to_ascii' ), $rule );
                    // handle base64 data
                    $value  = trim( str_replace( '%%semi%%', ';', $value ) );
                    
                    $rules = $values = array();
                    // save important flag
                    $important = $this->is_important( $value );
                    // normalize color
                    $value = $this->normalize_color( $value );
                    // normalize font
                    if ( 'font' == $rule ):
                        $this->normalize_font( $value, $rules, $values );
                    // normalize background
                    elseif( 'background' == $rule ):
                        $this->normalize_background( $value, $rules, $values );
                    // normalize margin/padding
                    elseif ( 'margin' == $rule || 'padding' == $rule ):
                        $this->normalize_margin_padding( $rule, $value, $rules, $values );
                    else:
                        $rules[]    = $rule;
                        $values[]   = $value;
                    endif;
                    foreach ( $rules as $rule ):
                        $value = trim( array_shift( $values ) );
                        // normalize zero values
                        $value = preg_replace( '#\b0(px|r?em)#', '0', $value );
                        // normalize gradients
                        if ( FALSE !== strpos( $value, 'gradient' ) ):
                            if ( FALSE !== strpos( $rule, 'filter' ) ):
                                // treat as background-image, we'll add filter rule later
                                $rule = 'background-image';
                                continue; 
                            endif;
                            if ( FALSE !== strpos( $value, 'webkit-gradient' ) ) continue; // bail on legacy webkit, we'll add it later
                            $value = $this->encode_gradient( $value );
                        endif;
                        // normalize common vendor prefixes
                        $rule = preg_replace( '#(\-(o|ms|moz|webkit)\-)?(' . implode( '|', $this->vendorrule ) . ')#', "$3", $rule );
                        if ( 'parnt' == $template && 'background-image' == $rule && strstr( $value, 'url(' ) )
                            $value = $this->convert_rel_url( $value, $relpath );
                        /**
                         * The reset flag forces the values for a given property (rule) to be rewritten completely 
                         * when using the raw CSS input or when reading from a stylesheet.
                         * This permits complete blocks of style data to be entered verbatim, replacing existing styles.
                         * When entering individual values from the Query/Selector inputs, multiple fallback values for existing 
                         * properties can be added in the order they are entered (e.g., margin: 1rem; margin: 1em;)
                         */
                        if ( !$reset ) $resetrule[ $rule ] = TRUE; 
                        
                        $qsid = $this->update_arrays( 
                            'child' == $template ? 'c' : 'p',
                            $query, 
                            $sel, 
                            $rule, 
                            $value, 
                            $important, 
                            NULL, // no rulevalid is passed when parsing from css (vs post input data)
                            empty( $resetrule[ $rule ] ) // if rule semaphore is TRUE, reset will be FALSE
                        );
                        $resetrule[ $rule ] = TRUE; // set rule semaphore so if same rule occurs again, it is not reset
                    endforeach;
                endforeach;
            endforeach;
        endforeach;
        // if this is a raw css update pass the last selector back to the browser to update the form
        if ( $this->ctc()->cache_updates && $qsid ):
            $this->ctc()->updates[] = array(
                'obj'   => 'qsid',
                'key'   => $qsid,
                'data'  => $this->obj_to_utf8( $this->denorm_sel_val( $qsid ) ),
            );
            do_action( 'chld_thm_cfg_update_qsid', $qsid );                
        endif;
        
    }

    // converts relative path to absolute path for preview
    function convert_rel_url( $value, $relpath, $url = TRUE  ) {
        if ( preg_match( '/data:/', $value ) ) return $value;
        $path       = preg_replace( '%url\([\'" ]*(.+?)[\'" ]*\)%', "$1", $value );
        if ( preg_match( '%(https?:)?//%', $path ) ) return $value;
        $pathparts  = explode( '/', $path );
        $fileparts  = explode( '/', $relpath );
        $newparts   = array();
        while ( $pathpart = array_shift( $pathparts ) ):
            if ( '..' == $pathpart )
                array_pop( $fileparts );
            else array_push( $newparts, sanitize_text_field( $pathpart ) );
        endwhile;
        $newvalue = ( $url ? 'url(' : '' )
            . ( $fileparts ? trailingslashit( implode( '/', $fileparts ) ) : '' ) 
            . implode( '/', $newparts ) . ( $url ? ')' : '' );
        $this->ctc()->debug( 'converted ' . $value . ' to ' . $newvalue . ' with ' . $relpath, __FUNCTION__, __CLASS__ );
        return $newvalue;
    }
    
    /**
     * write_css
     * converts normalized CSS object data into stylesheet.
     * Preserves selector sequence and !important flags of parent stylesheet.
     * @media query blocks are sorted using internal heuristics (see sort_queries)
     * New selectors are appended to the end of each media query block.
     * FIXME - this function has grown too monolithic - refactor and componentize
     */
    function write_css() {
        $output  = '';
        foreach ( $this->sort_queries() as $query => $sort_order ):
            $has_selector = 0;
            $sel_output   = '';
            $selectors = $this->denorm_dict_qs( $query, FALSE );
            uasort( $selectors, array( $this, 'cmp_seq' ) );
            if ( 'base' != $query ) $sel_output .=  $query . ' {' . LF;
            foreach ( $selectors as $selid => $qsid ):
                if ( $valarr = $this->unpack_val_ndx( $qsid ) ):
                    $sel            = $this->detokenize( $this->get_dict_value( 'sel', $selid ) );
                    $shorthand      = array();
                    $rule_output    = array();
                    foreach ( $valarr as $ruleid => $temparr ):
                        // normalize values for backward compatability
                        if ( isset( $temparr[ 'c' ] ) && 
                            ( !isset( $temparr[ 'p' ] ) || $temparr[ 'p' ] != $temparr[ 'c' ] ) ):
                            foreach ( $temparr[ 'c' ] as $rulevalarr ):
                                $this->add_vendor_rules( 
                                    $rule_output,
                                    $shorthand,
                                    $this->get_dict_value( 'rule', $ruleid ),
                                    $this->get_dict_value( 'val', $rulevalarr[ 0 ] ),
                                    $rulevalarr[ 1 ],
                                    $rulevalarr[ 2 ]
                                );
                            endforeach;
                        /**
                         * for testing
                        else:
                            foreach ( $temparr[ 'parnt' ] as $rulevalarr ):
                                $this->add_vendor_rules( 
                                    $rule_output,
                                    $shorthand,
                                    $rulearr[ $ruleid ],
                                    $valarr[ $rulevalarr[ 0 ] ],
                                    $rulevalarr[ 1 ],
                                    $rulevalarr[ 2 ]
                                );
                            endforeach;
                          */
                        endif;
                    endforeach;
                    /** FIXME ** need better way to sort rules and multiple values ***/
                    $this->encode_shorthand( $shorthand, $rule_output );
                    if ( count( $rule_output ) ):
                        // show load order -- removed in v.1.7.6 by popular demand
                        //$sel_output .= isset( $this->dict_seq[ $qsid ] )?'/*' . $this->dict_seq[ $qsid ] . '*/' . LF:''; 
                        $sel_output .= $sel . ' {' . LF . $this->stringify_rule_output( $rule_output ) . '}' . LF; 
                        $has_selector = 1;
                    endif;
                endif;
            endforeach;
            if ( 'base' != $query ) $sel_output .= '}' . LF;
            if ( $has_selector ) $output .= $sel_output;
        endforeach;
        $output = $this->get_css_header_comment( $this->get_prop( 'handling' ) ) . LF . $output;
        $stylesheet = $this->get_stylesheet_path();
        $this->ctc()->debug( 'writing stylesheet: ' . $stylesheet, __FUNCTION__, __CLASS__ );
        //echo //print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true) . LF;
        if ( $stylesheet_verified = $this->is_file_ok( $stylesheet, 'write' ) ):
            global $wp_filesystem; // this was initialized earlier;
            $mode = 'direct' == $this->ctc()->fs_method ? FALSE : 0666;
            // write new stylesheet:
            // try direct write first, then wp_filesystem write
            // stylesheet must already exist and be writable by web server
            if ( $this->ctc()->is_ajax && is_writable( $stylesheet_verified ) ):
                if ( FALSE === @file_put_contents( $stylesheet_verified, $output ) ): 
                    $this->ctc()->debug( 'Ajax write failed.', __FUNCTION__, __CLASS__ );
                    return FALSE;
                endif;
            elseif ( FALSE === $wp_filesystem->put_contents( $this->ctc()->fspath( $stylesheet_verified ), $output, $mode ) ):
                $this->ctc()->debug( 'Filesystem write failed.', __FUNCTION__, __CLASS__ );
                return FALSE;
            endif;
            return TRUE;
        endif;   
        return FALSE;
    }
    
    function stringify_rule_output( &$rule_output ) {
        $output = '';
        asort( $rule_output );
        foreach ( $rule_output as $rule => $sortstr )
            $output .= '    ' . $rule . ";\n";
        return $output;
    }
    
    function sortstr( $rule, $rulevalid ) {
        return substr( "0000" . $this->get_dict_id( 'rule', $rule ), -4) . substr( "00" . $rulevalid, -2 );
    }

    /**
     * encode_shorthand
     * converts CTC long syntax into CSS shorthand
     * v1.7.5 refactored for multiple values per property
     * v2.1.0 to prevent incorrect rendering, do not use shorthand if multiple values exist for any side property.
     */
    function encode_shorthand( $shorthand, &$rule_output ) {
        foreach ( $shorthand as $property => $sides ):
            if ( isset( $sides[ 'top' ] ) && 1 == count( $sides[ 'top' ] ) ):
                foreach ( $sides[ 'top' ] as $tval => $tarr ):
                    if ( isset( $sides[ 'right' ] ) && 1 == count( $sides[ 'right' ] ) ):
                        $currseq = $tarr[ 1 ];
                        foreach ( $sides[ 'right' ] as $rval => $rarr ):
                            // value must exist from side and priority must match all sides
                            if ( isset( $sides[ 'bottom' ] ) && 1 == count( $sides[ 'bottom' ] ) && $tarr[ 0 ] == $rarr[ 0 ] ):
                                if ( $rarr[ 1 ] > $currseq ) $currseq = $rarr[ 1 ];
                                foreach ( $sides[ 'bottom' ] as $bval => $barr ):
                                    if ( isset( $sides[ 'left' ] ) && 1 == count( $sides[ 'left' ] ) && $tarr[ 0 ] == $barr[ 0 ] ):
                                        // use highest sort sequence of all sides
                                        if ( $barr[ 1 ] > $currseq ) $currseq = $barr[ 1 ];
                                        foreach ( $sides[ 'left' ] as $lval => $larr ):
                                            if ( $tarr[ 0 ] != $larr[ 0 ] ) continue;
                                            if ( $larr[ 1 ] > $currseq ) $currseq = $larr[ 1 ];

                                            $combo = array(
                                                $tval,
                                                $rval,
                                                $bval,
                                                $lval,
                                            );
                                            // echo 'combo before: ' . print_r( $combo, TRUE ) . LF;
                                            // remove from shorthand array
                                            unset( $shorthand[ $property ][ 'top' ][ $tval ] );
                                            unset( $shorthand[ $property ][ 'right' ][ $rval ] );
                                            unset( $shorthand[ $property ][ 'bottom' ][ $bval ] );
                                            unset( $shorthand[ $property ][ 'left' ][ $lval ] );
                                            
                                            // combine into shorthand syntax
                                            if ( $lval === $rval ):
                                                //echo 'left same as right, popping left' . LF;
                                                array_pop( $combo );
                                                if ( $bval === $tval ):
                                                    //echo 'bottom same as top, popping bottom' . LF;
                                                    array_pop( $combo );
                                                    if ( $rval === $tval ): // && $bval === $tval ):
                                                        //echo 'right same as top, popping right' . LF;
                                                        array_pop( $combo );
                                                    endif;
                                                endif;
                                            endif;
                                            //echo 'combo after: ' . print_r( $combo, TRUE ) . LF;
                                            // set rule
                                            $rule_output[ $property . ': ' . implode( ' ', $combo ) . ( $tarr[ 0 ] ? ' !important' : '' ) ] = $this->sortstr( $property, $currseq );
                                            // reset sort sequence
                                            $currseq = 0;
                                        endforeach;
                                    endif;
                                endforeach;
                            endif;
                        endforeach;
                    endif;
                endforeach;
            endif;
        endforeach;
        // add remaining rules
        foreach ( $shorthand as $property => $sides ):
            foreach ( $sides as $side => $values ):
                $rule = $property . '-' . $side;
                foreach ( $values as $val => $valarr ):
                    // set rule
                    $rule_output[ $rule . ': ' . $val . ( $valarr[ 0 ] ? ' !important' : '' ) ] = $this->sortstr( $rule, $valarr[ 1 ] );
                endforeach;
            endforeach;
        endforeach;
    }
    
    /**
     * add_vendor_rules
     * Applies vendor prefixes to rules/values and separates out shorthand properties .
     * These are based on commonly used practices and not all vendor prefixes are supported.
     * TODO: verify this logic against vendor and W3C documentation
     */
    function add_vendor_rules( &$rule_output, &$shorthand, $rule, $value, $important, $rulevalid ) {
        if ( '' === trim( $value ) ) return;
        if ( 'filter' == $rule && ( FALSE !== strpos( $value, 'progid:DXImageTransform.Microsoft.Gradient' ) ) ) return;
        $importantstr = $important ? ' !important' : '';
        if ( preg_match( "/^(margin|padding)\-(top|right|bottom|left)$/", $rule, $matches ) ):
            $shorthand[ $matches[ 1 ] ][ $matches[ 2 ] ][ $value ] = array(
                $important,
                $rulevalid,
                );
            return;
        elseif ( preg_match( '/^(' . implode( '|', $this->vendorrule ) . ')$/', $rule ) ):
            foreach( array( 'moz', 'webkit', 'o' ) as $prefix ):
                $rule_output[ '-' . $prefix . '-' . $rule . ': ' . $value . $importantstr ] = $this->sortstr( $rule, $rulevalid++ );
            endforeach;
            $rule_output[ $rule . ': ' . $value . $importantstr ] = $this->sortstr( $rule, $rulevalid );
        elseif ( 'background-image' == $rule ):
            // gradient?
            
            if ( $gradient = $this->decode_gradient( $value ) ):
                // standard gradient
                foreach( array( 'moz', 'webkit', 'o', 'ms' ) as $prefix ):
                    // build key before dereferencing array - v.2.3.0.3
                    $propkey = 'background-image: -' . $prefix . '-' . 'linear-gradient(' . $gradient[ 'origin' ] . ', ' 
                        . $gradient[ 'color1' ] . ', ' . $gradient[ 'color2' ] . ')' . $importantstr;
                    $rule_output[ $propkey ] = $this->sortstr( $rule, $rulevalid++ );
                endforeach;
                // W3C standard gradient
                // rotate origin 90 degrees
                if ( preg_match( '/(\d+)deg/', $gradient[ 'origin' ], $matches ) ):
                    $org = ( 90 - $matches[ 1 ] ) . 'deg';
                else: 
                    foreach ( preg_split( "/\s+/", $gradient[ 'origin' ] ) as $dir ):
                        $dir = strtolower( $dir );
                        $dirs[] = ( 'top' == $dir ? 'bottom' : 
                            ( 'bottom' == $dir ? 'top' : 
                                ( 'left' == $dir ? 'right' : 
                                    ( 'right' == $dir ? 'left' : $dir ) ) ) );
                    endforeach;
                    $org = 'to ' . implode( ' ', $dirs );
                endif;
                // build key before dereferencing array - v.2.3.0.3
                $propkey = 'background-image: linear-gradient(' . $org . ', ' 
                    . $gradient[ 'color1' ] . ', ' . $gradient[ 'color2' ] . ')' . $importantstr;
                $rule_output[ $propkey ] = $this->sortstr( $rule, $rulevalid );
                
                // legacy webkit gradient - we'll add if there is demand
                // '-webkit-gradient(linear,' .$origin . ', ' . $color1 . ', '. $color2 . ')';
                
                /** 
                 * MS filter gradient - DEPRECATED in v1.7.5
                 * $type = ( in_array( $gradient[ 'origin' ], array( 'left', 'right', '0deg', '180deg' ) ) ? 1 : 0 );
                 * $color1 = preg_replace( "/^#/", '#00', $gradient[ 'color1' ] );
                 * $rule_output[ 'filter: progid:DXImageTransform.Microsoft.Gradient(GradientType=' . $type . ', StartColorStr="' 
                 *    . strtoupper( $color1 ) . '", EndColorStr="' . strtoupper( $gradient[ 'color2' ] ) . '")' 
                 *    . $importantstr ] = $this->sortstr( $rule, $rulevalid );
                 */
            else:
                // url or other value
                $rule_output[ $rule . ': ' . $value . $importantstr ] = $this->sortstr( $rule, $rulevalid );
            endif;
        else:
            $rule = preg_replace_callback( "/\d+/", array( $this, 'from_ascii' ), $rule );
            $rule_output[ $rule . ': ' . $value . $importantstr ] = $this->sortstr( $rule, $rulevalid );
        endif;
    }

    /**
     * normalize_background
     * parses background shorthand value and returns
     * normalized rule/value pairs for each property
     */
    function normalize_background( $value, &$rules, &$values ) {
        if ( FALSE !== strpos( $value, 'gradient' ) ):
            // only supporting linear syntax
            if ( preg_match( '#(linear\-|Microsoft\.)#', $value ) ):
                $values[] = $value;
                $rules[] = 'background-image';
            else:
                // don't try to normalize non-linear gradients
                $values[] = $value;
                $rules[] = 'background';
            endif;
        else:
            $regexes = array(
                'image'         => 'url *\\([^)]+?\\)|none',
                'attachment'    => 'scroll|fixed|local',
                'clip'          => '(padding|border|content)\\-box',
                'repeat'        => '(no\\-)?repeat(\\-(x|y))?|round|space',
                'size'          => 'cover|contain|auto',
                'position'      => 'top|bottom|left|right|center|\b0 +0\b|(\b0 +)?[\\-\\d.]+(px|%)( +0\b)?',
                'color'         => '\\#[a-fA-F0-9]{3,6}|(hsl|rgb)a? *\\([^)]+?\\)|[a-z]+'                
            );
            //echo '<pre><code>' . "\n";
            //echo '<strong>' . $value . '</strong>' . "\n";
            foreach ( $regexes as $property => $regex ):
                $this->temparray = array();
                //echo $property . ': ' . $regex . "\n";
                $value = preg_replace_callback( "/(" . $regex . ")/", array( $this, 'background_callback' ), $value );
                if ( count( $this->temparray ) ):
                    $rules[] = 'background-' . $property;
                    $values[] = implode( ' ', $this->temparray );
                    //echo '<strong>result: ' . implode( ' ', $this->temparray ) . "</strong>\n";
                endif;
            endforeach;
            //echo '</code></pre>' . "\n";
        endif;
    }
    
    function background_callback( $matches ) {
        $this->temparray[] = $matches[ 1 ];
    }

    /**
     * normalize_font
     * parses font shorthand value and returns
     * normalized rule/value pairs for each property
     */
    function normalize_font( $value, &$rules, &$values ) {
        $regex = '#^((\d+|bold|normal) )?((italic|normal) )?(([\d\.]+(px|r?em|%))[\/ ])?(([\d\.]+(px|r?em|%)?) )?(.+)$#is';
        preg_match( $regex, $value, $parts );
        if ( !empty( $parts[ 2 ] ) ):
            $rules[]    = 'font-weight';
            $values[]   = $parts[ 2 ];
        endif;
        if ( !empty( $parts[ 4 ] ) ):
            $rules[]    = 'font-style';
            $values[]   = $parts[ 4 ];
        endif;      
        if ( !empty( $parts[ 6 ] ) ):
            $rules[]    = 'font-size';
            $values[]   = $parts[ 6 ];
        endif;
        if ( !empty( $parts[ 9 ] ) ):
            $rules[]    = 'line-height';
            $values[]   = $parts[ 9 ];
        endif;
        if ( !empty( $parts[ 11 ] ) ):
            $rules[]    = 'font-family';
            $values[]   = $parts[ 11 ];
        endif;
    }

    /**
     * normalize_margin_padding
     * parses margin or padding shorthand value and returns
     * normalized rule/value pairs for each property
     */
    function normalize_margin_padding( $rule, $value, &$rules, &$values ) {
        $parts = preg_split( "/ +/", trim( $value ) );
        if ( !isset( $parts[ 1 ] ) ) $parts[ 1 ] = $parts[ 0 ];
        if ( !isset( $parts[ 2 ] ) ) $parts[ 2 ] = $parts[ 0 ];
        if ( !isset( $parts[ 3 ] ) ) $parts[ 3 ] = $parts[ 1 ];
        $rules[ 0 ]   = $rule . '-top';
        $values[ 0 ]  = $parts[ 0 ];
        $rules[ 1 ]   = $rule . '-right';
        $values[ 1 ]  = $parts[ 1 ];
        $rules[ 2 ]   = $rule . '-bottom';
        $values[ 2 ]  = $parts[ 2 ];
        $rules[ 3 ]   = $rule . '-left';
        $values[ 3 ]  = $parts[ 3 ];
    }

    /**
     * encode_gradient
     * Normalize linear gradients from a bazillion formats into standard CTC syntax.
     * This has been refactored in v1.7.5 to accommodate new spectrum color picker color "names."
     * Currently only supports two-color linear gradients with no inner stops.
     * TODO: legacy webkit? more gradients? 
     */
    function encode_gradient( $value ) {
        // don't try this at home, kids
        $regex = '/gradient[^\)]*?\(    #exp    descr
        (                               #[1]
        (                               #[2]
        (to\x20)?                       #[3]    reverse
        (top|bottom|left|right)?        #[4]    direction1
        (\x20                           #[5]
        (top|bottom|left|right))?       #[6]    direction2
            |\d+deg),)?                 #       or angle
        (color-stop\()?                 #[7]    optional
        ([^\w\#\)]*[\'"]?               #[8]
        (\#\w{3,8}                      #[9]    color (hex)
            |rgba?\([\d.,\x20]+?\)      #       red green blue (alpha)
            |hsla?\([\d%.,\x20]+?\)     #       hue sat. lum. (alpha)
            |[a-z]+)                    #       color (name)
        (\x20+[\d.]+%?)?)               #[10]   stop position
        (\),\x20*)?                     #[11]   optional close
        (color-stop\()?                 #[12]   optional
        ([^\w\#\)]*[\'"]?               #[13]
        (\#\w{3,8}                      #[14]   color (hex)
            |rgba?\([\d.,\x20]+?\)      #       red green blue (alpha)
            |hsla?\([\d%.,\x20]+?\)     #       hue sat. lum. (alpha)
            |[a-z]+)                    #       color (name)
        (\x20+[\d.]+%?)?)               #[15]   stop position
        (\))?                           #[16]   optional close
        ([^\w\)]*gradienttype=[\'"]?    #[17]   IE
        (\d)                            #[18]   IE
        [\'"]?)?                        #       IE
        [^\w\)]*\)/ix';
        $param = $parts = array();
        preg_match( $regex, $value, $parts );
        //$this->ctc()->debug( 'gradient value: ' . $value . ' parts: ' . print_r( $parts, TRUE ), __FUNCTION__, __CLASS__ );
        if ( empty( $parts[ 18 ] ) ):
            if ( empty( $parts[ 2 ] ) ):
                $param[ 0 ] = 'top';
            elseif ( 'to ' == $parts[ 3 ] ):
            
                $param[ 0 ] = ( 'top' == $parts[ 4 ] ? 'bottom' :
                    ( 'left' == $parts[ 4 ] ? 'right' : 
                        ( 'right' == $parts[ 4 ] ? 'left' : 
                            'top' ) ) ) ;
            else: 
                $param[ 0 ] = trim( $parts[ 2 ] );
            endif;
            if ( empty( $parts[ 10 ] ) ):
                $param[ 2 ] = '0%';
            else:
                $param[ 2 ] = trim( $parts[ 10 ] );
            endif;
            if ( empty( $parts[ 15 ] ) ):
                $param[ 4 ] = '100%';
            else:
                $param[ 4 ] = trim( $parts[ 15 ] );
            endif;
        elseif( '0' == $parts[ 18 ] ):
            $param[ 0 ] = 'top';
            $param[ 2 ] = '0%';
            $param[ 4 ] = '100%';
        elseif ( '1' == $parts[ 18 ] ): 
            $param[ 0 ] = 'left';
            $param[ 2 ] = '0%';
            $param[ 4 ] = '100%';
        endif;
        if ( isset( $parts[ 9 ] ) && isset( $parts[ 14 ] ) ):
            $param[ 1 ] = $parts[ 9 ];
            $param[ 3 ] = $parts[ 14 ];
            ksort( $param );
            return implode( ':', $param );
        else:
            return $value;
        endif;
    }

    /**
     * decode_border
     * De-normalize CTC border syntax into individual properties.
     */
    function decode_border( $value ) {
        $parts = preg_split( '#\s+#', $value, 3 );
        if ( 1 == count( $parts ) ):
            $parts[ 0 ] = $value;
            $parts[ 1 ] = $parts[ 2 ] = '';
        endif;
        return array(
            'width' => empty( $parts[ 0 ] ) ? '' : $parts[ 0 ],
            'style' => empty( $parts[ 1 ] ) ? '' : $parts[ 1 ],
            'color' => empty( $parts[ 2 ] ) ? '' : $parts[ 2 ],
        );
    }

    /**
     * decode_gradient
     * Decode CTC gradient syntax into individual properties.
     */
    function decode_gradient( $value ) {
        $parts = explode( ':', $value, 5 );
        if ( !preg_match( '#(url|none)#i', $value ) && 5 == count( $parts ) ):        
            return array(
                'origin' => empty( $parts[ 0 ] ) ? '' : $parts[ 0 ],
                'color1' => empty( $parts[ 1 ] ) ? '' : $parts[ 1 ],
                'stop1'  => empty( $parts[ 2 ] ) ? '' : $parts[ 2 ],
                'color2' => empty( $parts[ 3 ] ) ? '' : $parts[ 3 ],
                'stop2'  => empty( $parts[ 4 ] ) ? '' : $parts[ 4 ],
            );
        endif;
        return FALSE;
    }

    /**
     * denorm_rule_val
     * Return array of unique values corresponding to specific rule
     * FIXME: only return child if no original value exists
     */    
    function denorm_rule_val( $ruleid ) {
        $this->load_config( 'dict_val' );
        $this->load_config( 'val_ndx' );
        $rule_sel_arr = array();
        foreach ( $this->val_ndx as $qsid => $p ):
            if ( $valarr = $this->unpack_val_ndx( $qsid ) ):
                if ( !isset( $valarr[ $ruleid ] ) ) continue;
                foreach ( array( 'p', 'c' ) as $template ):
                    if ( isset( $valarr[ $ruleid ][ $template ] ) ):
                        foreach ( $valarr[ $ruleid ][ $template ] as $rulevalarr ):
                            $rule_sel_arr[ $rulevalarr[ 0 ] ] = $this->get_dict_value( 'val', $rulevalarr[ 0 ] );
                        endforeach;
                    endif;
                endforeach;
            endif;
        endforeach;
        return $rule_sel_arr;
    }

    /**
     * denorm_val_query
     * Return array of queries, selectors, rules, and values corresponding to
     * specific rule/value combo grouped by query, selector
     * FIXME: only return new values corresponding to specific rulevalid of matching original value
     */    
    function denorm_val_query( $valid, $rule ) {
        $this->load_config( 'dict_rule' );
        $this->load_config( 'val_ndx' );
        $value_query_arr = array();
        if ( $thisruleid = $this->get_dict_id( 'rule', $rule ) ):
            foreach ( $this->val_ndx as $qsid => $p ):
                if ( $valarr = $this->unpack_val_ndx( $qsid ) ):
                    foreach ( $valarr as $ruleid => $values ):
                        if ( $ruleid != $thisruleid ) continue;
                        foreach ( array( 'p', 'c' ) as $template ):
                            if ( isset( $values[ $template ] ) ):
                                foreach ( $values[ $template ] as $rulevalarr ):
                                    if ( $rulevalarr[ 0 ] != $valid ) continue;
                                    $selarr = $this->denorm_query_sel( $qsid );
                                    $value_query_arr[ $rule ][ $selarr[ 'query' ] ][ $qsid ] = $this->denorm_sel_val( $qsid );
                                endforeach;
                            endif;
                        endforeach;
                    endforeach;
                endif;
            endforeach;
        endif;
        return $value_query_arr;
    }

    /**
     * denorm_query_sel
     * Return id, query and selector values of a specific qsid (query-selector ID)
     */    
    function denorm_query_sel( $qsid ) {
        $this->load_config( 'dict_query' );
        $this->load_config( 'dict_sel' );
        $this->load_config( 'dict_seq' );
        $this->load_config( 'dict_qs' );
        $this->load_config( 'dict_token' );
        if ( FALSE === ( $qs = $this->get_dict_value( 'qs', $qsid ) ) ):
            $this->ctc()->debug( $qsid . ' does not exist', __FUNCTION__, __CLASS__ );
            return array();
        endif;
        list( $q, $s ) = explode( ':', $qs );
        if ( $seq = $this->get_dict_value( 'seq', $qsid ) ):
            $this->ctc()->debug( 'get seq: custom: ' . $seq, __FUNCTION__, __CLASS__ );
        else:
            $seq = $qsid;
            $this->ctc()->debug( 'get seq: using qsid: ' . $qsid, __FUNCTION__, __CLASS__ );
        endif;
        $qselarr = array(
            'id'        => $qsid,
            'query'     => $this->get_dict_value( 'query', $q ),
            'selector'  => $this->detokenize( $this->get_dict_value( 'sel', $s ) ),
            'seq'       => $seq,
        );
        return $qselarr;
    }

    /**
     * denorm_sel_val
     * Return array of rules, and values matching specific qsid (query-selector ID)
     * grouped by query, selector
     */    
    function denorm_sel_val( $qsid ) {
        $this->load_config( 'dict_val' );
        $this->load_config( 'dict_rule' );
        $this->load_config( 'val_ndx' );
        $selarr = $this->denorm_query_sel( $qsid );
        if ( $valarr = $this->unpack_val_ndx( $qsid ) ):
            //$this->ctc()->debug( 'valarr: ' . print_r( $valarr, TRUE ), __FUNCTION__, __CLASS__ );
            foreach ( $valarr as $ruleid => $values ):
                //$this->ctc()->debug( 'ruleid: ' . $ruleid, __FUNCTION__, __CLASS__ );
                foreach ( array( 'p', 'c' ) as $template ):
                    $t = 'c' == $template ? 'child' : 'parnt';
                    //$this->ctc()->debug( 'template: ' . $t, __FUNCTION__, __CLASS__ );
                    if ( isset( $values[ $template ] ) ):
                        foreach ( $values[ $template ] as $rulevalarr ):
                            $selarr[ 'value' ][ $this->get_dict_value( 'rule', $ruleid ) ][ $t ][] = array(
                                $this->get_dict_value( 'val', $rulevalarr[ 0 ] ),
                                $rulevalarr[ 1 ],
                                isset( $rulevalarr[ 2 ] ) ? $rulevalarr[ 2 ] : 1,
                            );
                        endforeach;
                    endif;
                endforeach;
            endforeach;
        endif;
        //$this->ctc()->debug( print_r( $selarr, TRUE ), __FUNCTION__, __CLASS__ );
        return $selarr;
    }

    /**
    /**
     * v1.7.5
     * convert and/or normalize rule/value index 
     * to support multiple values per property ( rule )
     * allows backward compatility with < v1.7.5
     */
    function convert_ruleval_array( &$arr ) {
        //$this->ctc()->debug( 'original array: ' . print_r( $arr, TRUE ), __FUNCTION__, __CLASS__ );
        foreach ( array( 'parnt', 'child' ) as $template ):
            // skip if empty array
            if ( !isset( $arr[ $template ] ) ) continue;
            $t = 'child' == $template ? 'c' : 'p';
            // check if using original data structure ( value is scalar )
            if ( ! is_array( $arr[ $template ] ) ):
                /**
                 * create new array to replace old scalar value
                 * value structure is
                 * [0] => value
                 * [1] => important
                 * [2] => priority
                 */
                $arr[ $t ] = array( array( $arr[ $template ], $arr[ 'i_' . $template ], 0, 1 ) );
            else:
                $arr[ $t ] = $arr[ $template ];        
            endif;
            unset( $arr[ $template ] );
        endforeach;
        //$this->ctc()->debug( 'first pass: ' . print_r( $arr, TRUE ), __FUNCTION__, __CLASS__ );
        foreach ( array( 'p', 'c' ) as $template ):
            if ( !isset( $arr[ $template ] ) ) continue;
            $newarr = array();
            // iterate each value and enforce array structure
            foreach ( $arr[ $template ] as $rulevalid => $rulevalarr ):
                // skip if empty array
                if ( empty ( $rulevalarr ) ) continue;
                // 
                if ( ! is_array( $rulevalarr ) ):
                    // important flag moves to individual value in array
                    $important = isset( $arr[ 'i_' . $template ] ) ? $arr[ 'i_' . $template ] : 0;
                    unset( $arr[ 'i_' . $template ] ); 
                    $val = (int) $rulevalarr;
                    $rulevalarr = array( $val, $important, $rulevalid );
                elseif ( !isset( $rulevalarr[ 2 ] ) ):
                    $rulevalarr[ 2 ] = $rulevalid;
                endif;
                $newarr[] = $rulevalarr;
            endforeach;
            $arr[ $template ] = $newarr;
        endforeach;
        //$this->ctc()->debug( 'second pass: ' . print_r( $arr, TRUE ), __FUNCTION__, __CLASS__ );
    }
    
    /**
     * Convert all internal data dictionaries to latest format.
     */
    function convert_dict_arrays(){
        $this->ctc()->debug( 'converting dictionaries from old format', __FUNCTION__, __CLASS__ );
        foreach ( $this->dicts as $dict => $loaded ):
            $this->load_config( $dict );
            switch ( $dict ):
                case 'dict_seq':
                case 'dict_token':
                    break;
                case 'sel_ndx':
                    $this->{ $dict } = array();
                    break;
                case 'val_ndx':
                    foreach ( $this->val_ndx as $qsid => $rulearr ):
                        foreach ( $rulearr as $ruleid => $valarr )
                            $this->convert_ruleval_array( $this->val_ndx[ $qsid ][ $ruleid ] );
                        $this->pack_val_ndx( $qsid, $this->val_ndx[ $qsid ] );
                    endforeach;
                    break;
                case 'dict_qs':
                    $qsarr = array();
                    foreach ( $this->dict_qs as $qsid => $arr ):
                        $qs = $arr[ 'q' ] . ':' . $arr[ 's' ];
                        $qsarr[ $qsid ] = $qs;
                    endforeach;
                    $this->dict_qs = $qsarr;
                    break;
                default:
                    $this->{ $dict } = array_flip( $this->{ $dict } );
                    foreach ( $this->{ $dict } as $key => $val ):
                        if ( 'dict_sel' == $dict )
                            $this->dict_sel[ $key ] = $this->tokenize( (string) $val );
                        else
                            $this->{ $dict }[ $key ] = ( string ) $val;
                    endforeach;
            endswitch;
            //echo '<pre><code><small><strong>' . $dict . '</strong>' . print_r( $this->{ $dict }, TRUE) . '</small></code></pre>' . LF;
        endforeach;
        $this->save_config();
    }
    
    /**
     * denorm_dict_qs
     * Return denormalized array containing query and selector heirarchy
     */    
    function denorm_dict_qs( $query = NULL, $norm = TRUE ) {
        $this->load_config( 'dict_query' );
        $this->load_config( 'dict_sel' );
        $this->load_config( 'dict_token' );
        $this->load_config( 'dict_qs' );
        $retarray = array();
        if ( $query ):
            $q = $this->get_dict_id( 'query', $query );
            $selarr = preg_grep( '/^' . $q . ':/', $this->dict_qs );
            foreach ( $selarr as $qsid => $qs ):
                list( $q, $s ) = explode( ':', $qs );
                if ( $norm )
                    $retarray[ $qsid ] = $this->detokenize( $this->get_dict_value( 'sel', $s ) );
                else
                    $retarray[ $s ] = $qsid;
            endforeach;
        else:
            return array_values( $this->dict_query );
        endif;
        if ( $norm )
            return $this->sort_selectors( $retarray );
        return $retarray;
    }
    
    /**
     * is_important
     * Strip important flag from value reference and return boolean
     * Updating two values at once
     */
    function is_important( &$value ) {
        $important = 0;
        $value = trim( str_ireplace( '!important', '', $value, $important ) );
        return $important;
    }
    
    /**
     * sort_queries
     * De-normalize query data and return array sorted as follows:
     * base
     * @media max-width queries in descending order
     * other @media queries in no particular order
     * @media min-width queries in ascending order
     */
    function sort_queries() {
        $this->load_config( 'dict_query' );
        $queries = array();
        foreach ( $this->dict_query as $queryid => $query ):
            /** lookup **/
            if ( 'base' == $query ):
                $queries[ 'base' ] = -999999;
                continue;
            endif;
            if ( preg_match( "/((min|max)(\-device)?\-width)\s*:\s*(\d+)/", $query, $matches ) ):
                $queries[ $query ] = 'min-width' == $matches[ 1 ] ? $matches[ 4 ] : -$matches[ 4 ];
            else:
                $queries[ $query ] = $queryid - 10000;
            endif;
        endforeach;
        asort( $queries );
        return $queries;
    }
    
    function sort_selectors( $selarr ) {
        $selarr = ( array ) $selarr;
        uasort( $selarr, array( $this, 'cmp_sel' ) );
        return array_flip( $selarr );
    }
    
    function cmp_sel( $a, $b ) {
        $cmpa = preg_replace( "/\W/", '', $a );
        $cmpb = preg_replace( "/\W/", '', $b );
        if ( $cmpa == $cmpb ) return 0;
        return ( $cmpa < $cmpb ) ? -1 : 1;
    }
    
    // sort selectors based on dict_seq if exists, otherwise qsid
    function cmp_seq( $a, $b ) {
        if ( FALSE === ( $cmpa = $this->get_dict_value( 'seq', $a ) ) )
            $cmpa = $a;
        if ( FALSE === ( $cmpb = $this->get_dict_value( 'seq', $b ) ) )
            $cmpb = $b;
        if ( $cmpa == $cmpb ) return 0;
        return ( $cmpa < $cmpb ) ? -1 : 1;
    }

    /**
     * obj_to_utf8
     * sets object data to UTF8
     * flattens to array
     * and stringifies NULLs
     */
    function obj_to_utf8( $data ) {
        if ( is_object( $data ) )
            $data = get_object_vars( $data );
        if ( is_array( $data ) )
            return array_map( array( &$this, __FUNCTION__ ), $data );
        else
            return is_null( $data ) ? '' : utf8_encode( $data );
		
		
    }
    
    // convert ascii character into decimal value 
    function to_ascii( $matches ) {
        return ord( $matches[ 0 ] );
    }
    
    // convert decimal value into ascii character
    function from_ascii( $matches ) {
        return chr( $matches[ 0 ] );
    }
    
    /**
     * is_file_ok
     * verify file exists and is in valid location
     * must be in theme or plugin folders
     */
    function is_file_ok( $stylesheet, $permission = 'read' ) {
        // remove any ../ manipulations
        $stylesheet = $this->ctc()->normalize_path( preg_replace( "%\.\./%", '/', $stylesheet ) );
        //$this->ctc()->debug( 'checking file: ' . $stylesheet, __FUNCTION__, __CLASS__ );
        if ( 'read' == $permission && !is_file( $stylesheet ) ):
            $this->ctc()->debug( 'read ' . $stylesheet . ' no file!', __FUNCTION__, __CLASS__ );
            return FALSE;
        elseif ( 'write' == $permission && !is_dir( dirname( $stylesheet ) ) ):
            $this->ctc()->debug( 'write ' . $stylesheet . ' no dir!', __FUNCTION__, __CLASS__ );
            return FALSE;
        elseif ( 'search' == $permission && !is_dir( $stylesheet ) ):
            $this->ctc()->debug( 'search ' . $stylesheet . ' no dir!', __FUNCTION__, __CLASS__ );
            return FALSE;
        endif;
        // check if in themes dir;
        $regex = '%^' . preg_quote( $this->ctc()->normalize_path( get_theme_root() ) ) . '%';
        //$this->ctc()->debug( 'theme regex: ' . $regex, __FUNCTION__, __CLASS__ );
        if ( preg_match( $regex, $stylesheet ) ): 
            //$this->ctc()->debug( $stylesheet . ' ok!', __FUNCTION__, __CLASS__ );
            return $stylesheet;
        endif;
        // check if in plugins dir
        $regex = '%^' . preg_quote( $this->ctc()->normalize_path( WP_PLUGIN_DIR ) ) . '%';
        //$this->ctc()->debug( 'plugin regex: ' . $regex, __FUNCTION__, __CLASS__ );
        if ( preg_match( $regex, $stylesheet ) ):
            //$this->ctc()->debug( $stylesheet . ' ok!', __FUNCTION__, __CLASS__ );
            return $stylesheet;
        endif;
        $this->ctc()->debug( $stylesheet . ' is not in wp folders!', __FUNCTION__, __CLASS__ );
        return FALSE;
    }
    
    /**
     * normalize_color
     * Sets hex string to lowercase and shortens to 3 char format if possible
     */
    function normalize_color( $value ) {
        $value = preg_replace_callback( "/#([0-9A-F]{3}([0-9A-F]{3})?)/i", array( $this, 'tolower' ), $value );
        $value = preg_replace( "/#([0-9A-F])\\1([0-9A-F])\\2([0-9A-F])\\3/i", "#$1$2$3", $value );
        return $value;
    }
    
    function normalize_query( $value ) {
        // space after :
        $value = str_replace( ':', ': ', trim( $value ) );
        // remove multiple whitespace
        $value = preg_replace( "/\s+/s", ' ', $value );
        // remove space after (
        $value = str_replace( '( ', '(', $value );
        // remove space before )
        $value = str_replace( ' )', ')', $value );
        return $value;
    }
    
    // callback for normalize_color regex
    function tolower( $matches ) {
        return '#' . strtolower( $matches[ 1 ] );
    }
    
    function tokenize( $value ){
        return $value;
        // swap out commas and/or consecutive alphas with leading non-alpha if present
        $value = preg_replace_callback( "/(, |[_\W]?[^\W_]+)/", array( $this, 'get_token' ), $value );
        // trim off leading/trailing delimiter
        $value = preg_replace( "/^%%|%%$/", '', $value );
        // split into packable array
        $array = array_map( array( $this, 'to_int' ), preg_split( "/(%%)+/", $value ) );
        //echo '<pre><code><small>';
        //var_dump( $array );
        //echo '</small></code></pre>';
        try {
            return $this->packer->encode( $this->packer->pack( $array ) );
        } catch ( Exception $e ) {
            $this->ctc()->debug( 'Pack failed -- ' . $e->getMessage(), __FUNCTION__, __CLASS__ );
        }
    }
    
    function to_int( $val ){
        return intval( $val ) == $val ? (int) $val : $val;
    }
    
    function detokenize( $packed ){
        return $packed;
        // unpack array
        try {
            $this->packer->reset( $this->packer->decode( $packed ) );
            $array = $this->packer->unpack();
        } catch ( Exception $e ) {
            $this->ctc()->debug( 'Unpack failed -- ' . $e->getMessage(), __FUNCTION__, __CLASS__ );
            return FALSE;
        }
        $unpacked = array();
        // iterate array and replace tokens
        do {
            $token = array_shift( $array );
            if ( 'integer' == gettype( $token ) )
                $unpacked[] = $this->get_dict_value( 'token', $token );
            else
                $unpacked[] = $token;
        } while( $array );
            
        // assemble array
        return implode( '', $unpacked );
    }
    
    function get_token( $matches ){
        $token = $matches[ 1 ];
        $id = $this->get_dict_id( 'token', $token );
        $this->instances[ $id ] = isset( $this->instances[ $id ] ) 
            ? $this->instances[ $id ] + 1 
            : 1;
        return '%%' . $id . '%%';
    }
}