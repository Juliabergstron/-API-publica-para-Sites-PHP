<?php
declare(strict_types=1);
require __DIR__ . '/src/OpenLibraryClient.php';

$url  = OPENLIBRARY_BUSCA . '?q=dom+casmurro&limit=1&fields=key,title';
$resp = requisicaoGet($url);
$json = $resp['corpo'] !== null ? json_decode($resp['corpo'], true) : null;

function linha(string $nome, bool $ok, string $detalhe = ''): void
{
    $icone = $ok ? '✅' : '❌';
    echo '<tr><td>' . htmlspecialchars($nome) . "</td><td>{$icone} " . htmlspecialchars($detalhe) . '</td></tr>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico — BiblioBusca</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 720px; margin: 30px auto; padding: 0 12px; }
        td { padding: 8px 12px; border-bottom: 1px solid #ddd; vertical-align: top; }
    </style>
</head>
<body>
<h1>Diagnóstico do BiblioBusca</h1>
<table>
<?php
linha('Versão do PHP', version_compare(PHP_VERSION, '8.1.0', '>='), PHP_VERSION);
linha('Extensão cURL', function_exists('curl_init'), function_exists('curl_init') ? 'habilitada' : 'DESABILITADA — ative extension=curl no php.ini');
linha('Extensão mbstring', function_exists('mb_strlen'), function_exists('mb_strlen') ? 'habilitada' : 'DESABILITADA — ative extension=mbstring no php.ini');
linha('allow_url_fopen', (bool) ini_get('allow_url_fopen'), ini_get('allow_url_fopen') ? 'ligado' : 'desligado');
linha('Requisição à Open Library', $resp['corpo'] !== null, $resp['erro'] ?? 'resposta HTTP 200 recebida');
linha('JSON válido com resultados', is_array($json) && !empty($json['docs']), is_array($json) ? 'encontrados: ' . ($json['numFound'] ?? 0) : 'JSON inválido ou vazio');
?>
</table>
<p><a href="index.php">← Voltar ao site</a></p>
</body>
</html>
