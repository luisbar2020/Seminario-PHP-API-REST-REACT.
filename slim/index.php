<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Controllers\TipoPropiedadesController;
use App\Controllers\InquilinosController;


require_once __DIR__ . '/src/Controllers/TipoPropiedades.php';
require_once __DIR__ . '/src/Controllers/Inquilinos.php';
require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add( function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, PATCH, DELETE')
        ->withHeader('Content-Type', 'application/json')
    ;
});

// ACÁ VAN LOS ENDPOINTS


$app->get('/',function(Request $request,Response $response,$args){
    $response->getBody()->write('Hola mundo!!');
    return $response->withHeader('Content-Type', 'application/json');
});



$app->get('/tipos_propiedad', TipoPropiedadesController::class. ':listar');
$app->post('/tipos_propiedad',TipoPropiedadesController::class. ':crearTipoPropiedad');
$app->delete('/tipos_propiedad/{id}',TipoPropiedadesController::class . ':eliminarTipoPropiedad');
$app->get('/inquilinos/listar', InquilinosController::class. ':listar');
$app->get('/inquilinos/listar/{id}', InquilinosController::class .':listarPorId');
$app->get('/inquilinos/{id}/reservas', InquilinosController::class. ':reservaPorId');
$app->delete('/inquilinos/eliminar/{id}', InquilinosController::class. ':eliminarPorId');



























$app->delete('/tipos', function(Request $request, Response $response , $args ) {
    $connection= getConnection(); //conexion a la DB 
    
    try {
        $query = $connection->prepare("DELETE FROM tipo_propiedades WHERE id = :id");
        $query->execute(['id' => $id]);

        $payload = json_encode([
            'status' => 'success',
            'code' => 200
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $payload = json_encode([
            'status' => 'error',
            'code' => 400
        ]);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}); 

$app->run(); 
