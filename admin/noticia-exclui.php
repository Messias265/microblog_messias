<?php

use Microblog\Auth\ControleDeAcesso;
use Microblog\Enums\TipoUsuario;
use Microblog\Helpers\Utils;
use Microblog\Services\NoticiaServico;

require_once __DIR__ . '/../vendor/autoload.php';

ControleDeAcesso::exigirLogin();

$id = Utils::normalizarEntrada($_GET['id'] ?? null, 'inteiro');
Utils::verificarId($id);

$tipoUsuario = TipoUsuario::from($_SESSION['tipo']);
$usuarioId = (int) $_SESSION['id'];

try {
    $noticiaServico = new NoticiaServico();
    $noticiaServico->excluir($id, $tipoUsuario, $usuarioId);
    header("location:noticias.php");
    exit;
} catch (Throwable $e) {
    Utils::registrarLog($e);
    Utils::alertaErro("Não foi possível excluir a notícia. Tente novamente");
}
