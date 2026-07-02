<?php

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $chave, ?string $mensagem = null)
{
    if ($mensagem !== null) {
        $_SESSION['flash'][$chave] = $mensagem;
        return null;
    }
    $valor = $_SESSION['flash'][$chave] ?? null;
    unset($_SESSION['flash'][$chave]);
    return $valor;
}

function e(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function formatarMoeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function statusClienteLabel(string $status): string
{
    $labels = [
        'teste'        => 'Em teste',
        'ok'           => 'Aprovado',
        'nao_aprovado' => 'Não aprovado',
        'ativo'        => 'Ativo',
        'bloqueado'    => 'Bloqueado',
    ];
    return $labels[$status] ?? $status;
}
