<?php
$router->get( '', function( $response ) {
    $view = new view();
    $view->open( 'index', array(
        'id' => 'Test'
    ));
});

$router->get( 'post/{id}', function( $response ) {
    $id = $response->getParameter( 'id' );
    echo $id;
});

$router->status( '404', function() {
    echo "404";
});