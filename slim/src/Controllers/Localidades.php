<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../../database.php';

class LocalidadesController {

    // GET /localidades
    public function listar(Request $request,Response $response) {
        $connection= getConnection (); 
        try {
            $query=$connection->query('SELECT * FROM localidades'); 
            $datos= $query->fetchAll(\PDO::FETCH_ASSOC);
            if($datos){
                $payload= codeResponseOk($datos);
                return responseWrite($response,$payload);
            }     
        } catch (\PDOException $e) {
            $payload=codeResponseBad();
            return responseWrite($response,$payload);
        }

    }
    // PUT /localidades/{id}
    public function editarLocalidad(Request $request, Response $response, $args ) {
        $id=$args['id'];
        if(!is_numeric($id)) {
            $status='Error'; $mensaje='ID no valido'; 
            $payload=codeResponseGeneric($status,$mensaje,400);
            return responseWrite($response,$payload);
        }
        $connection=getConnection();
        try{
            $query= $connection->query("SELECT id FROM localidades WHERE id=$id LIMIT 1");
            if($query->rowCount() ==0){
                $status='ERROR'; $mensaje='No se encuntra el ID'; $payload=codeResponseGeneric($status,$mensaje,404);
                return responseWrite($response,$payload);
            }
            $data=$request->getParsedBody();
            if(isset($data['nombre'])) {
                $localidad=$data['nombre'];
                var_dump($localidad);
                $query = $connection->prepare("SELECT nombre FROM localidades WHERE nombre = :localidad");
                $query->bindValue(':localidad', $localidad,);
                $query->execute();
                if($query->rowCount()>0){
                    $status='Error'; $mensaje='La localidad ya se encuentra en la base de datos';
                    $payload=codeResponseGeneric($status,$mensaje,400);
                    return responseWrite($response,$payload);
                }
                var_dump($localidad);
                $query=$connection->prepare("UPDATE localidades set nombre=:localidad WHERE id=$id");
                $query->bindValue(':localidad',$localidad);
                $query->execute();
                $status='Success';$mensaje='Localidad editada correctamente';
                $payload=codeResponseGeneric($status,$mensaje,200);
                return responseWrite($response,$payload);
            } else {
                $status = 'Error';
                $mensaje = 'El campo nombre es requerido';
                $payload = codeResponseGeneric($status, $mensaje, 400);
                return responseWrite($response, $payload);
            }
        }catch (\PDOException $e) {
            $payload=codeResponseBad();
            return responseWrite($response,$payload);
        }
    }
    

}









?>