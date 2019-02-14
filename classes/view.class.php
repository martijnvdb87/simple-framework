<?php
class view {
    const VIEWS = __DIR__ . '/../views\\';
    const PARTIALS = __DIR__ . '/../partials\\';
    
    private $params = array();
    private $config = array();

    public function open( String $path, Array $params = array() ) {
        $path = str_replace( '.', '\\', $path );

        if( file_exists( self::VIEWS . $path . '.php' ) ) {
            $this->set( $params );
            include( self::VIEWS . $path . '.php' );
        }
    }

    public function set( Array $params = array() ) {
        $this->params = array_merge( $this->params, $params );
    }

    public function get( String $id ) {
        if( !isset( $this->params[ $id ] ) ) {
            return;
        }

        return $this->params[ $id ];
    }

    public function echo( String $id ) {
        echo $this->get( $id );
    }

    public function part( String $path ) {
        $path = str_replace( '.', '\\', $path );

        if( file_exists( self::PARTIALS . $path . '.php' ) ) {
            include( self::PARTIALS . $path . '.php' );
        }
    }

    // Alias 'part' methode
    public function partial( String $path ) {
        $this->part( $path );
    }

    public function config( String $id ) {
        global $app;
        return $app->config( $id );
    }
}