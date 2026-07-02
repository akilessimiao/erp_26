<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePerfil(['admin_cliente', 'gerente']);

$pdo = getConnection();
$clienteId = $_SESSION['cliente_id'];

$cliente = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
$cliente->execute([$clienteId]);
$cliente = $cliente->fetch();

$totalCaixas = $pdo->prepare('SELECT COUNT(*) FROM caixas WHERE cliente_id = ? AND ativo = 1');
$totalCaixas->execute([$clienteId]);
$totalCaixas = (int)$totalCaixas->fetchColumn();

$vendasHoje = $pdo->prepare('SELECT COALESCE(SUM(total),0) FROM vendas WHERE cliente_id = ? AND DATE(criado_em) = CURDATE()');
$vendasHoje->execute([$clienteId]);
$vendasHoje = (float)$vendasHoje->fetchColumn();

$titulo = 'Painel do Cliente';
require __DIR__ . '/../includes/header.php';
?>

<h1><?= e($cliente['nome_empresa']) ?></h1>

<div class="card">
    <p>Status da conta:
        <span class="badge badge-<?= e($cliente['status']) ?>"><?= e(statusClienteLabel($cliente['status'])) ?></span>
        <?php if ($cliente['status'] === 'teste'): ?>
            (teste até <?= e($cliente['teste_fim']) ?>)
        <?php endif; ?>
    </p>

    <table>
        <tr><th>Caixas/terminais ativos</th><th>Vendas de hoje</th></tr>
        <tr>
            <td><?= $totalCaixas ?> <?= $totalCaixas > 3 ? '(cobrança faixa "acima de 3")' : '(cobrança faixa "até 3")' ?></td>
            <td><?= formatarMoeda($vendasHoje) ?></td>
        </tr>
    </table>

    <?php if ($_SESSION['perfil'] === 'admin_cliente'): ?>
        <a href="/cliente/usuarios.php" class="btn">Gerenciar usuários</a>
        <a href="/cliente/caixas.php" class="btn btn-secondary">Gerenciar caixas</a>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
