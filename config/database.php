<?php
/**
 * Configuração de conexão com o banco de dados MySQL via PDO.
 * Ajuste as credenciais abaixo conforme o seu ambiente (XAMPP / Laragon).
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'mini_soc');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Retorna uma instância única (reutilizada) da conexão PDO.
 */
function conectar_banco(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $opcoes = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 2,
    ];

    $portas = array_unique([DB_PORT, '3307', '3306']);
    $ultimoErro = null;

    foreach ($portas as $porta) {
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
            $ultimoErro = $e;
        }
    }

    http_response_code(500);
    die(
        'Erro ao conectar ao banco de dados <strong>mini_soc</strong>. '
        . 'Verifique se o MySQL está em execução, se os scripts da pasta <code>sql/</code> foram importados '
        . 'e se as credenciais em <code>config/database.php</code> estão corretas.'
    );
}
