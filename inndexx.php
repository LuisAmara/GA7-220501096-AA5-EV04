<?php

$host = "localhost";
$usuario = "root";
$password = "";
$basededatos = "api";

// Conexión a la base de datos
$conn = mysqli_connect($host, $usuario, $password, $basededatos);

// Chequear la conexión
if (!$conn) {
    die("Conexión fallida: " . mysqli_connect_error());
}

// Establecer el encabezado de respuesta como JSON
header("Content-Type: application/json");

// Obtener el método de la solicitud
$metodo = $_SERVER["REQUEST_METHOD"];

// Obtener el id de la URL para los métodos GET, PUT y DELETE
$id = isset($_GET['id']) ? $_GET['id'] : null;  // Por ejemplo, ?id=1

// Manejar la solicitud según el método
switch ($metodo) {
    case 'GET':
        // Consultar usuarios
        consultar($conn, $id);
        break;
    case 'POST':
        // Insertar un nuevo usuario
        insertar($conn);
        break;
    case 'PUT':
        // Actualizar un usuario existente
        actualizar($conn, $id);
        break;
    case 'DELETE':
        // Borrar un usuario existente
        borrar($conn, $id);
        break;
    default:
        // Método no permitido
        echo json_encode(array("error" => "Método no permitido"));
        break;
}

// Función para consultar usuarios
function consultar($conn, $id)
{
    if ($id !== null) {
        // Consulta con parámetro preparado
        $sql = "SELECT * FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        $sql = "SELECT * FROM usuarios";
        $resultado = $conn->query($sql);
    }

    if ($resultado) {
        $datos = array();
        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }
        echo json_encode($datos);
    } else {
        echo json_encode(array("error" => "Error al consultar usuarios: " . mysqli_error($conn)));
    }
}

// Función para insertar un usuario
function insertar($conn)
{
    $dato = json_decode(file_get_contents("php://input"), true);
    $id = isset($dato["id"]) ? (int)$dato["id"] : null;
    $nombre = isset($dato["nombre"]) ? mysqli_real_escape_string($conn, $dato["nombre"]) : '';

    if ($id !== null && $nombre !== '') {
        $sql = "INSERT INTO usuarios(id, nombre) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $id, $nombre);
        
        if ($stmt->execute()) {
            echo json_encode(array("id" => $id));
        } else {
            echo json_encode(array("error" => "Error al insertar el usuario: " . mysqli_error($conn)));
        }
    } else {
        echo json_encode(array("error" => "ID o nombre de usuario no proporcionado"));
    }
}

// Función para actualizar un usuario
function actualizar($conn, $id)
{
    // Obtener los datos del cuerpo de la solicitud en formato JSON
    $data = json_decode(file_get_contents("php://input"), true);

    // Verificar si se proporcionó el ID y los nuevos datos
    if (isset($data['nuevosDatos'])) {
        $nuevosDatos = $data['nuevosDatos'];

        // Construir la consulta SQL para actualizar los datos del usuario
        $sets = [];
        foreach ($nuevosDatos as $campo => $valor) {
            $sets[] = "$campo = ?";
        }
        $setString = implode(', ', $sets);
        $sql = "UPDATE usuarios SET $setString WHERE id = ?";

        // Preparar la consulta
        $stmt = $conn->prepare($sql);
        
        // Generar los tipos de datos para la consulta
        $types = str_repeat('s', count($nuevosDatos)) . 'i';
        $params = array_merge(array_values($nuevosDatos), array($id));

        // Vincular los parámetros
        $stmt->bind_param($types, ...$params);

        // Ejecutar la consulta SQL
        if ($stmt->execute()) {
            echo json_encode(array("mensaje" => "Usuario actualizado correctamente"));
        } else {
            echo json_encode(array("error" => "Error al actualizar el usuario: " . mysqli_error($conn)));
        }
    } else {
        echo json_encode(array("error" => "ID o nuevos datos no proporcionados"));
    }
}

// Función para borrar un usuario
function borrar($conn, $id)
{
    if ($id !== null) {
        // Consulta con parámetro preparado
        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(array("Mensaje" => "Usuario borrado correctamente"));
        } else {
            echo json_encode(array("error" => "Error al borrar el usuario"));
        }
    } else {
        echo json_encode(array("error" => "ID de usuario no proporcionado"));
    }
}

?>
