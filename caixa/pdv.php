<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePerfil(['caixa']);

$pdo = getConnection();
$clienteId = $_SESSION['cliente_id'];
$usuarioId = $_SESSION['usuario_id'];

// Garante que existe pelo menos um caixa/terminal ativo vinculado a este usuário/loja.
// Para simplificar neste pacote inicial, usamos o primeiro caixa ativo do cliente.
$caixa = $pdo->prepare('SELECT * FROM caixas WHERE cliente_id = ? AND ativo = 1 ORDER BY id LIMIT 1');
$caixa->execute([$clienteId]);
$caixa = $caixa->fetch();

$resultadoPreco = null;
$resultadoFidelidade = null;
$mensagemVenda = null;
$carrinho = $_SESSION['carrinho'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // --- Consulta de preço ---
    if ($acao === 'consultar_preco') {
        $termo = trim($_POST['termo'] ?? '');
        $stmt = $pdo->prepare(
            'SELECT * FROM produtos WHERE cliente_id = ? AND ativo = 1 AND (codigo_barras = ? OR nome LIKE ?) LIMIT 5'
        );
        $stmt->execute([$clienteId, $termo, "%$termo%"]);
        $resultadoPreco = $stmt->fetchAll();
    }

    // --- Consulta de cartão fidelidade ---
    if ($acao === 'consultar_fidelidade') {
        $numero = trim($_POST['numero_cartao'] ?? '');
        $stmt = $pdo->prepare('SELECT * FROM cartoes_fidelidade WHERE cliente_id = ? AND numero_cartao = ?');
        $stmt->execute([$clienteId, $numero]);
        $resultadoFidelidade = $stmt->fetch() ?: false;
    }

    // --- Adicionar item ao carrinho (venda pelo terminal) ---
    if ($acao === 'adicionar_item') {
        $produtoId = (int)$_POST['produto_id'];
        $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = ? AND cliente_id = ?');
        $stmt->execute([$produtoId, $clienteId]);
        $produto = $stmt->fetch();
        if ($produto) {
            $carrinho[] = ['produto_id' => $produto['id'], 'nome' => $produto['nome'], 'preco' => (float)$produto['preco']];
            $_SESSION['carrinho'] = $carrinho;
        }
    }

    // --- Finalizar venda ---
    if ($acao === 'finalizar_venda' && $caixa && $carrinho) {
        $total = array_sum(array_column($carrinho, 'preco'));
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO vendas (cliente_id, caixa_id, usuario_id, total) VALUES (?, ?, ?, ?)');
        $stmt->execute([$clienteId, $caixa['id'], $usuarioId, $total]);
        $vendaId = $pdo->lastInsertId();
        $itemStmt = $pdo->prepare('INSERT INTO venda_itens (venda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, 1, ?)');
        foreach ($carrinho as $item) {
            $itemStmt->execute([$vendaId, $item['produto_id'], $item['preco']]);
        }
        $pdo->commit();
        $mensagemVenda = 'Venda #' . $vendaId . ' finalizada — total ' . formatarMoeda($total);
        $carrinho = [];
        $_SESSION['carrinho'] = [];
    }

    if ($acao === 'limpar_carrinho') {
        $carrinho = [];
        $_SESSION['carrinho'] = [];
    }
}

$todosProdutos = $pdo->prepare('SELECT * FROM produtos WHERE cliente_id = ? AND ativo = 1 ORDER BY nome');
$todosProdutos->execute([$clienteId]);
$todosProdutos = $todosProdutos->fetchAll();

$totalCarrinho = array_sum(array_column($carrinho, 'preco'));

$titulo = 'PDV - Caixa';
require __DIR__ . '/../includes/header.php';
?>

<h1>PDV — Caixa</h1>

<?php if (!$caixa): ?>
    <div class="alert alert-error">Nenhum caixa/terminal ativo cadastrado para esta empresa. Peça ao Admin do Cliente para criar um em "Caixas".</div>
<?php else: ?>
    <p style="color:#666;">Terminal: <strong><?= e($caixa['identificador']) ?></strong></p>
<?php endif; ?>

<?php if ($mensagemVenda): ?>
    <div class="alert alert-success"><?= e($mensagemVenda) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Venda pelo terminal</h2>
    <form method="post" class="form-box">
        <input type="hidden" name="acao" value="adicionar_item">
        <label>Produto</label>
        <select name="produto_id" required>
            <?php foreach ($todosProdutos as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= e($p['nome']) ?> — <?= formatarMoeda((float)$p['preco']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Adicionar ao carrinho</button>
    </form>

    <table>
        <tr><th>Item</th><th>Preço</th></tr>
        <?php foreach ($carrinho as $item): ?>
            <tr><td><?= e($item['nome']) ?></td><td><?= formatarMoeda($item['preco']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$carrinho): ?>
            <tr><td colspan="2">Carrinho vazio.</td></tr>
        <?php endif; ?>
    </table>
    <p><strong>Total: <?= formatarMoeda($totalCarrinho) ?></strong></p>

    <form method="post" style="display:inline;">
        <input type="hidden" name="acao" value="finalizar_venda">
        <button type="submit" <?= (!$caixa || !$carrinho) ? 'disabled' : '' ?>>Finalizar venda</button>
    </form>
    <form method="post" style="display:inline;">
        <input type="hidden" name="acao" value="limpar_carrinho">
        <button type="submit" class="btn-secondary" style="background:#6b7280;">Limpar carrinho</button>
    </form>
</div>

<div class="card">
    <h2>Consulta de preço</h2>
    <form method="post" class="form-box">
        <input type="hidden" name="acao" value="consultar_preco">
        <label>Nome ou código de barras</label>
        <input type="text" name="termo" required>
        <button type="submit">Consultar</button>
    </form>
    <?php if ($resultadoPreco !== null): ?>
        <table>
            <tr><th>Produto</th><th>Código</th><th>Preço</th></tr>
            <?php foreach ($resultadoPreco as $p): ?>
                <tr><td><?= e($p['nome']) ?></td><td><?= e($p['codigo_barras'] ?? '—') ?></td><td><?= formatarMoeda((float)$p['preco']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$resultadoPreco): ?><tr><td colspan="3">Nada encontrado.</td></tr><?php endif; ?>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Consulta de cartão fidelidade</h2>
    <form method="post" class="form-box">
        <input type="hidden" name="acao" value="consultar_fidelidade">
        <label>Número do cartão</label>
        <input type="text" name="numero_cartao" required>
        <button type="submit">Consultar</button>
    </form>
    <?php if ($resultadoFidelidade === false): ?>
        <p style="margin-top:12px;">Cartão não encontrado.</p>
    <?php elseif ($resultadoFidelidade): ?>
        <table>
            <tr><th>Portador</th><th>Pontos</th></tr>
            <tr><td><?= e($resultadoFidelidade['nome_portador'] ?? '—') ?></td><td><?= (int)$resultadoFidelidade['pontos'] ?></td></tr>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
