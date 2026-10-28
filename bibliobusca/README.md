# 📚 BiblioBusca — Biblioteca Digital de Consulta (PHP + Open Library)

Aplicação web em **PHP 8.5** que consome a API pública **Open Library**, recebe os dados em **JSON**, processa e apresenta os livros dinamicamente ao usuário, com visual inspirado em bibliotecas clássicas (tons escuros, dourado e tipografia serifada).

## 🎯 Sobre o projeto

O usuário digita um livro, autor ou ISBN e o sistema consulta o acervo da Open Library, mostrando capa, autores, ano da primeira publicação, número de páginas, edições, idiomas e assuntos.

### Fluxo de funcionamento

1. O usuário informa o termo e escolhe o tipo de busca (**Tudo, Título, Autor ou ISBN**), clicando em **Consultar**.
2. O PHP valida o termo (mínimo de 2 caracteres; no ISBN, mantém apenas números e `X`).
3. O PHP faz uma requisição `GET` com **cURL** para:
   ```
   https://openlibrary.org/search.json?q=dom+casmurro&page=1&limit=12&fields=...
   ```
4. A API retorna um **JSON**; o PHP o decodifica com `json_decode()` e normaliza os campos de cada livro.
5. Os livros são exibidos em cartões, com paginação.

## ✅ Requisitos atendidos

| Requisito | Onde está |
|---|---|
| Título do sistema | Seção principal (hero) do `index.php` |
| Formulário | `<form method="get">` no `index.php` |
| Campo de pesquisa | Campo `busca` (+ seletor do tipo de busca) |
| Botão de consulta | Botão **Consultar** |
| Área de resultados | Seção "Resultados da consulta" |
| Requisição HTTP a API pública | `src/OpenLibraryClient.php` (cURL) |
| Tratamento do JSON | `json_decode()` + exibição dinâmica |

## ✨ Extras

- Busca por **tudo, título, autor ou ISBN**, com **capas** e **paginação**.
- Histórico das **últimas pesquisas** (via `$_SESSION`).
- Tratamento de erros: termo curto, nenhum livro encontrado, API indisponível, resposta inesperada.
- Proteção contra XSS com `htmlspecialchars()` em toda saída.
- Recursos modernos do PHP 8: `enum` (`TipoBusca`), `match`, tipos de união e `declare(strict_types=1)`.
- Ilustrações em SVG geradas por código (estante e pilha de livros) — sem imagens de terceiros.
- Layout responsivo (computador e celular).

## 🗂️ Estrutura de pastas

```
bibliobusca/
├── index.php                  # Página inicial (formulário + resultados)
├── diagnostico.php            # Verifica o ambiente (PHP, cURL) e testa a API
├── src/
│   └── OpenLibraryClient.php  # Consulta à API e tratamento do JSON
├── assets/
│   ├── css/style.css          # Estilos do site
│   └── img/                   # Ilustrações SVG (estante e pilha de livros)
├── .gitignore
└── README.md
```

## 🛠️ Tecnologias

- **PHP 8.5** (desenvolvido para a versão 8.5.12) com extensão **cURL**
- HTML5 e CSS3 (fontes Cormorant Garamond e Lato, do Google Fonts, com fontes alternativas offline)
- API pública [Open Library](https://openlibrary.org/developers/api) (sem chave)

## ▶️ Passo a passo para executar

### Opção A — Servidor embutido do PHP (mais simples)

1. Baixe/clone o projeto e abra o terminal **dentro da pasta `bibliobusca`**.
2. Confira a versão: `php -v` (deve mostrar 8.5.x).
3. Inicie o servidor: `php -S localhost:8000`
4. Abra **http://localhost:8000** no navegador.
5. Pesquise, por exemplo, **Dom Casmurro**. Para parar o servidor, use `Ctrl + C`.

### Opção B — XAMPP (Windows)

1. Abra o **XAMPP Control Panel** e clique em **Start** no **Apache**.
2. Copie a pasta `bibliobusca` para `C:\xampp\htdocs\` (o arquivo deve ficar em `C:\xampp\htdocs\bibliobusca\index.php`).
3. Abra **http://localhost/bibliobusca** (se o Apache usar outra porta, por exemplo `http://localhost:8080/bibliobusca`).
4. Se algo falhar, abra **http://localhost/bibliobusca/diagnostico.php**.

> É necessário ter conexão com a internet. A Open Library às vezes demora alguns segundos para responder.

## 🩺 Solução de problemas

A página `diagnostico.php` confere a versão do PHP, as extensões `curl` e `mbstring` e faz uma consulta de teste, mostrando o motivo de qualquer falha.

- **cURL desabilitado:** no `php.ini` (XAMPP: *Config → PHP (php.ini)*), remova o `;` de `;extension=curl` e `;extension=mbstring` e reinicie o Apache.
- **Erro de certificado SSL (cURL 60):** comum no XAMPP/Windows. O código já tenta de novo sem verificar o certificado; o ideal é configurar `curl.cainfo` no `php.ini` com um arquivo `cacert.pem`.
- **A página mostra código PHP:** o arquivo foi aberto direto (`file://`). Acesse sempre por `http://localhost/...`.
- **Página não encontrada:** confira se não ficou uma pasta dentro da outra (`htdocs/bibliobusca/bibliobusca`).

## 🧪 Pesquisas para testar

| Pesquisa | Tipo | O que esperar |
|---|---|---|
| `Dom Casmurro` | Título | Edições do livro de Machado de Assis |
| `Tolkien` | Autor | Obras de J. R. R. Tolkien |
| `Harry Potter` | Tudo | Vários livros da série |
| `xyzxyzxyz` | Tudo | Mensagem de "nenhum livro encontrado" |
| `a` | Tudo | O navegador pede pelo menos 2 caracteres |

## 🚀 Publicar no GitHub

```bash
cd bibliobusca
git init
git add .
git commit -m "BiblioBusca: pesquisa de livros com PHP e Open Library"
git branch -M main
git remote add origin https://github.com/SEU-USUARIO/bibliobusca.git
git push -u origin main
```

(Crie antes o repositório vazio `bibliobusca` no GitHub, sem README.)

## 🎥 Vídeo de demonstração

[Assista no YouTube](COLE-AQUI-O-LINK-DO-VIDEO)

## 👤 Autor

Seu Nome — Curso/Turma — Instituição
