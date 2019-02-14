<?php
final class app {
    private $config = false;

    public function run() {
        $this->includes();
        $this->router();
    }

    private function includes() {
        include_once( __DIR__ . '/../classes/mRouter.class.php' );
        include_once( __DIR__ . '/../classes/view.class.php' );
    }

    private function router() {
        $router = new mRouter();
        
        // Include routes
        $files = scandir( __DIR__ . '/../routes' );

        foreach( $files as $file ) {
            if( $file == '.' || $file == '..' ) {
                continue;
            }
            
            include_once( __DIR__ . '/../routes/' .  $file );
        }

        
        $router->open();
    }

    public function config( String $id ) {
        $this->readConfig();

        if( !isset( $this->config[ $id ] ) ) {
            return;
        }

        return $this->config[ $id ];
    }

    private function readConfig() {
        if( $this->config !== false ) {
            return;
        }

        $this->config = array();

        $handle = fopen( __DIR__ . '/../.config', 'r' );
        if( !$handle ) {
            return false;
        }

        while( ( $line = fgets( $handle ) ) !== false ) {
            if( strpos( $line, '#' ) === 0 ) {
                continue;
            }
            if( strpos( $line, '=' ) === false ) {
                continue;
            }

            list( $key, $value ) = explode( '=', $line, 2 );

            $key = trim( $key );
            $value  = trim( $value );
            
            $this->config[ $key ] = $value;
        }

        fclose( $handle ); 
    }
}