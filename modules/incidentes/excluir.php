<?php
/**
 * Exclusão de incidente via POST (com Prepared Statement).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('modules/incidentes/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $pdo = conectar_banco();
    $stmt = $pdo->prepare('DELETE FROM incidentes WHERE id = :id');
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        definir_flash('success', 'Incidente excluído com sucesso.');
    } else {
        definir_flash('warning', 'Incidente não encontrado.');
    }
}

redirecionar('modules/incidentes/index.php');
