<?php
/**
 * Exclusão de vulnerabilidade via POST.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

// Aceita exclusão apenas via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('modules/vulnerabilidades/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

// Remove a vulnerabilidade pelo ID com Prepared Statement
if ($id > 0) {
    $pdo = conectar_banco();
    $stmt = $pdo->prepare('DELETE FROM vulnerabilidades WHERE id = :id');
    $stmt->execute([':id' => $id]);
    definir_flash('success', 'Vulnerabilidade excluída com sucesso.');
}

redirecionar('modules/vulnerabilidades/index.php');
