<?php
/**
 * Autenticação e controle de acesso.
 *
 * Existem dois "mundos" de login:
 *  - admins        -> dono do SaaS (ADMIN do sistema)
 *  - usuarios       -> pessoas dentro de um cliente (admin_cliente, gerente, caixa)
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Autentica o ADMIN do sistema. Retorna true/false. */
function loginAdminSistema(string $email, string $senha): bool
{
    $pdo = getConnection();
    $stmt = $pdo->prepare('SELECT id, nome, senha_hash FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($senha, $admin['senha_hash'])) {
        session_regenerate_id(true);
        $_SESSION['tipo']      = 'admin_sistema';
        $_SESSION['admin_id']  = $admin['id'];
        $_SESSION['admin_nome'] = $admin['nome'];
        return true;
    }
    return false;
}

/** Autentica um usuário do cliente (admin_cliente, gerente ou caixa). */
function loginUsuarioCliente(string $email, string $senha): bool
{
    $pdo = getConnection();
    $stmt = $pdo->prepare(
        'SELECT u.id, u.nome, u.senha_hash, u.perfil, u.ativo, u.cliente_id, c.status AS cliente_status
         FROM usuarios u
         JOIN clientes c ON c.id = u.cliente_id
         WHERE u.email = ?'
    );
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        return false;
    }
    if (!$usuario['ativo']) {
        return false;
    }
    if (!in_array($usuario['cliente_status'], ['teste', 'ok', 'ativo'], true)) {
        return false; // bloqueado ou não aprovado no teste
    }

    session_regenerate_id(true);
    $_SESSION['tipo']        = 'usuario_cliente';
    $_SESSION['usuario_id']  = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['perfil']      = $usuario['perfil']; // admin_cliente | gerente | caixa
    $_SESSION['cliente_id']  = $usuario['cliente_id'];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

/** Bloqueia a página se não houver um ADMIN do sistema logado. */
function requireAdminSistema(): void
{
    if (($_SESSION['tipo'] ?? null) !== 'admin_sistema') {
        header('Location: /public/login.php');
        exit;
    }
}

/** Bloqueia a página se não houver usuário do cliente logado com um dos perfis permitidos. */
function requirePerfil(array $perfisPermitidos): void
{
    if (($_SESSION['tipo'] ?? null) !== 'usuario_cliente' || !in_array($_SESSION['perfil'] ?? null, $perfisPermitidos, true)) {
        header('Location: /public/login.php');
        exit;
    }
}

function gerarChaveAcesso(): string
{
    return strtoupper(bin2hex(random_bytes(8))); // ex: A1B2C3D4E5F6G7H8
}
