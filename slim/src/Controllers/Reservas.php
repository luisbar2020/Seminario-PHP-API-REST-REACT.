<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
require_once __DIR__ . '/../../utilidades.php';


class ReservasController {
    private function obtenerValorPropiedadPorNoche($propiedadId) {
        $connection = getConnection();
        $sql = "SELECT valor_noche FROM propiedades WHERE id = :id";
        $query = $connection->prepare($sql);
        $query->execute([':id' => $propiedadId]);
        $result = $query->fetch();
        return $result ? $result['valor_noche'] : false;
    }

    // POST
    public function agregarReserva(Request $request, Response $response) {
        $connection = getConnection();
        $data = $request->getParsedBody(); // Obtener los datos enviados en el cuerpo de la solicitud
        // Verificar que todos los datos requeridos estén presentes
        $requiredFields = ['propiedad_id', 'inquilino_id', 'fecha_desde', 'cantidad_noches'];
        $payload=faltanDatos($requiredFields,$data);
        if (isset($payload)) {
            return responseWrite($response,$payload);
        }

        $valid = true;

        // Verificar si el inquilino está Activo
        function inquilinoActivo($inquilinoId) {
            $connection = getConnection();
            $sql = "SELECT * FROM inquilinos WHERE id = :id AND activo = 1";
            $query = $connection->prepare($sql);
            $query->execute([':id' => $inquilinoId]);
            $result = $query->fetch();
            return $result ? true : false;
        }

        if (!inquilinoActivo($data['inquilino_id'])) {
            $payload = codeResponseGeneric("El inquilino especificado no está activo.", 400, "Bad Request");
            return responseWrite($response, $payload, 400);
        }

        // Verificar si la propiedad está disponible
        function propiedadDisponible($propiedadId) {
            $connection = getConnection();
            $fechaActual = date('Y-m-d');
            $sql = "SELECT * FROM reservas WHERE propiedad_id = :propiedad_id ";
            $query = $connection->prepare($sql);
            $query->execute([':propiedad_id' => $propiedadId]);
            $result = $query->fetch();
            return $result ? false : true;
        }

        if (!propiedadDisponible($data['propiedad_id'])) {
            $payload = codeResponseGeneric("La propiedad especificada no está disponible.", 400, "Bad Request");
            return responseWrite($response, $payload, 400);
        }

        // // Verificar si la fecha de inicio es válida (fecha_desde es menor a la fecha actual)
        // $fecha_desde = strtotime($data['fecha_desde']);
        // if ($fecha_desde === false || $fecha_desde < time()) {
        //     $payload = codeResponseGeneric("La fecha de inicio de la reserva debe ser una fecha futura válida.", 400, "Bad Request");
        //     return responseWrite($response, $payload, 400);
        // }

        $valor_por_noche = obtenerValorPropiedadPorNoche($data['propiedad_id']);
        if ($valor_por_noche === false) {
            $payload = codeResponseGeneric("No se pudo obtener el valor por noche de la propiedad.", 500, "Internal Server Error");
            return responseWrite($response, $payload, 500);
        }

        $valor_total = $valor_por_noche * $data['cantidad_noches'];

        // Verificar si alguno de los datos no es válido
        if (
            !inquilinoActivo($data['inquilino_id']) ||
            !propiedadDisponible($data['propiedad_id']) ||
            $fecha_desde === false ||
            $fecha_desde < time() ||
            $valor_por_noche === false
        ) {
            $valid = false;
        }

        // Insertar la reserva en la base de datos solo si todos los datos son válidos
        if ($valid) {
            // Insertar en la base de datos
            $sql = "INSERT INTO reservas (propiedad_id, inquilino_id, fecha_desde, cantidad_noches, valor_total) 
                    VALUES (:propiedad_id, :inquilino_id, :fecha_desde, :cantidad_noches, :valor_total)";
            $values = [
                ':propiedad_id' => $data['propiedad_id'],
                ':inquilino_id' => $data['inquilino_id'],
                ':fecha_desde' => $data['fecha_desde'],
                ':cantidad_noches' => $data['cantidad_noches'],
                ':valor_total' => $valor_total
            ];

            try {
                $query = $connection->prepare($sql);
                $query->execute($values);
                $payload = codeResponseGeneric('Reserva creada correctamente.', 201, "Created");
                return responseWrite($response, $payload, 201);
            } catch (\PDOException $e) {
                $payload = codeResponseGeneric('Error al crear la reserva.', 500, "Internal Server Error");
                return responseWrite($response, $payload, 500);
            }
        } else {
            $payload = codeResponseGeneric('No se pudo crear la reserva debido a datos inválidos.', 400, "Bad Request");
            return responseWrite($response, $payload, 400);
        }
    }

    //GET

