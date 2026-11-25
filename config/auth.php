<?php
/**
 * Middleware de autenticação, controle de sessão e funções auxiliares globais.
 */

// Inicia a sessão PHP apenas se ainda não estiver ativa nesta requisição
if (session_status() === PHP_SESSION_NONE) {
    // Configurações de segurança para o cookie de sessão:
    // - httponly: impede acesso ao cookie via JavaScript (mitiga roubo de sessão por XSS)
    // - samesite=Lax: protege contra envio indevido do cookie em requisições cross-site (CSRF)
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Calcula automaticamente a URL base do projeto, funcionando tanto na raiz
 * quanto em subpastas do htdocs (ex.: /mini-soc ou /Trabalho-PHP).
 */
function base_url(string $caminho = ''): string
{
    static $prefixo = null;

    if ($prefixo === null) {
        // Compara o caminho físico da raiz do projeto com o DOCUMENT_ROOT do Apache
        $raizProjeto = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ($_SERVER['DOCUMENT_ROOT'] ?? ''));

        $prefixo = '';
        if ($docRoot !== '' && stripos($raizProjeto, $docRoot) === 0) {
            // Extrai apenas o nome da subpasta relativa (ex.: "Trabalho-PHP") e codifica para URL
            $relativo = trim(substr($raizProjeto, strlen($docRoot)), '/');
            if ($relativo !== '') {
                $segmentos = array_map('rawurlencode', explode('/', $relativo));
                $prefixo = '/' . implode('/', $segmentos);
            }
        }
    }

    $caminhoLimpo = ltrim($caminho, '/');
    return $caminhoLimpo === '' ? ($prefixo === '' ? '/' : $prefixo . '/') : $prefixo . '/' . $caminhoLimpo;
}

/**
 * Envia cabeçalho HTTP Location e encerra a execução imediatamente para evitar vazamento de conteúdo.
 */
function redirecionar(string $caminho): never
{
    header('Location: ' . base_url($caminho));
    exit;
}

/**
 * Verifica se há usuário autenticado na sessão (ID válido armazenado em $_SESSION).
 */
function esta_logado(): bool
{
    return isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] > 0;
}

/**
 * Middleware obrigatório chamado no topo de todas as páginas internas.
 * Bloqueia acesso anônimo e redireciona para a tela de login com aviso.
 */
function exigir_login(): void
{
    if (!esta_logado()) {
        definir_flash('warning', 'Faça login para acessar o sistema.');
        redirecionar('modules/auth/login.php');
    }
}

/**
 * Retorna os dados básicos do usuário autenticado.
 */
function usuario_logado(): ?array
{
    if (!esta_logado()) {
        return null;
    }

    return [
        'id'     => (int) $_SESSION['usuario_id'],
        'nome'   => (string) ($_SESSION['usuario_nome'] ?? ''),
        'email'  => (string) ($_SESSION['usuario_email'] ?? ''),
        'perfil' => (string) ($_SESSION['usuario_perfil'] ?? 'analista'),
    ];
}

/**
 * Escapa strings para saída segura em HTML (proteção contra XSS).
 */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Armazena uma mensagem flash na sessão para ser exibida na próxima tela.
 */
function definir_flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = [
        'tipo'     => $tipo,
        'mensagem' => $mensagem,
    ];
}

/**
 * Exibe e limpa a mensagem flash armazenada na sessão.
 */
function exibir_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    $tipo = e($_SESSION['flash']['tipo'] ?? 'info');
    $mensagem = e($_SESSION['flash']['mensagem'] ?? '');
    unset($_SESSION['flash']);

    echo '<div class="alert alert-' . $tipo . ' alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">';
    echo $mensagem;
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>';
    echo '</div>';
}

/**
 * Formata datas vindas do MySQL (YYYY-MM-DD HH:MM:SS) para padrão brasileiro.
 */
function formatar_data(?string $data, bool $comHora = true): string
{
    if (empty($data)) {
        return '-';
    }

    $timestamp = strtotime($data);
    if ($timestamp === false) {
        return e($data);
    }

    return date($comHora ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
}

/**
 * Rótulo legível em português para valores de severidade/criticidade.
 */
function rotulo_severidade(string $valor): string
{
    return match ($valor) {
        'Critica' => 'Crítica',
        'Media'   => 'Média',
        'Alta'    => 'Alta',
        'Baixa'   => 'Baixa',
        default   => $valor,
    };
}

/**
 * Rótulo legível em português para status do incidente.
 */
function rotulo_status(string $valor): string
{
    return match ($valor) {
        'Em Analise' => 'Em Análise',
        default      => $valor,
    };
}

/**
 * Retorna um badge Bootstrap discreto para severidade ou criticidade.
 */
function badge_severidade(string $severidade): string
{
    $classe = match ($severidade) {
        'Critica' => 'badge-sev-critica',
        'Alta'    => 'badge-sev-alta',
        'Media'   => 'badge-sev-media',
        'Baixa'   => 'badge-sev-baixa',
        default   => 'text-bg-secondary',
    };

    return '<span class="badge rounded-pill ' . $classe . '">' . e(rotulo_severidade($severidade)) . '</span>';
}

/**
 * Retorna um badge Bootstrap discreto para status do incidente.
 */
function badge_status(string $status): string
{
    $classe = match ($status) {
        'Aberto'     => 'badge-status-aberto',
        'Em Analise' => 'badge-status-analise',
        'Contido'    => 'badge-status-contido',
        'Resolvido'  => 'badge-status-resolvido',
        default      => 'text-bg-secondary',
    };

    return '<span class="badge rounded-pill ' . $classe . '">' . e(rotulo_status($status)) . '</span>';
}
