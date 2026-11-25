<?php
/**
 * Encerra a sessão do usuário de forma segura e redireciona para o login.
 */

require_once __DIR__ . '/../../config/auth.php';

// 1. Limpa todas as variáveis armazenadas em memória na sessão atual
$_SESSION = [];

// 2. Expira o cookie de sessão no navegador (definindo validade no passado)
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

// 3. Destrói o arquivo/registro de sessão no servidor
session_destroy();

// 4. Inicia nova sessão limpa apenas para exibir a mensagem de feedback na tela de login
session_start();
definir_flash('info', 'Sessão encerrada com sucesso.');
redirecionar('modules/auth/login.php');
