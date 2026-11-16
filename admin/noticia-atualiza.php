<?php

use Microblog\Auth\ControleDeAcesso;
use Microblog\Enums\Destaque;
use Microblog\Enums\TipoUsuario;
use Microblog\Helpers\Utils;
use Microblog\Helpers\Validacoes;
use Microblog\Models\Noticia;
use Microblog\Services\CategoriaServico;
use Microblog\Services\NoticiaServico;

require_once __DIR__ . '/../vendor/autoload.php';

ControleDeAcesso::exigirLogin();

$metodo = $_SERVER['REQUEST_METHOD'];
$idRecebido = $metodo === 'POST'
    ? ($_POST['id'] ?? null)
    : ($_GET['id'] ?? null);
$id = Utils::normalizarEntrada($idRecebido, 'inteiro');
Utils::verificarId($id);

$mensagemErro = "";
$listaDeCategorias = [];
$dadosNoticia = null;
$titulo = "";
$texto = "";
$resumo = "";
$destaque = "";
$categoriaId = "";
$imagem = "";

try {
    $categoriaServico = new CategoriaServico();
    $listaDeCategorias = $categoriaServico->listarTodos();
} catch (Throwable $e) {
    Utils::registrarLog($e);
    $mensagemErro = "Não foi possível carregar as categorias";
}

$tipoUsuario = TipoUsuario::from($_SESSION['tipo']);
$usuarioId = (int) $_SESSION['id'];
$noticiaServico = null;

if ($mensagemErro === "") {
    try {
        $noticiaServico = new NoticiaServico();
        $dadosNoticia = $noticiaServico->buscarPorId($tipoUsuario, $usuarioId, $id);
    } catch (Throwable $e) {
        Utils::registrarLog($e);
        $mensagemErro = "Não foi possível carregar os dados da notícia";
    }
}

if ($dadosNoticia === null && $mensagemErro === "") {
    Utils::alertaErro("Notícia não encontrada!");
}

if ($dadosNoticia !== null) {
    $titulo = $dadosNoticia['titulo'];
    $texto = $dadosNoticia['texto'];
    $resumo = $dadosNoticia['resumo'];
    $destaque = $dadosNoticia['destaque'];
    $categoriaId = $dadosNoticia['categoria_id'] === null
        ? ""
        : (string) $dadosNoticia['categoria_id'];
    $imagem = $dadosNoticia['imagem'];
}

if ($metodo === 'POST' && $mensagemErro === "" && $dadosNoticia !== null) {
    try {
        $titulo = Utils::normalizarEntrada($_POST['titulo'] ?? '');
        $texto = Utils::normalizarEntrada($_POST['texto'] ?? '');
        $resumo = Utils::normalizarEntrada($_POST['resumo'] ?? '');
        $destaque = Utils::normalizarEntrada($_POST['destaque'] ?? '');
        $categoriaId = Utils::normalizarEntrada($_POST['categoria'] ?? '', 'inteiro');

        Validacoes::validarTitulo($titulo);
        Validacoes::validarTexto($texto);
        Validacoes::validarResumo($resumo);
        Validacoes::validarDestaque($destaque);
        Validacoes::validarCategoria($categoriaId);

        if ($categoriaServico->buscarPorId($categoriaId) === null) {
            throw new InvalidArgumentException("Selecione uma categoria válida.");
        }

        $destaqueEnum = Destaque::from($destaque);
        $erroImagem = $_FILES['imagem']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($erroImagem !== UPLOAD_ERR_NO_FILE) {
            $imagem = Utils::upload($_FILES['imagem'] ?? null);
        }

        $noticia = new Noticia(
            $titulo,
            $texto,
            $resumo,
            $imagem,
            $destaqueEnum,
            $dadosNoticia['usuario_id'] === null ? null : (int) $dadosNoticia['usuario_id'],
            $categoriaId,
            $id
        );
        $noticiaServico->atualizar($noticia, $tipoUsuario, $usuarioId);

        header("location:noticias.php");
        exit;
    } catch (InvalidArgumentException $e) {
        $mensagemErro = $e->getMessage();
    } catch (Throwable $e) {
        Utils::registrarLog($e);
        $mensagemErro = "Não foi possível atualizar a notícia";
    }
}

require_once __DIR__ . '/../includes/cabecalho-admin.php';
?>

<div class="row">
    <article class="col-12 bg-white rounded shadow my-1 py-4">
        <h2 class="text-center">Atualizar dados da notícia</h2>

        <?php if ($mensagemErro !== ""): ?>
            <div class="alert alert-danger text-center">
                <?= Utils::escaparHtml($mensagemErro) ?>
            </div>
        <?php endif; ?>

        <?php if ($dadosNoticia !== null): ?>
            <form class="mx-auto w-75" action="" method="post" id="form-atualizar" name="form-atualizar" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= Utils::escaparHtml($id) ?>">
                <div class="mb-3">
                    <label class="form-label" for="categoria">Categoria:</label>
                    <select class="form-select" name="categoria" id="categoria">
                        <option value="" <?= $categoriaId === '' ? 'selected' : '' ?>></option>
                        <?php foreach ($listaDeCategorias as $itemCategoria): ?>
                            <option value="<?= Utils::escaparHtml($itemCategoria['id']) ?>" <?= (string) $itemCategoria['id'] === (string) $categoriaId ? 'selected' : '' ?>>
                                <?= Utils::escaparHtml($itemCategoria['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="titulo">Título:</label>
                    <input value="<?= Utils::escaparHtml($titulo) ?>" class="form-control" type="text" id="titulo" name="titulo">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="texto">Texto:</label>
                    <textarea class="form-control" name="texto" id="texto" cols="50" rows="6"><?= Utils::escaparHtml($texto) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="resumo">Resumo (máximo de 300 caracteres):</label>
                    <span id="maximo" class="badge bg-danger">0</span>
                    <textarea class="form-control" name="resumo" id="resumo" cols="50" rows="2" maxlength="300"><?= Utils::escaparHtml($resumo) ?></textarea>
                </div>
                <div class="mb-3">
                    <div class="row">
                        <div class="col-5">
                            <label for="imagem-existente" class="form-label">Imagem atual:</label>
                            <input value="<?= Utils::escaparHtml($imagem) ?>" class="form-control" type="text" id="imagem-existente" readonly disabled>
                        </div>
                        <div class="col-7 text-end">
                            <img src="../images/<?= Utils::escaparHtml($imagem) ?>" alt="Imagem da notícia" class="img-fluid rounded">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="imagem" class="form-label">Caso queira mudar, selecione outra imagem:</label>
                    <input class="form-control" type="file" id="imagem" name="imagem" accept="image/jpeg, image/gif, image/png, image/webp">
                </div>
                <div class="mb-3">
                    <p>Deixar a notícia em destaque?</p>
                    <input type="radio" class="btn-check" name="destaque" id="option1" autocomplete="off" value="nao" <?= $destaque === 'nao' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-danger" for="option1">Não</label>
                    <input type="radio" class="btn-check" name="destaque" id="option2" autocomplete="off" value="sim" <?= $destaque === 'sim' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-success" for="option2">Sim</label>
                </div>
                <button class="btn btn-primary" name="atualizar"><i class="bi bi-arrow-clockwise"></i> Atualizar</button>
            </form>
        <?php endif; ?>
    </article>
</div>

<script src="js/contador.js"></script>
<?php require_once __DIR__ . '/../includes/rodape-admin.php'; ?>
