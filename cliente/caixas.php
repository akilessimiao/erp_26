<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePerfil(['admin_cliente', 'gerente']);

$pdo = getConnection();
$clienteId = $_SESSION['cliente_id'];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'criar') {
    $identificador = trim($_POST['identificador'] ?? '');
    $loja = trim($_POST['loja'] ?? '');

    if ($identificador === '') {
        $erros[] = 'Informe um identificador para o caixa/terminal.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO caixas (cliente_id, identificador, loja) VALUES (?, ?, ?)');
        $stmt->execute([$clienteId, $identificador, $loja ?: null]);
        flash('sucesso', 'Caixa/terminal criado.');
        redirect('/cliente/caixas.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'alternar') {
    $stmt = $pdo->prepare('UPDATE caixas SET ativo = NOT ativo WHERE id = ? AND cliente_id = ?');
    $stmt->execute([(int)$_POST['caixa_id'], $clienteId]);
    redirect('/cliente/caixas.php');
}

$stmt = $pdo->prepare('SELECT * FROM caixas WHERE cliente_id = ? ORDER BY criado_em');
$stmt->execute([$clienteId]);
$caixas = $stmt->fetchAll();
$ativos = count(array_filter($caixas, fn($c) => $c['ativo']));

$titulo = 'Caixas / Terminais';
require __DIR__ . '/../includes/header.php';
?>

<h1>Caixas / Terminais</h1>

<?php if ($msg = flash('sucesso')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php foreach ($erros as $erro): ?>
    <div class="alert alert-error"><?= e($erro) ?></div>
<?php endforeach; ?>

<div class="card">
    <p>Caixas ativos: <strong><?= $ativos ?></strong> —
        faixa de cobrança atual: <strong><?= $ativos > 3 ? 'Acima de 3 (Valor Y + Gerente)' : 'Até 3 (Valor X)' ?></strong>
    </p>
</div>

<?php if ($_SESSION['perfil'] === 'admin_cliente'): ?>
<div class="card">
    <h2>Novo caixa/terminal</h2>
    <form method="post" class="form-box">
        <input type="hidden" name="acao" value="criar">
        <label>Identificador (ex: Caixa 01)</label>
        <input type="text" name="identificador" required>
        <label>Loja</label>
        <input type="text" name="loja">
        <button type="submit">Adicionar</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h2>Caixas cadastrados</h2>
    <table>
        <tr><th>Identificador</th><th>Loja</th><th>Status</th><?php if ($_SESSION['perfil'] === 'admin_cliente'): ?><th>Ação</th><?php endif; ?></tr>
        <?php foreach ($caixas as $c): ?>
        <tr>
            <td><?= e($c['identificador']) ?></td>
            <td><?= e($c['loja'] ?? '—') ?></td>
            <td><?= $c['ativo'] ? 'Ativo' : 'Inativo' ?></td>
            <?php if ($_SESSION['perfil'] === 'admin_cliente'): ?>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="acao" value="alternar">
                    <input type="hidden" name="caixa_id" value="<?= (int)$c['id'] ?>">
                    <button style="margin:0;padding:4px 10px;"><?= $c['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
