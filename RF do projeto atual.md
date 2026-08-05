# 🕸️ Roach Scraping API REST - Coletor Automatizado e Agregador de Editais

Sistema de **Extração, Transformação e Carga (ETL)** e **API REST** para captura, sanitização e disponibilização de editais e linhas de financiamento de agências de fomento brasileiras (como **FINEP**, **FAPESC**, **FAPESP** e **FAPPR**).

A solução combina o framework de raspagem **Roach PHP**, navegação headless via **Spatie Browsershot (Puppeteer/Chromium)**, parseamento de DOM com **Symfony DomCrawler** e uma API REST em **Laravel 12**.

---

## 📋 Requisitos do Sistema

### 🎯 Requisitos Funcionais (RF)

- **[RF-01] Coleta Automatizada de Editais Multi-Fonte**: O sistema deve raspar e extrair automaticamente editais de portais de fomento (FINEP, FAPESC, FAPESP, FAPPR) via Spiders dedicados.
- **[RF-02] Suporte a Paginação Assíncrona (AJAX/SPA)**: O sistema deve interagir com elementos dinâmicos de interface (como botões de avançar paginação AJAX no Liferay da FINEP) para capturar todas as páginas da vitrine de oportunidades.
- **[RF-03] Sanitização e Limpeza de Dados**: O sistema deve filtrar e tratar ruídos visuais dos campos extraídos, como expressões de *tooltip* informativa ("i") coladas ao texto de Público-Alvo.
- **[RF-04] Idempotência na Persistência (Upsert)**: O sistema deve gerar um identificador único unívoco (`external_id` via MD5 composto de `Título + Link`) para evitar duplicação e atualizar registros existentes no banco de dados.
- **[RF-05] Listagem de Editais com Paginação**: A API REST (`GET /api/editais`) deve listar os editais cadastrados com ordenação por data de publicação e paginação de 12 itens por página.
- **[RF-06] Consulta Detalhada de Edital**: A API REST (`GET /api/editais/{id}`) deve retornar os detalhes completos de um edital específico por ID.
- **[RF-07] Busca Textual por Termo**: O sistema deve permitir a busca por palavra-chave filtrando nos campos de `titulo` ou `objetivo` do edital via parâmetro `search`.
- **[RF-08] Filtro Multi-Seleção por Público-Alvo**: O sistema deve permitir a filtragem de editais por múltiplos públicos-alvo (`publico[]`), incluindo a regra de aproximação semântica e checagem de elegibilidade.
- **[RF-09] Filtro por Órgão/Fonte de Fomento**: O sistema deve permitir a filtragem de editais por um ou mais órgãos de origem (`fonte[]`), ex: `FINEP`, `FAPESC`, `FAPPR`.
- **[RF-10] Visualização Web (Frontend)**: O sistema deve fornecer visões HTML (`/editais` e `/editais/show`) que consomem a API REST para navegação e consulta amigável.

---

### ⚙️ Requisitos Não-Funcionais (RNF)

- **[RNF-01] Desempenho e Coleta em Lote (Cofre Virtual)**: A arquitetura da Spider deve acumular os nós HTML de todas as páginas em buffer no motor V8 em uma única sessão do Chromium, reduzindo o tempo total de coleta da FINEP de ~30 minutos para 1-2 minutos.
- **[RNF-02] Resiliência e Timeouts Estendidos**: A integração com o Chromium headless deve ser configurada com suporte a chamadas assíncronas e `protocolTimeout` estendido (até 10 minutos) para evitar erros de desconexão (`Runtime.evaluate timed out`).
- **[RNF-03] Concorrência e Monitoramento de Mutação de DOM**: O crawler deve validar mutações no contêiner de cards antes de avançar a paginação, eliminando *race conditions* causadas por atualizações prematuras de rodapé.
- **[RNF-04] Padronização RESTful**: As respostas da API devem seguir a estrutura JSON padronizada `{ "success": true, "data": ... }` com códigos HTTP apropriados (200, 404, 500).
- **[RNF-05] Manutenibilidade e Extensibilidade**: As Spiders devem ser isoladas por fonte e estender os contratos do Roach PHP (`Spider` e `ItemProcessorInterface`), permitindo a adição de novos portais sem impactar a API existente.

---

## 🛠️ Tecnologias e Dependências Core

- **PHP 8.2+** & **Laravel 12**
- **Roach PHP** (`roach-php/laravel`) - Framework para orquestração de Spiders e Pipelines.
- **Spatie Browsershot** + **Puppeteer (Node.js & Chromium)** - Emulação de navegador headless para execução de JavaScript e interação com SPA/AJAX.
- **Symfony DomCrawler** - Parseamento de nós HTML e extração de seletores CSS.
- **SQLite / MySQL** - Persistência dos dados de editais.

---

## 🏗️ Arquitetura e Estrutura de Pastas

