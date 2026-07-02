<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePerfil(['admin_cliente', 'gerente']);

$pdo = getConnection();
$clienteId = $_SESSION['cliente_id'];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar') {
    $nome  = trim($_POST['nome'] ?? '');
    $preco = str_replace(',', '.', trim($_POST['preco'] ?? ''));
    $codigo = trim($_POST['codigo_barras'] ?? '');
    $estoque = (int)($_POST['estoque'] ?? 0);

    if ($nome === '' || !is_numeric($preco) || (float)$preco <= 0) {
        $erros[] = 'Informe nome e um preço válido.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO produtos (cliente_id, nome, codigo_barras, preco, estoque) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$clienteId, $nome, $codigo ?: null, (float)$preco, $estoque]);
        flash('sucesso', 'Produto cadastrado.');
        redirect('/cliente/produtos.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'alternar') {
    $stmt = $pdo->prepare('UPDATE produtos SET ativo = NOT ativo WHERE id = ? AND cliente_id = ?');
    $stmt->execute([(int)$_POST['produto_id'], $clienteId]);
    redirect('/cliente/produtos.php');
}

$stmt = $pdo->prepare('SELECT * FROM produtos WHERE cliente_id = ? ORDER BY nome');
$stmt->execute([$clienteId]);
$produtos = $stmt->fetchAll();

$titulo = 'Produtos';
require __DIR__ . '/../includes/header.php';
?>

<h1>Produtos</h1>

<?php if ($msg = flash('sucesso')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php foreach ($erros as $erro): ?><div class="alert alert-error"><?= e($erro) ?></div><?php endforeach; ?>

<div class="card">
    <h2>Novo produto</h2>
    <form method="post" class="form-box">
        <input type="hidden" name="acao" value="criar">
        <label>Nome</label>
        <input type="text" name="nome" required>
        <label>Código de barras</label>
        <input type="text" name="codigo_barras">
        <label>Preço (R$)</label>
        <input type="text" name="preco" required placeholder="Ex: 19,90">
        <label>Estoque</label>
        <input type="number" name="estoque" value="0">
        <button type="submit">Cadastrar</button>
    </form>
</div>

<div class="card">
    <h2>Produtos cadastrados</h2>
    <table>
        <tr><th>Nome</th><th>Código</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Ação</th></tr>
        <?php foreach ($produtos as $p): ?>
        <tr>
            <td><?= e($p['nome']) ?></td>
            <td><?= e($p['codigo_barras'] ?? '—') ?></td>
            <td><?= formatarMoeda((float)$p['preco']) ?></td>
            <td><?= (int)$p['estoque'] ?></td>
            <td><?= $p['ativo'] ? 'Ativo' : 'Inativo' ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="acao" value="alternar">
                    <input type="hidden" name="produto_id" value="<?= (int)$p['id'] ?>">
                    <button style="margin:0;padding:4px 10px;"><?= $p['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$produtos): ?><tr><td colspan="6">Nenhum produto cadastrado.</td></tr><?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
