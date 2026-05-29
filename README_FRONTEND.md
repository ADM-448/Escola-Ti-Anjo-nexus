# Nova Arquitetura de Frontend: 100% REST API

Este documento detalha a recente refatoração arquitetural que separou a camada de apresentação (Frontend) da camada lógica e de dados (Backend) do projeto AnjoNexus.

---

## 🎯 Conceito Geral

Anteriormente, o sistema utilizava o motor de templates **Blade** do Laravel (`.blade.php`), o que significa que o HTML era montado dinamicamente pelo servidor (PHP) antes de ser enviado ao navegador do usuário.

Agora, migramos para uma arquitetura **100% REST API**. O Frontend passou a ser completamente estático e independente (HTML puro + JavaScript Vanilla). O Backend (Laravel) agora atua estritamente como um provedor de dados (JSON) através de endpoints na API.

## 📂 Estrutura de Arquivos e Organização

Para manter o projeto organizado, optamos por não expor os arquivos HTML diretamente na raiz pública (`public/`). Em vez disso, isolamos o Frontend em sua própria estrutura dentro da pasta segura `resources/`:

* `resources/views/editais/index.html`: A vitrine principal (lista e filtros).
* `resources/views/editais/show.html`: A página de detalhes de um edital específico.

## 🚦 O Fluxo de Comunicação (Como Funciona na Prática)

O fluxo de exibição de uma página funciona da seguinte forma:

1. **Acesso:** O usuário digita a URL `http://.../editais`.
2. **Distribuição (Web):** O roteador web do Laravel (`routes/web.php`) intercepta essa chamada. Como não processamos mais PHP na view, o Laravel usa a função `response()->file(...)` apenas para ler o arquivo `index.html` bruto do disco e entregá-lo instantaneamente ao navegador.
3. **Renderização Inicial:** O navegador recebe um HTML limpo e desenha a estrutura da página (cabeçalho, barra lateral de filtros) e exibe um "spinner" de carregamento.
4. **Requisição de Dados (API):** O script JavaScript embutido no HTML dispara assincronamente uma requisição HTTP via **Fetch API** para o endpoint `GET /api/editais`.
5. **Processamento do Backend:** O roteador da API (`routes/api.php`) encaminha para o `EditalController@index`. O controlador faz a consulta ao banco de dados (aplicando eventuais filtros de busca, público ou órgão), serializa os modelos Eloquent em uma estrutura JSON, e devolve essa resposta.
6. **Atualização do DOM:** O JavaScript recebe a resposta JSON, itera sobre os dados e injeta dinamicamente (montando as *tags* via template literals) os cards dos editais e os botões de paginação diretamente no DOM da página.

## 🔗 Mapeamento de Rotas

O ecossistema foi dividido em dois grupos de rotas distintos:

### Rotas de Interface (Servidor de Arquivos Estáticos)
Localizadas em `routes/web.php`:
* `GET /` ──▶ Redireciona para `/editais`.
* `GET /editais` ──▶ Serve `resources/views/editais/index.html`.
* `GET /editais/show` ──▶ Serve `resources/views/editais/show.html`.

### Rotas de Dados (A API REST)
Localizadas em `routes/api.php`:
* `GET /api/editais` ──▶ Retorna a listagem JSON paginada, aceitando queries (`?search=`, `?publico[]=`, `?fonte[]=`).
* `GET /api/editais/{id}` ──▶ Retorna os detalhes JSON de um único edital, cujo ID foi fornecido na URL (ex: `/editais/show?id=14`).

## 💡 Vantagens Dessa Arquitetura

1. **Desacoplamento Absoluto:** O time de frontend pode trabalhar no HTML/CSS/JS sem precisar entender de PHP ou Blade. Se decidirmos no futuro mover o Frontend para um framework como React, Vue ou Angular, ou até mesmo para outro servidor/repositório, a API já está completamente pronta e homologada.
2. **Performance Percebida:** Como a página base carrega quase que instantaneamente (arquivos estáticos), a interface é exibida muito mais rápido para o usuário enquanto os dados pesados carregam em segundo plano de forma não-bloqueante.
3. **Omnicanalidade (Reuso):** As regras de negócio (filtros, paginação, sanitização) estão centralizadas no Controller da API. O mesmo endpoint `/api/editais` já pode ser consumido nativamente pelo aplicativo Mobile (React Native) ou qualquer outra plataforma externa sem a necessidade de duplicar a lógica de busca.
