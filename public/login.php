<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$tipo = $_GET['tipo'] ?? 'cliente'; // 'cliente' ou 'admin'
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo  = $_POST['tipo'] ?? 'cliente';

    if ($email === '' || $senha === '') {
        $erro = 'Preencha e-mail e senha.';
    } elseif ($tipo === 'admin') {
        if (loginAdminSistema($email, $senha)) {
            redirect('/admin/dashboard.php');
        }
        $erro = 'E-mail ou senha inválidos.';
    } else {
        if (loginUsuarioCliente($email, $senha)) {
            redirect(($_SESSION['perfil'] === 'caixa') ? '/caixa/pdv.php' : '/cliente/dashboard.php');
        }
        $erro = 'E-mail ou senha inválidos, ou acesso bloqueado.';
    }
}

$titulo = 'Login';
require __DIR__ . '/../includes/header.php';
?>

<div class="login-wrap">
    <div class="login-box">
        <h1 style="text-align:center;font-size:1.4rem;">ERP 2026</h1>

        <div class="login-tabs">
            <a href="?tipo=cliente" class="<?= $tipo === 'cliente' ? 'active' : '' ?>">Sou Cliente</a>
            <a href="?tipo=admin" class="<?= $tipo === 'admin' ? 'active' : '' ?>">Admin do Sistema</a>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-error"><?= e($erro) ?></div>
        <?php endif; ?>

        <form method="post" class="form-box">
            <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
            <label>E-mail</label>
            <input type="email" name="email" required autofocus>
            <label>Senha</label>
            <input type="password" name="senha" required>
            <button type="submit" style="width:100%;">Entrar</button>
        </form>

        <?php if ($tipo === 'admin'): ?>
            <p style="font-size:0.78rem;color:#888;margin-top:16px;">Exemplo inicial: admin@erp2026.com / admin123</p>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
