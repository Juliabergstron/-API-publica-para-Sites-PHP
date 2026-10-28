<?php
declare(strict_types=1);

/**
 * Cliente para a API pública Open Library (https://openlibrary.org/dev/docs/api/search).
 * Escrito para PHP 8.5.
 */

/** Tipos de busca aceitos. O valor do enum é o nome do parâmetro na API. */
enum TipoBusca: string
{
    case Geral  = 'q';
    case Titulo = 'title';
    case Autor  = 'author';
    case Isbn   = 'isbn';

    public function rotulo(): string
    {
        return match ($this) {
            self::Geral  => 'Tudo',
            self::Titulo => 'Título',
            self::Autor  => 'Autor',
            self::Isbn   => 'ISBN',
        };
    }
}

const OPENLIBRARY_BUSCA  = 'https://openlibrary.org/search.json';
const OPENLIBRARY_CAPAS  = 'https://covers.openlibrary.org/b/id/';
const OPENLIBRARY_SITE   = 'https://openlibrary.org';
const LIVROS_POR_PAGINA  = 12;

/**
 * Uma tentativa de requisição com cURL.
 *
 * @return array{corpo: ?string, codigo: int, erro: ?string}
 */
function requisicaoCurl(string $url, string $agente, bool $verificarSsl): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4, // evita lentidão com IPv6 no Windows
        CURLOPT_USERAGENT      => $agente,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => $verificarSsl,
        CURLOPT_SSL_VERIFYHOST => $verificarSsl ? 2 : 0,
    ]);

    $corpo  = curl_exec($ch);
    $codigo = curl_errno($ch);
    $erro   = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    // PHP 8.5: curl_close() está obsoleta; o handle é liberado automaticamente.

    if ($corpo === false) {
        return ['corpo' => null, 'codigo' => $codigo, 'erro' => "cURL erro {$codigo}: {$erro}"];
    }
    if ($status !== 200) {
        return ['corpo' => null, 'codigo' => 0, 'erro' => "A API respondeu com HTTP {$status}"];
    }

    return ['corpo' => (string) $corpo, 'codigo' => 0, 'erro' => null];
}

/**
 * Faz a requisição GET.
 *
 * @return array{corpo: ?string, erro: ?string}
 */
function requisicaoGet(string $url): array
{
    // A Open Library pede que o cliente se identifique com um User-Agent.
    $agente = 'BiblioBusca/1.0 (projeto academico em PHP)';

    if (function_exists('curl_init')) {
        $resp = requisicaoCurl($url, $agente, true);

        // Problema comum no XAMPP/Windows: certificados raiz não configurados
        // (erros cURL 60, 77 e 35). Nesse caso, tenta de novo sem verificar o certificado.
        // Aceitável aqui porque a consulta é só de leitura, em ambiente de estudo local.
        if ($resp['corpo'] === null && in_array($resp['codigo'], [35, 60, 77], true)) {
            $resp = requisicaoCurl($url, $agente, false);
        }

        return ['corpo' => $resp['corpo'], 'erro' => $resp['erro']];
    }

    // Alternativa caso a extensão cURL não esteja habilitada
    $contexto = stream_context_create(['http' => [
        'timeout' => 25,
        'header'  => "User-Agent: {$agente}\r\nAccept: application/json\r\n",
    ]]);
    $corpo = @file_get_contents($url, false, $contexto);

    if ($corpo === false) {
        $ultimo = error_get_last()['message'] ?? 'falha desconhecida';
        return ['corpo' => null, 'erro' => "file_get_contents: {$ultimo} (habilite a extensão curl no php.ini)"];
    }

    return ['corpo' => $corpo, 'erro' => null];
}

/** Traduz códigos de idioma da API (ex.: "por") para nomes legíveis. */
function nomeIdioma(string $codigo): string
{
    return match ($codigo) {
        'por' => 'Português',
        'eng' => 'Inglês',
        'spa' => 'Espanhol',
        'fre' => 'Francês',
        'ger' => 'Alemão',
        'ita' => 'Italiano',
        default => strtoupper($codigo),
    };
}

/** Converte um documento retornado pela API no formato usado pela página. */
function normalizarLivro(array $doc): array
{
    $capaId = $doc['cover_i'] ?? null;

    return [
        'titulo'   => (string) ($doc['title'] ?? 'Sem título'),
        'autores'  => implode(', ', array_slice((array) ($doc['author_name'] ?? []), 0, 3)),
        'ano'      => $doc['first_publish_year'] ?? null,
        'paginas'  => $doc['number_of_pages_median'] ?? null,
        'edicoes'  => $doc['edition_count'] ?? null,
        'idiomas'  => array_map('nomeIdioma', array_slice((array) ($doc['language'] ?? []), 0, 3)),
        'assuntos' => array_slice((array) ($doc['subject'] ?? []), 0, 3),
        'capa'     => $capaId ? OPENLIBRARY_CAPAS . $capaId . '-M.jpg' : null,
        'link'     => isset($doc['key']) ? OPENLIBRARY_SITE . $doc['key'] : null,
    ];
}

/**
 * Pesquisa livros na Open Library.
 *
 * @return array{ok: bool, erro?: string, detalhe?: ?string, total?: int, livros?: array<int, array>}
 */
function buscarLivros(string $termo, TipoBusca $tipo, int $pagina = 1): array
{
    $termo = trim($termo);

    if ($tipo === TipoBusca::Isbn) {
        $termo = preg_replace('/[^0-9Xx]/', '', $termo) ?? '';
    }

    if (mb_strlen($termo) < 2) {
        return ['ok' => false, 'erro' => 'Digite pelo menos 2 caracteres para pesquisar.'];
    }

    $parametros = http_build_query([
        $tipo->value => $termo,
        'page'       => max(1, $pagina),
        'limit'      => LIVROS_POR_PAGINA,
        'fields'     => 'key,title,author_name,first_publish_year,cover_i,'
                      . 'number_of_pages_median,language,edition_count,subject',
    ]);

    $resposta = requisicaoGet(OPENLIBRARY_BUSCA . '?' . $parametros);
    $corpo    = $resposta['corpo'];

    if ($corpo === null) {
        return [
            'ok'      => false,
            'erro'    => 'Não foi possível conectar à API Open Library. Tente novamente em instantes.',
            'detalhe' => $resposta['erro'],
        ];
    }

    $json = json_decode($corpo, true);

    if (!is_array($json) || !isset($json['docs'])) {
        return ['ok' => false, 'erro' => 'A API retornou uma resposta inesperada.'];
    }

    if (empty($json['docs'])) {
        return ['ok' => false, 'erro' => 'Nenhum livro encontrado para essa pesquisa.'];
    }

    return [
        'ok'     => true,
        'total'  => (int) ($json['numFound'] ?? count($json['docs'])),
        'livros' => array_map('normalizarLivro', $json['docs']),
    ];
}
