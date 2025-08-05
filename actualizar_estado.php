<?php 
session_start();
date_default_timezone_set('America/Santo_Domingo');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit();
}

$serverName = "sdb-apptransportistas-maco.database.windows.net";
$database = "db-apptransportistas-maco";
$username = "ServiceAppTrans";
$password = "⁠nZ(#n41LJm)iLmJP";

$connectionInfo = array(
    "Database" => $database,
    "UID" => $username,
    "PWD" => $password,
    "TrustServerCertificate" => true
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . print_r(sqlsrv_errors(), true)]);
    exit();
}

$factura = $_POST['factura'] ?? null;
$nuevoEstado = $_POST['nuevoEstado'] ?? null;

if (!$factura || !$nuevoEstado) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
    exit();
}

$sql = "UPDATE custinvoicejour SET Validar = ? WHERE Factura = ?";
$params = [$nuevoEstado, $factura];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al actualizar el estado: ' . print_r(sqlsrv_errors(), true)
    ]);
    exit();
}

$rowsAffected = sqlsrv_rows_affected($stmt);
if ($rowsAffected === false) {
    echo json_encode(['success' => false, 'error' => 'No se pudo obtener el número de filas afectadas.']);
    exit();
} elseif ($rowsAffected === 0) {
    echo json_encode(['success' => false, 'error' => 'No se actualizó ninguna fila.']);
    exit();
}

echo json_encode(['success' => true]);

sqlsrv_close($conn);
