<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

// Conexión a la base de datos
function getConnection() {
    $dbhost = "localhost";
    $dbname = "seminariophp";
    $dbuser = "root";
    $dbpass = "root";
    $connection = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $connection;
}

$app->get('/tipos_propiedad/listar', function(Request $request, Response $response) {
    $connection = getConnection(); // Obtiene la base de datos 
    try {
        $query = $connection->query('SELECT nombre FROM tipo_propiedades');
        $tipos = $query->fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $tipos
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
$app->addErrorMiddleware(true, true, true);
$app->run();