    public function listar (Request $request, Response $response) {
    
        // Obtiene la conexión a la base de datos
            
        $connection = getConnection();
        try {  
             // Realiza la consulta SQL
            $query = $connection->query('SELECT * FROM reservas');
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
    
    // DELETE 
    public function eliminarReserva (Request $request, Response $response, $args){
        $id = $args['id'];
        if (!is_numeric($id)) {
                $status = 'Error';
                $mensaje = 'ID NO VALIDO';
                $payload = codeResponseGeneric($status, $mensaje, 400);
                return responseWrite($response, $payload);
        }
        try {
            $connection=getConnection();
            $query= $connection ->query("SELECT id,fecha_desde FROM reservas WHERE id=$id LIMIT 1");
            if($query->rowCount()>0){
                $reserva= $query->fetch(\PDO::FETCH_ASSOC);
                $fecha_incio= $reserva['fecha_desde']; 
                $fecha_actual= date('Y-m-d');
                if($fecha_actual<$fecha_incio){
                    $query=$connection->prepare('DELETE FROM reservas WHERE id=:id');
                    $query->bindValue(':id',$id);   
                    $query->execute();
                    $mensaje='Eliminado correctamente.'; $status="Success"; $payload=codeResponseGeneric($status,$mensaje,200);
                    return responseWrite($response,$payload);
                } else {
                    $mensaje='La reserva ya inicio.'; $status='Error'; 
                    $payload=codeResponseGeneric($status,$mensaje,400);
                    return responseWrite($response,$payload);
                }
            } else {
                $status='ERROR'; $mensaje='No se encuentra la reserva con el ID proporcionado';
                $payload= codeResponseGeneric($status,$mensaje,400);
                return responseWrite($response,$payload);
            }

        }
        catch (\PDOException $e){
             $payload= codeRespondeBad();
             return responseWrite($response,$payload);
        }
    }
    //PUT
    public function editarReserva(Request $request, Response $response, $args) {
        $connection = getConnection();
        $data = $request->getParsedBody(); // Obtener los datos enviados en el cuerpo de la solicitud
        // Obtener el ID de la reserva de los argumentos de la URL
        $id = $args['id'];
        // Verificar si el ID es numérico
        if (!is_numeric($id)) {
            $errors[] = "ID no válido.";
        }

        // Verificar que todos los datos requeridos estén presentes
        $requiredFields = ['propiedad_id', 'inquilino_id', 'fecha_desde', 'cantidad_noches'];
        $payload=faltanDatos($requiredFields,$data);
        if (isset($payload)) {
            return responseWrite($response,$payload);
        }

        if (!empty($errors)) {
            $payload = codeResponseGeneric("Error en los datos proporcionados.", ['errors' => $errors], 400);
            return responseWrite($response, $payload);
        }

        // Verificar si la fecha de inicio ya pasó
        try {
            $query = $connection->query("SELECT fecha_desde FROM reservas WHERE id=$id LIMIT 1");
            if ($query->rowCount() > 0) {
                $reserva = $query->fetch(\PDO::FETCH_ASSOC);
                $fechaInicio = $reserva['fecha_desde'];
                $fechaActual = date('Y-m-d');

                if ($fechaActual < $fechaInicio) {
                    // La reserva aún no ha comenzado, se permite la edición
                    
                    // Verificar si el inquilino existe
                    $queryInquilino = $connection->prepare("SELECT * FROM inquilinos WHERE id = :inquilino_id");
                    $queryInquilino->execute([':inquilino_id' => $data['inquilino_id']]);
                    $resultInquilino = $queryInquilino->fetch();
                    if (!$resultInquilino) {
                        // Si no se encontró ningún resultado, el inquilino no existe
                        $errores[] = "No existe ese inquilino";
                    } else if ($resultInquilino['activo'] === 0) {
                        // Si el inquilino está inactivo
                        $errores[] = "El inquilino especificado no está activo";
                    }
                    
                    // Verificar si la propiedad existe
                    $queryPropiedad = $connection->prepare("SELECT * FROM propiedades WHERE id = :propiedad_id");
                    $queryPropiedad->execute([':propiedad_id' => $data['propiedad_id']]);
                    $resultPropiedad = $queryPropiedad->fetch();
                    if (!$resultPropiedad) {
                        $errores[] = "No existe esa propiedad";
                    } else if ($resultPropiedad['disponible'] === 0) {
                        $errores[] = "La propiedad especificada no está activa";
                    }

                    // Si hay errores, devolverlos
                    if (!empty($errores)) {
                        $payload = codeResponseGeneric("Error en los datos proporcionados.",  $errores,400);
                        return responseWrite($response, $payload);
                    }

                    // Actualizar la reserva en la base de datos
                    $sql = "UPDATE reservas 
                            SET propiedad_id = :propiedad_id, 
                                inquilino_id = :inquilino_id, 
                                fecha_desde = :fecha_desde, 
                                cantidad_noches = :cantidad_noches,
                                valor_total = :valor_total
                            WHERE id = :id";

                    $valor_por_noche = obtenerValorPropiedadPorNoche($data['propiedad_id']);
                
                    $valor_total = $valor_por_noche * $data['cantidad_noches'];

                    $values = [
                        ':id' => $id,
                        ':propiedad_id' => $data['propiedad_id'],
                        ':inquilino_id' => $data['inquilino_id'],
                        ':fecha_desde' => $data['fecha_desde'],
                        ':cantidad_noches' => $data['cantidad_noches'],
                        ':valor_total' => $valor_total,
                    ];

                    try {
                        $query = $connection->prepare($sql);
                        $query->execute($values);
                        $payload = codeResponseGeneric("Reserva actualizada correctamente.", 200, "OK");
                        return responseWrite($response, $payload);
                    } catch (\PDOException $e) {
                        $payload = codeResponseGeneric("Error al actualizar la reserva.", 500, "Internal Server Error");
                        return responseWrite($response, $payload, 500);
                    }
                } else {
                    // La reserva ya ha comenzado, no se permite la edición
                    $payload = codeResponseGeneric("La reserva ya ha comenzado y no puede ser editada.", 400, "Bad Request");
                    return responseWrite($response, $payload, 400);
                }
            } else {
                // No se encuentra la reserva con el ID proporcionado
                $payload = codeResponseGeneric("No se encuentra la reserva con el ID proporcionado.", 404, "Not Found");
                return responseWrite($response, $payload, 404);
            }
        } catch (\PDOException $e) {
            // Error de base de datos
            $payload = codeResponseGeneric("Error de base de datos al buscar la reserva.", 500, "Internal Server Error");
            return responseWrite($response, $payload, 500);
        }
    }
}