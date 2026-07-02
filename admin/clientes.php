<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminSistema();

$pdo = getConnection();

// Aprovação/reprovação do teste de 5 dias
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'], $_POST['cliente_id'])) {
    $clienteId = (int)$_POST['cliente_id'];
    $novoStatus = match ($_POST['acao']) {
        'aprovar'  => 'ok',
        'reprovar' => 'nao_aprovado',
        'bloquear' => 'bloqueado',
        'reativar' => 'ativo',
        default    => null,
    };
    if ($novoStatus) {
        $stmt = $pdo->prepare('UPDATE clientes SET status = ? WHERE id = ?');
        $stmt->execute([$novoStatus, $clienteId]);
        flash('sucesso', 'Status do cliente atualizado.');
    }
    redirect('/admin/clientes.php');
}

$clientes = $pdo->query(
    "SELECT c.*, p.nome AS plano_nome
     FROM clientes c
     LEFT JOIN planos p ON p.id = c.plano_id
     ORDER BY c.criado_em DESC"
)->fetchAll();

$titulo = 'Clientes';
require __DIR__ . '/../includes/header.php';
?>

<h1>Clientes</h1>

<?php if ($msg = flash('sucesso')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card">
    <a href="/admin/cliente_novo.php" class="btn">+ Novo cliente</a>

    <table>
        <tr>
            <th>Empresa</th><th>CNPJ</th><th>Plano</th><th>Status</th><th>Teste até</th><th>Chave</th><th>Ações</th>
        </tr>
        <?php foreach ($clientes as $c): ?>
        <tr>
            <td><?= e($c['nome_empresa']) ?></td>
            <td><?= e($c['cnpj']) ?></td>
            <td><?= e($c['plano_nome'] ?? '—') ?></td>
            <td><span class="badge badge-<?= e($c['status']) ?>"><?= e(statusClienteLabel($c['status'])) ?></span></td>
            <td><?= e($c['teste_fim'] ?? '—') ?></td>
            <td><code><?= e($c['chave_acesso']) ?></code></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="cliente_id" value="<?= (int)$c['id'] ?>">
                    <?php if ($c['status'] === 'teste'): ?>
                        <button name="acao" value="aprovar" style="margin:0;padding:4px 10px;">OK</button>
                        <button name="acao" value="reprovar" class="btn-secondary" style="margin:0;padding:4px 10px;background:#991b1b;">Não</button>
                    <?php elseif ($c['status'] === 'bloqueado'): ?>
                        <button name="acao" value="reativar" style="margin:0;padding:4px 10px;">Reativar</button>
                    <?php else: ?>
                        <button name="acao" value="bloquear" style="margin:0;padding:4px 10px;background:#991b1b;">Bloquear</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$clientes): ?>
            <tr><td colspan="7">Nenhum cliente cadastrado ainda.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
