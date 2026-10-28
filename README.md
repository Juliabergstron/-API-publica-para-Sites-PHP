# 📚 BiblioBusca — Biblioteca Digital de Consulta

> Aplicação web em **PHP 8.5** que consulta a API pública **Open Library**, recebe os dados em **JSON**, processa e exibe livros dinamicamente ao usuário.

🎥 **Vídeo de demonstração:** [Assista no YouTube](COLE-AQUI-O-LINK-DO-VIDEO)

---

## 📖 Sobre o projeto

O **BiblioBusca** é um catálogo de livros online. O usuário digita o nome de um livro, de um autor ou um ISBN, e o sistema consulta o acervo mundial da Open Library, apresentando para cada obra:

- capa;
- título e autores;
- ano da primeira publicação;
- número médio de páginas;
- quantidade de edições;
- idiomas e assuntos;
- link para a página completa da obra.

O projeto foi desenvolvido como atividade acadêmica sobre **consumo de APIs públicas com PHP**: o PHP faz a requisição HTTP, interpreta o JSON retornado e gera a página de forma dinâmica.

## 🔄 Como funciona

```
Usuário digita "Dom Casmurro" ──► [ CONSULTAR ]
        │
        ▼
PHP valida o termo e monta a requisição
        │   GET https://openlibrary.org/search.json?title=dom+casmurro&page=1&limit=12&fields=...
        ▼
API Open Library responde com JSON
        │
        ▼
PHP faz json_decode(), normaliza os campos de cada livro
        │
        ▼
Página exibe os livros em cartões, com paginação
```

## ✅ Requisitos da atividade

| Requisito | Onde está |
|---|---|
| Título do sistema | Seção principal do `index.php` ("BiblioBusca") |
| Formulário | `<form method="get">` no `index.php` |
| Campo de pesquisa | Campo `busca`, com seletor do tipo de busca |
| Botão de consulta | Botão **Consultar** |
| Área para apresentação dos resultados | Seção "Resultados da consulta" |
| Requisição HTTP a uma API pública | `src/OpenLibraryClient.php` (cURL) |
| Recebimento e tratamento de JSON | `json_decode()` e função `normalizarLivro()` |

## ✨ Funcionalidades

- Busca por **tudo, título, autor ou ISBN**.
- Exibição de **capas** e dados detalhados de cada livro.
- **Paginação** dos resultados (12 livros por página).
- Histórico das **últimas pesquisas** na sessão.
- Tratamento de erros: termo curto, nenhum livro encontrado, API indisponível e resposta inesperada.
- Proteção contra XSS com `htmlspecialchars()` em toda saída.
- Código com recursos modernos do PHP: `enum`, `match`, tipos de união e `declare(strict_types=1)`.
- Design responsivo em tema de biblioteca clássica (tons escuros e dourado).
- Página `diagnostico.php` para verificar o ambiente e testar a conexão com a API.

## 🛠️ Tecnologias

- **PHP 8.5** (extensões `curl` e `mbstring`)
- HTML5 e CSS3
- API pública [Open Library](https://openlibrary.org/developers/api) (não exige chave)
- Fontes Cormorant Garamond e Lato (Google Fonts)

## 🗂️ Estrutura do projeto

```
bibliobusca/
├── index.php                  # Página inicial: formulário e resultados
├── diagnostico.php            # Verificação do ambiente e teste da API
├── src/
│   └── OpenLibraryClient.php  # Requisição à API e tratamento do JSON
├── assets/
│   ├── css/style.css          # Estilos do site
│   └── img/                   # Ilustrações em SVG
├── .gitignore
└── README.md
```

## ▶️ Como executar

**Pré-requisitos:** PHP 8.5 com a extensão `curl` habilitada e conexão com a internet.

### Opção 1 — Servidor embutido do PHP

```bash
git clone https://github.com/SEU-USUARIO/bibliobusca.git
cd bibliobusca
php -S localhost:8000
```

Acesse **http://localhost:8000**.

### Opção 2 — XAMPP (Windows)

1. Inicie o **Apache** no painel do XAMPP.
2. Copie a pasta `bibliobusca` para `C:\xampp\htdocs\`.
3. Acesse **http://localhost/bibliobusca**.

## 🩺 Solução de problemas

Acesse `/diagnostico.php` para ver o estado do ambiente e da conexão com a API.

- **cURL desabilitado:** no `php.ini`, remova o `;` de `;extension=curl` e `;extension=mbstring` e reinicie o Apache.
- **Erro de certificado SSL (cURL 60):** comum no XAMPP. O código tenta novamente sem a verificação; o ideal é configurar `curl.cainfo` com um `cacert.pem`.
- **A página mostra código PHP:** acesse por `http://localhost/...` e não abrindo o arquivo diretamente.

## 🧪 Exemplos de pesquisa

| Pesquisa | Tipo | Resultado esperado |
|---|---|---|
| `Dom Casmurro` | Título | Edições do romance de Machado de Assis |
| `Tolkien` | Autor | Obras de J. R. R. Tolkien |
| `Harry Potter` | Tudo | Livros da série |
| `xyzxyzxyz` | Tudo | Mensagem de nenhum livro encontrado |

## 📸 Capturas de tela

<!-- Adicione prints em uma pasta "prints/" e referencie assim: -->
<!-- ![Página inicial](prints/home.png) -->
<!-- ![Resultados](prints/resultados.png) -->

## 👤 Autor

**Julia Costa Bergstron** — 2°DS-AMS — ETEC Zona Leste

## 📄 Licença

Projeto acadêmico, desenvolvido para fins educacionais. Os dados exibidos pertencem à [Open Library](https://openlibrary.org).
