<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
require_once __DIR__ . '/../../database.php';

class InquilinosController {
    
    // GET /inquilinos
    public function listar (Request $request, Response $response) {
       
        // Obtiene la conexión a la base de datos
            
        $connection = getConnection();
        try {  
             // Realiza la consulta SQL
             $query = $connection->query('SELECT * FROM inquilinos');
             // Obtiene los resultados de la consulta
             $tipos = $query->fetchAll(\PDO::FETCH_ASSOC);
             // Preparamos la respuesta json 
             $payload = codeResponseOk($tipos);
             // funcion que devulve y muestra la respuesta 
             return responseWrite($response, $payload);
         } catch (\PDOException $e) {
                // En caso de error, prepara una respuesta de error JSON
                $payload= codeRespondeBad();
                // devolvemos y mostramos la respuesta con el error.
                return responseWrite($response,$payload);
         }
     
    }
    // GET INQUILINOS/{ID}
    public function listarPorId (Request $request, Response $response, $args) {
       
        // Obtiene la conexión a la base de datos
            
        $connection = getConnection();
        try {  
             $id = $args['id'];
             // Realiza la consulta SQL
             $query = $connection->query("SELECT * FROM inquilinos WHERE id=$id");
             // Obtiene los resultados de la consulta
             $tipos = $query->fetchAll(\PDO::FETCH_ASSOC);
             // Preparamos la respuesta json 
             if($tipos) {
                 $payload = codeResponseOk($tipos);
                // funcion que devulve y muestra la respuesta 
                return responseWrite($response, $payload);
            } else {
                $payload = json_encode([
                    'status' => 'error',
                    'message' => 'No se encontró ningún inquilino con el ID proporcionado.'
                ]);
                // Devolver y mostrar la respuesta con el error
                return responseWrite($response, $payload);
            }
         } catch (\PDOException $e) {
                // En caso de error, prepara una respuesta de error JSON
                $payload= codeRespondeBad();
                // devolvemos y mostramos la respuesta con el error.
                return responseWrite($response,$payload);
         }
     
    }
     // GET inquilinos/{idInquilino}/reservas
    public function reservaPorId (Request $request, Response $response, $args) {
       
        // Obtiene la conexión a la base de datos
            
        $connection = getConnection();
        try {  
             $id = $args['id'];
             // Realiza la consulta SQL
             $query = $connection->query("SELECT * FROM reservas WHERE inquilino_id=$id");
             // Obtiene los resultados de la consulta
             $tipos = $query->fetchAll(\PDO::FETCH_ASSOC);
             // Preparamos la respuesta json 
             if($tipos) {
                 $payload = codeResponseOk($tipos);
                // funcion que devulve y muestra la respuesta 
                return responseWrite($response, $payload, 200);
            } else {
                $payload = json_encode([
                    'status' => 'error',
                    'message' => 'No se encontró ninguna reserva con el ID proporcionado.'
                ]);
                // Devolver y mostrar la respuesta con el error
                return responseWrite($response, $payload, 400);
            }
         } catch (\PDOException $e) {
                // En caso de error, prepara una respuesta de error JSON
                $payload= codeRespondeBad();
                // devolvemos y mostramos la respuesta con el error.
                return responseWrite($response,$payload);
         }
     
    }
    // DELETE inquilinos/{id}
    public function eliminarPorId (Request $request, Response $response, $args) {
       
        // Obtiene la conexión a la base de datos
        $connection = getConnection(); 
       
        try {    
             $id = $args['id'];
             // Realiza la consulta SQL
             $query = $connection->prepare("DELETE FROM inquilinos WHERE id = :id");
             $query -> bindParam (':id', $id, \PDO::PARAM_INT);
             $query-> execute();
             $filas_delete= $query->rowCount();
             // Preparamos la respuesta json 
             if($filas_delete>0) {
                 $payload = json_encode([
                    'status' => 'success',
                    'code: ' => 200,
                    'message' => 'INQUILINO BORRADO EXITOSAMENTE'
                ]);
                // funcion que devulve y muestra la respuesta 
                return responseWrite($response, $payload);
            } else {
                $payload = json_encode([
                    'status' => 'error',
                    'message' => 'No se encontró ninguna inquilino con el ID proporcionado.'
                ]);
                // Devolver y mostrar la respuesta con el error
                return responseWrite($response, $payload);
            }
         } catch (\PDOException $e) {
                // En caso de error, prepara una respuesta de error JSON
                $payload= codeResponseBad();
                // devolvemos y mostramos la respuesta con el error.
                return responseWrite($response,$payload);
         }
     
    }
}
?>