<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminSistema();

$pdo = getConnection();
$totais = $pdo->query(
    "SELECT
        SUM(status = 'teste') AS em_teste,
        SUM(status IN ('ok','ativo')) AS ativos,
        SUM(status = 'bloqueado') AS bloqueados,
        COUNT(*) AS total
     FROM clientes"
)->fetch();

$titulo = 'Painel do Admin';
require __DIR__ . '/../includes/header.php';
?>

<h1>Painel do Admin do Sistema</h1>

<div class="card">
    <h2>Resumo de clientes</h2>
    <table>
        <tr><th>Total de clientes</th><th>Em teste</th><th>Ativos/Aprovados</th><th>Bloqueados</th></tr>
        <tr>
            <td><?= (int)$totais['total'] ?></td>
            <td><?= (int)$totais['em_teste'] ?></td>
            <td><?= (int)$totais['ativos'] ?></td>
            <td><?= (int)$totais['bloqueados'] ?></td>
        </tr>
    </table>
    <a href="/admin/clientes.php" class="btn">Ver todos os clientes</a>
    <a href="/admin/cliente_novo.php" class="btn btn-secondary">+ Novo cliente</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
