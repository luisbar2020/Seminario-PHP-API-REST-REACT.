<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
require_once __DIR__ . '/../../database.php';

class TipoPropiedadesController {
    
    // GET /tipos_propiedad
    public function listar (Request $request, Response $response) {
       
        // Obtiene la conexión a la base de datos
            
        $connection = getConnection();
        try {
             // Realiza la consulta SQL
             $query = $connection->query('SELECT nombre FROM tipo_propiedades');
             // Obtiene los resultados de la consulta
             $tipos = $query->fetchAll(\PDO::FETCH_ASSOC);
             // Preparamos la respuesta json 
             $payload = codeResponseOk($tipos);
             // funcion que devulve y muestra la respuesta 
             return responseWrite($response,$payload);
         } catch (\PDOException $e) {
                // En caso de error, prepara una respuesta de error JSON
                $payload= codeRespondeBad();
                // devolvemos y mostramos la respuesta con el error.
                return responseWrite($response,$payload);
         }
     
    }
    // DELETE /tipos_propiedad/{id}
    public function eliminarTipoPropiedad (Request $request, Response $response, $args) {
        $connection= getConnection();
        try { 
            $id= $args['id'];
            if (!is_numeric($id)) {
                $status='Error'; $mensaje='ID NO VALIDO';
                $payload=codeResponseGeneric($status,$mensaje,400);
                return responseWrite($response,$payload);
            }
            $query= $connection ->query("SELECT id FROM tipo_propiedades WHERE id=$id");
            if($query->rowCount() > 0) {
                $query= $connection -> query("SELECT tipo_propiedad_id FROM propiedades WHERE tipo_propiedad_id=$id LIMIT 1");
                if($query -> rowCount()>0) {
                    $status='error';   $mensaje='Esta localidad está siendo usada.';
                    $payload= codeResponseGeneric($status,$mensaje,400);
                    return responseWrite($response,$payload);
                } else {
                    $query= $connection ->prepare("DELETE from tipo_propiedades WHERE id=:id ");
                    $query -> bindParam(':id', $id, \PDO::PARAM_INT);
                    $query->execute();
                    $status='Success';   $mensaje='Eliminado exitosamente';
                    $payload= codeResponseGeneric($status,$mensaje,200);
                    return responseWrite($response,$payload);
                }

            } else {
                $status='ERROR'; $mensaje='No se encuentra el ID';
                $payload= codeResponseGeneric($status,$mensaje,400);
                return responseWrite($response,$payload);
            }
        } 
        catch(\PDOException $e) {
            $payload= codeResponseBad();
            // devolvemos y mostramos la respuesta con el error.
            return responseWrite($response,$payload);
        }
    }
}
?>