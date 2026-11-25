<?php
/**
 * Encerra a sessão do usuário de forma segura e redireciona para o login.
 */

require_once __DIR__ . '/../../config/auth.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Inicia nova sessão limpa apenas para exibir a mensagem de feedback na tela de login
session_start();
definir_flash('info', 'Sessão encerrada com sucesso.');
redirecionar('modules/auth/login.php');