```
.
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── EditalController.php    # API REST (Filtros de texto, fonte, público e paginação)
│   │       └── PlatformController.php  # Dados e estatísticas de plataformas de fomento
│   ├── Models/
│   │   └── Edital.php                  # Model Eloquent da tabela 'editais'
│   └── Spiders/
│       ├── FinepSpider.php             # Spider para a FINEP (Navegação dinâmica com Browsershot)
│       ├── FapeScSpider.php            # Spider para o portal FAPESC
│       ├── FapespSpider.php            # Spider para o portal FAPESP
│       ├── FapprSpider.php             # Spider para o portal FAPPR
│       └── Processors/
│           └── SalvarNoBancoProcessor.php # Pipeline Roach: sanitização e updateOrCreate no BD
├── database/                           # Migrations e estrutura da tabela 'editais'
├── resources/
│   └── views/
│       └── editais/
│           ├── index.html              # Interface web de busca e filtragem dos editais
│           └── show.html               # Interface web de exibição detalhada de edital
├── routes/
│   ├── api.php                         # Endpoints da API REST (/api/editais)
│   └── web.php                         # Rotas de navegação da interface web
├── README.md                           # Documentação geral do sistema
└── Roach.md                            # Guia prático de comandos e utilização do Roach PHP
```

---

## 🔄 Fluxo de Funcionamento (ETL)

```
[Spiders (Roach PHP + Browsershot)]
              │
              ▼
    1. Extração Dinâmica (Navegação Headless + Injeção JS de Paginação)
              │
              ▼
    2. Acúmulo no Cofre Virtual & Parseamento (Symfony DomCrawler)
              │
              ▼
    3. Tratamento & Sanitização de Dados (Regex de Tooltips e Resíduos)
              │
              ▼
[SalvarNoBancoProcessor] (Geração de external_id MD5 + updateOrCreate)
              │
              ▼
        [Banco de Dados]
              │
              ▼
[EditalController] ──> Endpoints REST API (GET /api/editais) ──> [Frontend/Clientes]
```

---

## 📈 Jornada de Engenharia e Desafios Solucionados

| Desafio | Sintoma / O que quebrava | Solução Implementada |
| --- | --- | --- |
| **API Incompleta do Portal** | A API JSON nativa do portal trazia campos `NULL`. | Pivot da raspagem para a vitrine rica `/oportunidades`. |
| **Paginação AJAX em SPA** | A URL não alterava ao mudar de página. | Injeção de script assíncrono Puppeteer simulando cliques no botão `.lexicon-icon-angle-right`. |
| **Timeout do Chromium** | `ProtocolError: Runtime.evaluate timed out` em execuções longas. | Ajuste do `protocolTimeout` para 10 min e conversão do script JS em `Promise` encadeada. |
| **Race Condition no DOM** | O rodapé atualizava antes dos novos cards renderizarem. | Ancoragem total no contêiner `.lista-cards`, aguardando a alteração da assinatura HTML antes de prosseguir. |
| **Cards Fixados (Pinned)** | Título do 1º card fixo gerava loop infinito. | Monitoramento dinâmico de mudanças no contêiner global de cards. |
| **Colisão de Chaves** | Usar apenas a URL sobrescrevia editais que compartilhavam o mesmo link. | Criação de chave composta única `external_id = md5(Título + URL)`. |

---

## 🔌 API REST (Endpoints)

### 1. Listar Editais (com Filtros e Paginação)
- **URL:** `GET /api/editais`
- **Parâmetros Query Opcionais:**
  - `search` (string): Termo para busca em `titulo` ou `objetivo`.
  - `publico[]` (array): Lista de públicos-alvo (ex: `publico[]=Empresa&publico[]=ICT`).
  - `fonte[]` (array): Lista de órgãos de origem (ex: `fonte[]=FINEP&fonte[]=FAPESC`).
  - `page` (int): Número da página (padrão: 1).

**Exemplo de Resposta:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "external_id": "e10adc3949ba59abbe56e057f20f883e",
        "titulo": "Chamada Pública FINEP - Subvenção Econômica",
        "link": "https://www.finep.gov.br/oportunidades/...",
        "objetivo": "Apoiar projetos de inovação...",
        "fonte": "FINEP",
        "condicao_financiamento": "Não reembolsável",
        "operacao": "Subvenção Econômica",
        "publico": "Empresas de todos os portes",
        "data_publicacao": "2026-08-01"
      }
    ],
    "per_page": 12,
    "total": 45
  }
}
```

### 2. Consultar Edital Específico
- **URL:** `GET /api/editais/{id}`

---

## 🚀 Como Executar o Projeto

1. **Instalar Dependências PHP e Node.js:**
   ```bash
   composer install
   npm install
   ```

2. **Configurar o Ambiente:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Executar as Migrações do Banco de Dados:**
   ```bash
   php artisan migrate:fresh
   ```

4. **Rodar a Coleta de Editais (Spider FINEP):**
   ```bash
   php artisan roach:run FinepSpider
   ```

5. **Iniciar o Servidor de Desenvolvimento:**
   ```bash
   composer run dev
   ```
   Acesse a interface em `http://localhost:8000/editais` ou consume os endpoints em `http://localhost:8000/api/editais`.