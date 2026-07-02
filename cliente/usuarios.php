<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePerfil(['admin_cliente']);

$pdo = getConnection();
$clienteId = $_SESSION['cliente_id'];
$erros = [];

// Criar novo usuário (gerente ou caixa)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'criar') {
    $nome   = trim($_POST['nome'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $senha  = $_POST['senha'] ?? '';
    $perfil = $_POST['perfil'] ?? '';

    if ($nome === '' || $email === '' || strlen($senha) < 6 || !in_array($perfil, ['gerente', 'caixa'], true)) {
        $erros[] = 'Preencha todos os campos corretamente (senha com no mínimo 6 caracteres).';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (cliente_id, nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$clienteId, $nome, $email, password_hash($senha, PASSWORD_DEFAULT), $perfil]);
            flash('sucesso', 'Usuário criado com sucesso.');
            redirect('/cliente/usuarios.php');
        } catch (PDOException $e) {
            $erros[] = ($e->getCode() === '23000') ? 'Já existe um usuário com este e-mail.' : 'Erro ao salvar.';
        }
    }
}

// Ativar/desativar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'alternar') {
    $stmt = $pdo->prepare('UPDATE usuarios SET ativo = NOT ativo WHERE id = ? AND cliente_id = ?');
    $stmt->execute([(int)$_POST['usuario_id'], $clienteId]);
    redirect('/cliente/usuarios.php');
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE cliente_id = ? ORDER BY perfil, nome");
$stmt->execute([$clienteId]);
$usuarios = $stmt->fetchAll();

$titulo = 'Usuários';
require __DIR__ . '/../includes/header.php';
?>

<h1>Usuários</h1>

<?php if ($msg = flash('sucesso')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php foreach ($erros as $erro): ?>
    <div class="alert alert-error"><?= e($erro) ?></div>
<?php endforeach; ?>

<div class="card">
    <h2>Novo usuário (Gerente ou Caixa)</h2>
    <form method="post" class="form-box">
        <input type="hidden" name="acao" value="criar">
        <label>Nome</label>
        <input type="text" name="nome" required>
        <label>E-mail</label>
        <input type="email" name="email" required>
        <label>Senha</label>
        <input type="password" name="senha" required minlength="6">
        <label>Perfil</label>
        <select name="perfil" required>
            <option value="gerente">Gerente</option>
            <option value="caixa">Caixa</option>
        </select>
        <button type="submit">Criar usuário</button>
    </form>
</div>

<div class="card">
    <h2>Usuários cadastrados</h2>
    <table>
        <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th><th>Ação</th></tr>
        <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><?= e($u['nome']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['perfil']) ?></td>
            <td><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="acao" value="alternar">
                    <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>">
                    <button style="margin:0;padding:4px 10px;"><?= $u['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
