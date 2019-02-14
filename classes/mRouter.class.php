<?php
class mRouter {
    private $url        = '';
    private $status     = 404;
    private $current    = false;
    private $routes     = array();
    private $path       = false;
    private $protocol   = false;

    public function get( $url, $callback ) {
        $this->addRoute( 'get', $url, $callback );
    }

    public function post( $url, $callback ) {
        $this->addRoute( 'post', $url, $callback );
    }

    public function put( $url, $callback ) {
        $this->addRoute( 'put', $url, $callback );
    }

    public function patch( $url, $callback ) {
        $this->addRoute( 'patch', $url, $callback );
    }

    public function delete( $url, $callback ) {
        $this->addRoute( 'delete', $url, $callback );
    }

    public function status( $code, $callback ) {
        $this->addStatus( $code, $callback );
    }

    public function open( $url = false, $method = false ) {
        if( $method === false ) {
            $method = strtolower( $_SERVER[ 'REQUEST_METHOD' ] );
        }

        if( $url === false ) {
            $path_self  = pathinfo( $_SERVER[ 'PHP_SELF' ] );
            $dirname    = $path_self[ 'dirname' ];
            $this->path = '//' . $_SERVER[ 'HTTP_HOST' ] . $dirname . '/';

            if(
                isset( $_SERVER[ 'HTTPS' ] ) && (
                    $_SERVER[ 'HTTPS' ] == 'on' ||
                    $_SERVER[ 'HTTPS' ] == 1
                ) ||
                isset( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ) &&
                $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] == 'https'
            ) {
                $this->protocol = 'https';
            } else {
                $this->protocol = 'http';
            }

            $url = $_SERVER[ 'REQUEST_URI' ];
            $url = strtolower( $url );
    
            if( substr( $url, 0, strlen( $dirname ) ) == $dirname ) {
                $url = substr( $url, strlen( $dirname ) );
            }
        }

        $urlParts = explode( '?', $url );

        if( sizeof( $urlParts ) > 1 ) {
            $url = $urlParts[ 0 ];
        }

        if( $url[ 0 ] != '/' ) {
            $url = '/' . $url;
        }

        if( $url[ ( strlen( $url ) - 1 ) ] != '/' ) {
            $url = $url . '/';
        }

        if( $url === '/' ) {
            $url = '//';
        }

        switch( $method ) {
            case 'get':
            case 'post':
            case 'put':
            case 'patch':
            case 'delete':
                break;

            default:
                $method = false;
                break;
        }

        if( $method === false ) {
            return false;
        }

        $this->url = $url;

        if( isset( $this->routes[ $method ] ) ) {
            for( $i = 0; $i < sizeof( $this->routes[ $method ] ); $i++ ) {
                if( fnmatch( $this->routes[ $method ][ $i ][ 'pattern' ], $url, FNM_PATHNAME ) ) {
                    $this->status = 200;
                    $this->current = $this->routes[ $method ][ $i ];
                    break;
                }
            }
        }

        $status = $this->status . '';
        
        http_response_code( $this->status );

        if(
            isset( $this->routes[ 'status' ] ) &&
            isset( $this->routes[ 'status' ][ $status ] ) &&
            isset( $this->routes[ 'status' ][ $status ][ 'callback' ] ) &&
            is_callable( $this->routes[ 'status' ][ $status ][ 'callback' ] )
        ) {
            $this->routes[ 'status' ][ $status ][ 'callback' ]();
        }

        if(
            $this->current &&
            $this->current[ 'callback' ] &&
            is_callable( $this->current[ 'callback' ] )
        ) {
            $response = new mRouterResponse( $this->url, $this->current );
            $this->current[ 'callback' ]( $response );
        }
    }

    private function addRoute( $method, $url, $callback ) {
        if( !isset( $this->routes[ $method ] ) ) {
            $this->routes[ $method ] = array();
        }

        $url = '/' . trim( $url, '/' ) . '/';

        $pattern = preg_replace( '/{.+?}/', '*', $url );

        array_push( $this->routes[ $method ], array(
            'url'       => $url,
            'pattern'   => $pattern,
            'callback'  => $callback
        ) );
    }

    private function addStatus( $code, $callback ) {
        if( !isset( $this->routes[ 'status' ] ) ) {
            $this->routes[ 'status' ] = array();
        }

        $this->routes[ 'status' ][ $code ] = array(
            'code'      => $code,
            'callback'  => $callback
        );
    }
}

class mRouterResponse {
    private $url        = false;
    private $current    = false;
    private $parameters = array();
    private $paramScan  = false;

    public function __construct( $url, $current ) {
        $this->url      = $url;
        $this->current  = $current;
    }

    public function getParameter( $id ) {
        if( !$this->paramScan ) {
            $this->paramScan = true;

            $pattern    = $this->current[ 'pattern' ];
            $url        = $this->current[ 'url' ];
            $pageUrl    = $this->url;

            $partsPattern   = explode( '/', $pattern );
            $partsUrl       = explode( '/', $url );
            $partsPageUrl   = explode( '/', $pageUrl );

            for( $i = 0; $i < sizeof( $partsPattern ); $i++ ) {
                if( strpos( $partsPattern[ $i ], '*' ) !== false ) {
                    $p = explode( '*', $partsPattern[ $i ] );

                    if( sizeof( $p ) != 2 ) {
                        return false;
                    }

                    $key    = $partsUrl[ $i ];
                    $key    = ltrim( $key, $p[ 0 ] );
                    $key    = rtrim( $key, $p[ 1 ] );
                    $key    = preg_replace( '/[^A-Za-z0-9]/', '', $key );

                    $value  = $partsPageUrl[ $i ];
                    $value  = ltrim( $value, $p[ 0 ] );
                    $value  = rtrim( $value, $p[ 1 ] );
                    $value  = preg_replace( '/[^A-Za-z0-9]/', '', $value );

                    $this->parameters[ $key ] = $value;
                }
            }
        }

        if( isset( $this->parameters[ $id ] ) ) {
            return $this->parameters[ $id ];
        }
        
        return false;
    }
}