<?php
/**
 * Conexão com o banco de dados via PDO.
 * Ajuste as constantes abaixo para o seu ambiente.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'erp2026');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em produção, registre o erro em log em vez de exibi-lo.
            die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
        }
    }

    return $pdo;
}
