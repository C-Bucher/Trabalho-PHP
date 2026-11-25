<?php
/**
 * Configuração de conexão com o banco de dados MySQL via PDO.
 * Ajuste as credenciais abaixo conforme o seu ambiente (XAMPP / Laragon).
 */

// Constantes de conexão com o servidor MySQL/MariaDB local
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'mini_soc');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Retorna uma instância única (padrão Singleton simples via variável static) da conexão PDO.
 * Tenta conectar primeiro na porta configurada e faz fallback automático entre 3307 e 3306.
 */
function conectar_banco(): PDO
{
    static $pdo = null;

    // Reutiliza a conexão já aberta nesta requisição HTTP para evitar abrir múltiplas conexões
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Opções de segurança e comportamento do PDO:
    // - ERRMODE_EXCEPTION: lança PDOException em caso de erro SQL (facilita tratamento com try/catch)
    // - FETCH_ASSOC: retorna resultados como arrays associativos (nome_coluna => valor)
    // - EMULATE_PREPARES => false: força prepared statements nativos do MySQL (proteção real contra SQL Injection)
    $opcoes = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 2,
    ];

    // Lista de portas a testar (útil em ambientes XAMPP onde o MySQL pode rodar na 3307 ou 3306)
    $portas = array_unique([DB_PORT, '3307', '3306']);
    $ultimoErro = null;

    foreach ($portas as $porta) {
        // Monta a string DSN com charset utf8mb4 para suportar acentuação completa
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            $porta,
            DB_NAME
        );

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
            return $pdo;
        } catch (PDOException $e) {
            // Guarda o último erro e tenta a próxima porta da lista
            $ultimoErro = $e;
        }
    }

    // Se nenhuma porta responder, retorna erro HTTP 500 com mensagem orientativa ao usuário
    http_response_code(500);
    die(
        'Erro ao conectar ao banco de dados <strong>mini_soc</strong>. '
        . 'Verifique se o MySQL está em execução, se os scripts da pasta <code>sql/</code> foram importados '
        . 'e se as credenciais em <code>config/database.php</code> estão corretas.'
    );
}
