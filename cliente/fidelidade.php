<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePerfil(['admin_cliente', 'gerente']);

$pdo = getConnection();
$clienteId = $_SESSION['cliente_id'];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero = trim($_POST['numero_cartao'] ?? '');
    $nome   = trim($_POST['nome_portador'] ?? '');
    $pontos = (int)($_POST['pontos'] ?? 0);

    if ($numero === '') {
        $erros[] = 'Informe o número do cartão.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO cartoes_fidelidade (cliente_id, numero_cartao, nome_portador, pontos) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$clienteId, $numero, $nome ?: null, $pontos]);
            flash('sucesso', 'Cartão cadastrado.');
            redirect('/cliente/fidelidade.php');
        } catch (PDOException $e) {
            $erros[] = ($e->getCode() === '23000') ? 'Já existe um cartão com este número.' : 'Erro ao salvar.';
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM cartoes_fidelidade WHERE cliente_id = ? ORDER BY numero_cartao');
$stmt->execute([$clienteId]);
$cartoes = $stmt->fetchAll();

$titulo = 'Cartão Fidelidade';
require __DIR__ . '/../includes/header.php';
?>

<h1>Cartões Fidelidade</h1>

<?php if ($msg = flash('sucesso')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php foreach ($erros as $erro): ?><div class="alert alert-error"><?= e($erro) ?></div><?php endforeach; ?>

<div class="card">
    <h2>Novo cartão</h2>
    <form method="post" class="form-box">
        <label>Número do cartão</label>
        <input type="text" name="numero_cartao" required>
        <label>Nome do portador</label>
        <input type="text" name="nome_portador">
        <label>Pontos iniciais</label>
        <input type="number" name="pontos" value="0">
        <button type="submit">Cadastrar</button>
    </form>
</div>

<div class="card">
    <h2>Cartões cadastrados</h2>
    <table>
        <tr><th>Número</th><th>Portador</th><th>Pontos</th></tr>
        <?php foreach ($cartoes as $c): ?>
        <tr><td><?= e($c['numero_cartao']) ?></td><td><?= e($c['nome_portador'] ?? '—') ?></td><td><?= (int)$c['pontos'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$cartoes): ?><tr><td colspan="3">Nenhum cartão cadastrado.</td></tr><?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
