<?php

class ms_theme_editor_packer {
    
    const NON_UTF8_REGEX = '/(
        [\xC0-\xC1] # Invalid UTF-8 Bytes
        | [\xF5-\xFF] # Invalid UTF-8 Bytes
        | \xE0[\x80-\x9F] # Overlong encoding of prior code point
        | \xF0[\x80-\x8F] # Overlong encoding of prior code point
        | [\xC2-\xDF](?![\x80-\xBF]) # Invalid UTF-8 Sequence Start
        | [\xE0-\xEF](?![\x80-\xBF]{2}) # Invalid UTF-8 Sequence Start
        | [\xF0-\xF4](?![\x80-\xBF]{3}) # Invalid UTF-8 Sequence Start
        | (?<=[\x0-\x7F\xF5-\xFF])[\x80-\xBF] # Invalid UTF-8 Sequence Middle
        | (?<![\xC2-\xDF]|[\xE0-\xEF]|[\xE0-\xEF][\x80-\xBF]|[\xF0-\xF4]|[\xF0-\xF4][\x80-\xBF]|[\xF0-\xF4][\x80-\xBF]{2})[\x80-\xBF] # Overlong Sequence
        | (?<=[\xE0-\xEF])[\x80-\xBF](?![\x80-\xBF]) # Short 3 byte sequence
        | (?<=[\xF0-\xF4])[\x80-\xBF](?![\x80-\xBF]{2}) # Short 4 byte sequence
        | (?<=[\xF0-\xF4][\x80-\xBF])[\x80-\xBF](?![\x80-\xBF]) # Short 4 byte sequence (2)
    )/x';
    var $enctab = array(
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '!', '#', '$',
        '%', '&', '(', ')', '*', '+', ',', '.', '/', ':', ';', '<', '=',
        '>', '?', '@', '[', ']', '^', '_', '`', '{', '|', '}', '~', '"'
    );
    var $dectab;
    var $buffer;
    var $offset;
    
    function __construct(){
        $this->dectab = array_flip( $this->enctab );
    }
    
    function reset( $buffer = '' ){
        $this->buffer = $buffer;
        $this->offset = 0;
    }
    
    public function pack( $value ) {

        switch ( gettype( $value ) ) {
            case 'array': return array_values( $value ) === $value
                ? $this->packArray( $value )
                : $this->packMap( $value );

            case 'string': return preg_match( self::NON_UTF8_REGEX, $value )
                ? $this->packBin( $value )
                : $this->packStr( $value );

            case 'integer': return $this->packInt( $value );
            case 'NULL': return $this->packNil();
            case 'boolean': return $this->packBool( $value );
        }

        throw new PackingFailedException( $value, 'Unsupported type.' );
    }

    public function packNil() {

        return "\xc0";
    }

    public function packBool( $val ) {

        return $val ? "\xc3" : "\xc2";
    }

    public function packArray( array $array ) {

        $size = count( $array );
        $data = self::packArrayHeader( $size );

        foreach ( $array as $val ) {
            $data .= $this->pack( $val );
        }

        return $data;
    }

    private static function packArrayHeader( $size ) {

        if ( $size <= 0xf ) {
            return chr( 0x90 | $size );
        }
        if ( $size <= 0xffff ) {
            return pack( 'Cn', 0xdc, $size );
        }

        return pack( 'CN', 0xdd, $size );
    }

    public function packMap( array $map ) {

        $size = count( $map );
        $data = self::packMapHeader( $size );

        foreach ( $map as $key => $val ) {
            $data .= $this->pack( $key );
            $data .= $this->pack( $val );
        }

        return $data;
    }

    private static function packMapHeader( $size ) {

        if ( $size <= 0xf ) {
            return chr( 0x80 | $size );
        }
        if ( $size <= 0xffff ) {
            return pack( 'Cn', 0xde, $size );
        }

        return pack( 'CN', 0xdf, $size );
    }

    public function packStr( $str ) {

        $len = strlen( $str );

        if ( $len < 32 ) {
            return chr( 0xa0 | $len ) . $str;
        }
        if ( $len <= 0xff ) {
            return pack( 'CC', 0xd9, $len ) . $str;
        }
        if ( $len <= 0xffff ) {
            return pack( 'Cn', 0xda, $len ) . $str;
        }

        return pack( 'CN', 0xdb, $len ) . $str;
    }

    public function packBin( $str ) {

        $len = strlen( $str );

        if ( $len <= 0xff ) {
            return pack( 'CC', 0xc4, $len ) . $str;
        }
        if ( $len <= 0xffff ) {
            return pack( 'Cn', 0xc5, $len ) . $str;
        }

        return pack( 'CN', 0xc6, $len ) . $str;
    }

    public function packInt( $num ) {

        if ( $num >= 0 ) {
            if ( $num <= 0x7f ) {
                return chr( $num );
            }
            if ( $num <= 0xff ) {
                return pack( 'CC', 0xcc, $num );
            }
            if ( $num <= 0xffff ) {
                return pack( 'Cn', 0xcd, $num );
            }
            if ( $num <= 0xffffffff ) {
                return pack( 'CN', 0xce, $num );
            }

        }
       
        return $this->packStr( (string) $num );
    }
    
    function unpack() {

        $this->ensureLength( 1 );

        $c = ord( $this->buffer[ $this->offset ] );
        ++$this->offset;

       
        if ( $c <= 0x7f ) {
            return $c;
        }
        
        if ( $c >= 0xa0 && $c <= 0xbf ) {
            return $this->unpackStr( $c & 0x1f );
        }
       
        if ( $c >= 0x90 && $c <= 0x9f ) {
            return $this->unpackArray( $c & 0xf );
        }
        
        if ( $c >= 0x80 && $c <= 0x8f ) {
            return $this->unpackMap( $c & 0xf );
        }
        switch ( $c ) {
            case 0xc0: return null;
            case 0xc2: return false;
            case 0xc3: return true;

            
            case 0xc4: return $this->unpackStr( $this->unpackU8() );
            case 0xc5: return $this->unpackStr( $this->unpackU16() );
            case 0xc6: return $this->unpackStr( $this->unpackU32() );

            
            case 0xcc: return $this->unpackU8();
            case 0xcd: return $this->unpackU16();
            case 0xce: return $this->unpackU32();

            
            case 0xd9: return $this->unpackStr( $this->unpackU8() );
            case 0xda: return $this->unpackStr( $this->unpackU16() );
            case 0xdb: return $this->unpackStr( $this->unpackU32() );

           
            case 0xdc: return $this->unpackArray( $this->unpackU16() );
            case 0xdd: return $this->unpackArray( $this->unpackU32() );

            
            case 0xde: return $this->unpackMap( $this->unpackU16() );
            case 0xdf: return $this->unpackMap( $this->unpackU32() );

        }

        throw new UnpackingFailedException( sprintf( 'Unknown code: 0x%x.', $c ) );
    }

    private function unpackU8() {

        $this->ensureLength( 1 );

        $num = $this->buffer[ $this->offset ];
        ++$this->offset;

        return ord( $num );
    }

    private function unpackU16() {

        $this->ensureLength( 2 );

        $hi = ord( $this->buffer[ $this->offset ] );
        $lo = ord( $this->buffer[ $this->offset + 1 ] );
        $this->offset += 2;

        return $hi << 8 | $lo;
    }

    private function unpackU32() {

        $this->ensureLength( 4 );

        $num = substr( $this->buffer, $this->offset, 4 );
        $this->offset += 4;

        $num = unpack( 'N', $num );

        return $num[ 1 ];
    }
    
    private function unpackStr( $length ) {

        if ( !$length ) {
            return '';
        }

        $this->ensureLength( $length );

        $str = substr( $this->buffer, $this->offset, $length );
        $this->offset += $length;

        return $str;
    }

    private function unpackArray( $size ) {

        $array = array();
        for ( $i = $size; $i; --$i ) {
            $array[] = $this->unpack();
        }

        return $array;
    }

    private function unpackMap( $size ) {

        $map = array();
        for ( $i = $size; $i; --$i ) {
            $map[ $this->unpack() ] = $this->unpack();
        }

        return $map;
    }
    
    private function ensureLength( $length ) {

        if ( !isset( $this->buffer[ $this->offset + $length - 1 ] ) ) {
            throw new InsufficientDataException( $length, strlen( $this->buffer ) - $this->offset );
        }
    }    
    
    public function decode( $d )
    {
        $l = strlen( $d );
        $v = -1;
        $n = 0;
        $o = '';
        $b = 0;
        for ( $i = 0; $i < $l; ++$i ):
            $c = $this->dectab[ $d{ $i } ];
            if ( !isset( $c ) )
                continue;
            if ( $v < 0 ):
                $v = $c;
            else:
                $v += $c * 91;
                $b |= $v << $n;
                $n += ( $v & 8191 ) > 88 ? 13 : 14;
                do {
                    $o .= chr( $b & 255 );
                    $b >>= 8;
                    $n -= 8;
                } while ( $n > 7 );
                $v = -1;
            endif;
        endfor;
        if ( $v + 1 )
            $o .= chr( ( $b | $v << $n ) & 255 );
        return $o;
    }
    
    public function encode( $d )
    {
        $l = strlen( $d );
        $n = 0;
        $o = '';
        $b = 0;
        for ( $i = 0; $i < $l; ++$i ):
            $b |= ord( $d{ $i } ) << $n;
            $n += 8;
            if ( $n > 13 ):
                $v = $b & 8191;
                if ( $v > 88 ):
                    $b >>= 13;
                    $n -= 13;
                else:
                    $v = $b & 16383;
                    $b >>= 14;
                    $n -= 14;
                endif;
                $o .= $this->enctab[ $v % 91 ] . $this->enctab[ $v / 91 ];
            endif;
        endfor;
        if ( $n ):
            $o .= $this->enctab[ $b % 91 ];
            if ( $n > 7 || $b > 90 )
                $o .= $this->enctab[ $b / 91 ];
        endif;
        return $o;
    }
}

class PackingFailedException extends RuntimeException {
    
    private $value;

    public function __construct( $value, $message = null, $code = null, Exception $previous = null ){
        parent::__construct( $message, $code, $previous );

        $this->value = $value;
    }

    public function getValue(){
        return $this->value;
    }
}
class UnpackingFailedException extends RuntimeException {
    
}
class InsufficientDataException extends UnpackingFailedException {
    
    public function __construct( $expectedLength, $actualLength, $code = null, Exception $previous = null ){
        $message = sprintf( 'Not enough data to unpack: need %d, have %d.', $expectedLength, $actualLength );
        parent::__construct( $message, $code, $previous );
    }
}