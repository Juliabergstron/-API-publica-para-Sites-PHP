<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/src/OpenLibraryClient.php';

/** Escapa texto para exibição segura em HTML. */
function e(string|int|null $texto): string
{
    return htmlspecialchars((string) ($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Monta o link de uma pesquisa/página. */
function urlBusca(string $busca, TipoBusca $tipo, int $pagina = 1): string
{
    return 'index.php?' . http_build_query([
        'busca'  => $busca,
        'tipo'   => $tipo->value,
        'pagina' => $pagina,
    ]) . '#resultado';
}

$busca  = trim((string) ($_GET['busca'] ?? ''));
$tipo   = TipoBusca::tryFrom((string) ($_GET['tipo'] ?? 'q')) ?? TipoBusca::Geral;
$pagina = min(100, max(1, (int) ($_GET['pagina'] ?? 1)));

$resultado = null;

// Histórico da sessão. Descarta itens inválidos (por exemplo, de versões antigas do projeto
// que usavam a mesma sessão do navegador em localhost).
$historico = array_values(array_filter(
    (array) ($_SESSION['historico'] ?? []),
    fn(mixed $h): bool => is_array($h)
        && isset($h['busca'], $h['tipo'])
        && is_string($h['busca'])
        && is_string($h['tipo'])
));

if ($busca !== '') {
    $resultado = buscarLivros($busca, $tipo, $pagina);

    // Guarda as últimas pesquisas bem-sucedidas (sem repetir o mesmo termo)
    if ($resultado['ok'] && $pagina === 1) {
        $historico = array_values(array_filter(
            $historico,
            fn(array $h): bool => mb_strtolower($h['busca']) !== mb_strtolower($busca)
        ));
        array_unshift($historico, ['busca' => $busca, 'tipo' => $tipo->value]);
        $historico = array_slice($historico, 0, 6);
    }
}

$_SESSION['historico'] = $historico;

$totalPaginas = ($resultado['ok'] ?? false)
    ? min(100, (int) ceil($resultado['total'] / LIVROS_POR_PAGINA))
    : 1;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BiblioBusca — Biblioteca Digital de Consulta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,500&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="nav">
    <div class="nav-in">
        <a class="marca" href="index.php">Biblio<span>Busca</span></a>
        <nav>
            <a href="#pesquisa">Pesquisar</a>
            <a href="#resultado">Acervo</a>
            <a href="#sobre">Sobre</a>
        </nav>
    </div>
</header>

<!-- ===== HERO + FORMULÁRIO ===== -->
<section class="hero" id="pesquisa">
    <div class="hero-conteudo">
        <p class="sobretitulo">Acervo mundial · Open Library</p>
        <h1>BiblioBusca</h1>
        <p class="subtitulo">A biblioteca digital para consultar livros, autores e edições</p>

        <form method="get" action="index.php" class="busca" id="form-busca">
            <label for="busca" class="so-leitor">Pesquisar livros</label>
            <input type="text" id="busca" name="busca" placeholder="Digite um livro, autor ou ISBN…"
                   value="<?= e($busca) ?>" required minlength="2" autocomplete="off">

            <select name="tipo" aria-label="Tipo de busca">
                <?php foreach (TipoBusca::cases() as $opcao): ?>
                    <option value="<?= e($opcao->value) ?>" <?= $opcao === $tipo ? 'selected' : '' ?>>
                        <?= e($opcao->rotulo()) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" id="btn-busca">Consultar</button>
        </form>

        <?php if ($historico): ?>
            <p class="recentes">
                <span>Recentes</span>
                <?php foreach ($historico as $h): ?>
                    <a href="<?= e(urlBusca($h['busca'], TipoBusca::tryFrom($h['tipo']) ?? TipoBusca::Geral)) ?>"><?= e($h['busca']) ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<!-- ===== RESULTADOS ===== -->
<section class="resultados" id="resultado" aria-live="polite">
    <div class="container">
        <h2 class="titulo-secao">Resultados da consulta</h2>

        <?php if ($resultado === null): ?>
            <p class="vazio">Nenhuma pesquisa realizada ainda. Digite o nome de um livro ou autor no campo acima.</p>

        <?php elseif (!$resultado['ok']): ?>
            <div class="alerta">
                <?= e($resultado['erro']) ?>
                <?php if (!empty($resultado['detalhe'])): ?>
                    <small>Detalhe técnico: <?= e($resultado['detalhe']) ?></small>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <p class="resumo">
                <?= e(number_format($resultado['total'], 0, ',', '.')) ?> resultado(s) · página <?= e($pagina) ?> de <?= e($totalPaginas) ?>
            </p>

            <div class="grade">
                <?php foreach ($resultado['livros'] as $livro): ?>
                    <article class="livro">
                        <div class="capa">
                            <?php if ($livro['capa']): ?>
                                <img src="<?= e($livro['capa']) ?>" alt="Capa do livro <?= e($livro['titulo']) ?>" loading="lazy">
                            <?php else: ?>
                                <span class="sem-capa">Sem<br>capa</span>
                            <?php endif; ?>
                        </div>

                        <div class="info">
                            <h3><?= e($livro['titulo']) ?></h3>
                            <p class="autor"><?= e($livro['autores'] ?: 'Autor desconhecido') ?></p>

                            <ul>
                                <li><b>1ª publicação</b> <?= e($livro['ano'] ?? '—') ?></li>
                                <li><b>Páginas (média)</b> <?= e($livro['paginas'] ?? '—') ?></li>
                                <li><b>Edições</b> <?= e($livro['edicoes'] ?? '—') ?></li>
                                <li><b>Idiomas</b> <?= e($livro['idiomas'] ? implode(', ', $livro['idiomas']) : '—') ?></li>
                            </ul>

                            <?php if ($livro['assuntos']): ?>
                                <p class="assuntos">
                                    <?php foreach ($livro['assuntos'] as $assunto): ?>
                                        <span><?= e($assunto) ?></span>
                                    <?php endforeach; ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($livro['link']): ?>
                                <a class="ver-mais" href="<?= e($livro['link']) ?>" target="_blank" rel="noopener">Ver na Open Library →</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <nav class="paginacao" aria-label="Paginação">
                <?php if ($pagina > 1): ?>
                    <a href="<?= e(urlBusca($busca, $tipo, $pagina - 1)) ?>">← Anterior</a>
                <?php endif; ?>
                <?php if ($pagina < $totalPaginas): ?>
                    <a href="<?= e(urlBusca($busca, $tipo, $pagina + 1)) ?>">Próxima →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>

<!-- ===== SOBRE ===== -->
<section class="sobre" id="sobre">
    <div class="split">
        <div class="split-texto">
            <p class="sobretitulo">Sobre o sistema</p>
            <h2>Uma estante aberta ao mundo</h2>
            <p>
                O BiblioBusca consulta em tempo real a API pública da Open Library. O PHP envia a
                requisição, recebe os dados em JSON, processa cada obra e a apresenta como um
                cartão do acervo, com capa, autoria, edições e assuntos.
            </p>
            <p>Projeto acadêmico desenvolvido em PHP 8.5, sem banco de dados e sem chave de API.</p>
        </div>
        <div class="split-imagem">
            <img src="assets/img/pilha.svg" alt="Pilha de livros antigos com um livro aberto no topo">
        </div>
    </div>

    <div class="recursos container">
        <div><span>I</span><h3>Por título</h3><p>Encontre obras e edições pelo nome do livro.</p></div>
        <div><span>II</span><h3>Por autor</h3><p>Explore a bibliografia de escritores clássicos e atuais.</p></div>
        <div><span>III</span><h3>Por ISBN</h3><p>Localize uma edição específica pelo código ISBN.</p></div>
        <div><span>IV</span><h3>Capas e detalhes</h3><p>Veja capas, páginas, idiomas e assuntos de cada obra.</p></div>
    </div>
</section>

<footer class="rodape">
    <p>BiblioBusca · Dados fornecidos pela API pública <a href="https://openlibrary.org" target="_blank" rel="noopener">Open Library</a></p>
</footer>

<script>
    // Indica que a consulta está em andamento
    document.getElementById('form-busca').addEventListener('submit', () => {
        const btn = document.getElementById('btn-busca');
        btn.textContent = 'Buscando…';
        btn.disabled = true;
    });
    <?php if ($resultado !== null): ?>
    // Rola até os resultados após uma consulta
    document.getElementById('resultado').scrollIntoView();
    <?php endif; ?>
</script>
</body>
</html>
