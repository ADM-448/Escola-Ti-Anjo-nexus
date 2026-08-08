# 📖 Manual de Código: Radar de Editais B2B
## Guia Completo de Desenvolvimento — Backend Laravel 12 + Frontend Vanilla JS

> **Para quem é este manual:** Todos os integrantes do time, independente do nível técnico. Siga este guia de ponta a ponta. A nomenclatura, a estrutura de arquivos e os exemplos aqui apresentados são o **padrão oficial do projeto**.

---

## 📌 Sumário

1. [Filosofia do Projeto (API Stateless)](#1-filosofia-do-projeto-api-stateless)
2. [Estrutura de Arquivos e Nomenclatura](#2-estrutura-de-arquivos-e-nomenclatura)
3. [Backend: CRUD Completo no Laravel 12](#3-backend-crud-completo-no-laravel-12)
4. [Frontend: Estilização Padrão e Design System](#4-frontend-estilização-padrão-e-design-system)
5. [Frontend: Consumo da API com JavaScript](#5-frontend-consumo-da-api-com-javascript)
6. [Autenticação (Login, Cadastro e Token Sanctum)](#6-autenticação-login-cadastro-e-token-sanctum)
7. [Filtros, Busca e Paginação](#7-filtros-busca-e-paginação)
8. [Upload de Arquivos (PDFs e Imagens)](#8-upload-de-arquivos-pdfs-e-imagens)
9. [WebSockets: Notificações em Tempo Real](#9-websockets-notificações-em-tempo-real)
10. [Filas e Jobs Assíncronos (Queues)](#10-filas-e-jobs-assíncronos-queues)
11. [Testes Automatizados (PHPUnit)](#11-testes-automatizados-phpunit)
12. [Glossário Técnico para a Banca](#12-glossário-técnico-para-a-banca)

---

## 1. Filosofia do Projeto (API Stateless)

O projeto segue a arquitetura **API REST Stateless**:

```
[Navegador do Usuário]          [Servidor Laravel 12]
   HTML + CSS + JS      <-->       API REST (/api/...)
   (Frontend Puro)            (Retorna APENAS JSON)
```

**Regras fundamentais:**
- O Backend **NUNCA** retorna HTML. Apenas JSON.
- O Frontend **NUNCA** renderiza Blade. Apenas consome JSON.
- Toda comunicação autentica com `Authorization: Bearer {token}`.
- Toda resposta segue o **Padrão Único de JSON** definido abaixo.

### ✅ Padrão Único de Resposta JSON (OBRIGATÓRIO)

```json
// Sucesso (200 OK ou 201 Created)
{
  "success": true,
  "data": { ... },
  "message": "Operação realizada com sucesso."
}

// Erro de Validação (422 Unprocessable Entity)
{
  "success": false,
  "message": "Dados inválidos.",
  "errors": {
    "email": ["O campo e-mail é obrigatório."]
  }
}

// Erro de Autorização (401 Unauthorized)
{
  "success": false,
  "message": "Token inválido ou expirado. Faça login novamente."
}

// Não Encontrado (404 Not Found)
{
  "success": false,
  "message": "Recurso não encontrado."
}
```

---

## 2. Estrutura de Arquivos e Nomenclatura

### 2.1 Backend (Laravel)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── AuthController.php        # Login, Cadastro, Logout
│   │   ├── EditalController.php          # CRUD de Editais
│   │   ├── StartupController.php         # CRUD de Startups (Clientes)
│   │   ├── PropostaController.php        # CRUD de Propostas/Minutas
│   │   └── ...
│   └── Requests/
│       ├── LoginRequest.php              # Validação do formulário de Login
│       ├── StoreStartupRequest.php       # Validação ao criar Startup
│       └── ...
├── Models/
│   ├── User.php
│   ├── Startup.php
│   ├── Edital.php
│   └── Proposta.php
├── Jobs/
│   └── ProcessarPdfEdital.php            # Tarefa assíncrona de leitura de PDF
└── Events/
    └── PropostaGeradaEvent.php           # Evento para WebSocket

routes/
└── api.php                               # TODAS as rotas da API aqui
```

### 2.2 Nomenclatura (Padrão Oficial)

| Tipo | Convenção | Exemplo |
| --- | --- | --- |
| Classe PHP | `PascalCase` | `EditalController`, `Startup` |
| Método PHP | `camelCase` | `index()`, `store()`, `myMethod()` |
| Arquivo PHP | Mesmo da Classe | `EditalController.php` |
| Tabela do BD | `plural_snake_case` | `editais`, `startups`, `propostas` |
| Coluna do BD | `snake_case` | `data_publicacao`, `publico_alvo` |
| Variável JS | `camelCase` | `dadosEditais`, `authToken` |
| Função JS | `camelCase` | `carregarEditais()`, `fazerLogin()` |
| Classe CSS | `kebab-case` | `.card-edital`, `.btn-primary` |
| ID HTML | `kebab-case` | `#modal-edital`, `#filtro-fonte` |

### 2.3 Frontend

```
public/
├── index.html             # Ponto de entrada (redireciona por JS)
├── css/
│   └── app.css            # ⚠️ Design System único - TODOS editam aqui
├── js/
│   ├── auth.js            # Funções de Login e Token
│   ├── api.js             # Função fetch() central (usada por todos)
│   ├── editais.js         # Lógica da tela de editais
│   ├── startups.js        # Lógica da carteira de startups
│   └── propostas.js       # Lógica das minutas/propostas
└── pages/
    ├── login.html
    ├── cadastro.html
    ├── editais.html
    ├── perfil.html
    └── kanban.html
```

---

## 3. Backend: CRUD Completo no Laravel 12

### 3.1 Migration (Estrutura da Tabela)

> **Arquivo:** `database/migrations/xxxx_create_startups_table.php`

```php
public function up(): void
{
    Schema::create('startups', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('cnpj', 18)->unique();
        $table->string('nome_fantasia');
        $table->string('faturamento_anual')->nullable();
        $table->string('trl_nivel')->nullable(); // ex: "TRL 4"
        $table->json('cnaes')->nullable();       // array de CNAEs
        $table->text('pitch')->nullable();
        $table->timestamps();
    });
}
```

**Comandos:**
```bash
php artisan make:migration create_startups_table
php artisan migrate
```

---

### 3.2 Model (Regras de Negócio)

> **Arquivo:** `app/Models/Startup.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Startup extends Model
{
    protected $table = 'startups';

    // Campos que podem ser preenchidos em massa (segurança)
    protected $fillable = [
        'user_id',
        'cnpj',
        'nome_fantasia',
        'faturamento_anual',
        'trl_nivel',
        'cnaes',
        'pitch',
    ];

    // Converte o campo JSON em array PHP automaticamente
    protected $casts = [
        'cnaes' => 'array',
    ];

    // Relacionamento: uma Startup pertence a um User (Consultor)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relacionamento: uma Startup tem muitas Propostas
    public function propostas()
    {
        return $this->hasMany(Proposta::class);
    }
}
```

---

### 3.3 Form Request (Validação Centralizada)

> **Arquivo:** `app/Http/Requests/StoreStartupRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStartupRequest extends FormRequest
{
    // Apenas usuários autenticados podem fazer essa requisição
    public function authorize(): bool
    {
        return auth()->check();
    }

    // Regras de validação dos campos
    public function rules(): array
    {
        return [
            'cnpj'            => 'required|string|max:18|unique:startups,cnpj',
            'nome_fantasia'   => 'required|string|max:255',
            'faturamento_anual' => 'nullable|string',
            'trl_nivel'       => 'nullable|string',
            'cnaes'           => 'nullable|array',
            'pitch'           => 'nullable|string|max:2000',
        ];
    }

    // Mensagens de erro em português
    public function messages(): array
    {
        return [
            'cnpj.required'        => 'O CNPJ é obrigatório.',
            'cnpj.unique'          => 'Este CNPJ já está cadastrado.',
            'nome_fantasia.required' => 'O nome fantasia é obrigatório.',
        ];
    }
}
```

**Comando para criar:**
```bash
php artisan make:request StoreStartupRequest
```

---

### 3.4 Controller (CRUD Completo — 5 Métodos)

> **Arquivo:** `app/Http/Controllers/StartupController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStartupRequest;
use App\Models\Startup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StartupController extends Controller
{
    /**
     * INDEX — Listar todas as startups do consultor logado
     * GET /api/startups
     */
    public function index(Request $request): JsonResponse
    {
        // auth()->id() pega o ID do usuário logado via Token Sanctum
        $startups = Startup::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $startups,
            'message' => 'Startups carregadas com sucesso.',
        ], 200);
    }

    /**
     * STORE — Criar uma nova startup
     * POST /api/startups
     */
    public function store(StoreStartupRequest $request): JsonResponse
    {
        // $request->validated() retorna SOMENTE os campos que passaram na validação
        $startup = Startup::create([
            ...$request->validated(),
            'user_id' => auth()->id(), // vincula ao consultor logado
        ]);

        return response()->json([
            'success' => true,
            'data'    => $startup,
            'message' => 'Startup cadastrada com sucesso!',
        ], 201);
    }

    /**
     * SHOW — Exibir uma startup específica
     * GET /api/startups/{id}
     */
    public function show(string $id): JsonResponse
    {
        // findOrFail lança um 404 automaticamente se não encontrar
        $startup = Startup::where('user_id', auth()->id())->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $startup,
        ], 200);
    }

    /**
     * UPDATE — Atualizar uma startup existente
     * PUT /api/startups/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $startup = Startup::where('user_id', auth()->id())->findOrFail($id);

        $startup->update($request->validate([
            'nome_fantasia'    => 'sometimes|string|max:255',
            'faturamento_anual'=> 'sometimes|nullable|string',
            'trl_nivel'        => 'sometimes|nullable|string',
            'cnaes'            => 'sometimes|nullable|array',
            'pitch'            => 'sometimes|nullable|string|max:2000',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $startup->fresh(), // retorna dados atualizados do BD
            'message' => 'Startup atualizada com sucesso!',
        ], 200);
    }

    /**
     * DESTROY — Excluir uma startup
     * DELETE /api/startups/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $startup = Startup::where('user_id', auth()->id())->findOrFail($id);
        $startup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Startup removida com sucesso.',
        ], 200);
    }
}
```

**Comando para criar:**
```bash
php artisan make:controller StartupController
```

---

### 3.5 Rotas da API

> **Arquivo:** `routes/api.php`

```php
<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EditalController;
use App\Http\Controllers\StartupController;
use Illuminate\Support\Facades\Route;

// --- Rotas públicas (sem autenticação) ---
Route::post('/auth/cadastro', [AuthController::class, 'cadastro']);
Route::post('/auth/login',    [AuthController::class, 'login']);

// --- Rotas protegidas (exige Token Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // CRUD Startups (Resource = cria todas as 5 rotas automaticamente)
    Route::apiResource('startups', StartupController::class);

    // CRUD Editais
    Route::get('/editais',      [EditalController::class, 'index']);
    Route::get('/editais/{id}', [EditalController::class, 'show']);

});
```

**Verificar rotas registradas:**
```bash
php artisan route:list --path=api
```

---

## 4. Frontend: Estilização Padrão e Design System

> **Arquivo:** `public/css/app.css`
> Todos devem importar este arquivo. **Não crie estilos inline no HTML.**

```css
/* ===================================================
   DESIGN SYSTEM — RADAR DE EDITAIS
   Regra: Use as variáveis abaixo. Não invente cores.
   =================================================== */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root {
  /* --- Paleta de Cores --- */
  --color-bg:         #0f1117;   /* fundo geral escuro */
  --color-surface:    #1a1d2e;   /* cards e painéis */
  --color-border:     #2a2d3e;   /* bordas suaves */
  --color-primary:    #6c63ff;   /* roxo vibrante (ação principal) */
  --color-primary-h:  #8b83ff;   /* hover do primário */
  --color-success:    #22c55e;   /* verde (aprovado, sucesso) */
  --color-warning:    #f59e0b;   /* amarelo (pendente, atenção) */
  --color-danger:     #ef4444;   /* vermelho (erro, excluir) */
  --color-text:       #e2e8f0;   /* texto principal */
  --color-muted:      #64748b;   /* texto secundário/desativado */

  /* --- Tipografia --- */
  --font-base: 'Inter', system-ui, sans-serif;

  /* --- Espaçamento --- */
  --radius-sm:  6px;
  --radius-md:  10px;
  --radius-lg:  16px;
  --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.3);
}

/* --- Reset e Base --- */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: var(--font-base);
  background-color: var(--color-bg);
  color: var(--color-text);
  line-height: 1.6;
}
a { color: var(--color-primary); text-decoration: none; }
a:hover { color: var(--color-primary-h); }

/* --- Layout Principal --- */
.app-layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  min-height: 100vh;
}
.main-content {
  padding: 2rem;
  overflow-y: auto;
}

/* --- Sidebar --- */
.sidebar {
  background-color: var(--color-surface);
  border-right: 1px solid var(--color-border);
  padding: 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.sidebar-logo {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-primary);
  padding: 0.5rem;
  margin-bottom: 1rem;
}
.sidebar-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0.75rem;
  border-radius: var(--radius-sm);
  color: var(--color-muted);
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.15s ease;
}
.sidebar-link:hover,
.sidebar-link.active {
  background-color: rgba(108, 99, 255, 0.12);
  color: var(--color-primary);
}

/* --- Cards --- */
.card {
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 1.5rem;
  box-shadow: var(--shadow-card);
}
.card-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.card-meta {
  font-size: 0.8rem;
  color: var(--color-muted);
  margin-bottom: 0.75rem;
}

/* --- Botões --- */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.25rem;
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s ease;
}
.btn-primary {
  background-color: var(--color-primary);
  color: #fff;
}
.btn-primary:hover { background-color: var(--color-primary-h); }

.btn-outline {
  background-color: transparent;
  border: 1px solid var(--color-border);
  color: var(--color-text);
}
.btn-outline:hover { border-color: var(--color-primary); color: var(--color-primary); }

.btn-danger {
  background-color: var(--color-danger);
  color: #fff;
}
.btn-danger:hover { opacity: 0.85; }

.btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* --- Formulários --- */
.form-group { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1rem; }
.form-label { font-size: 0.85rem; font-weight: 500; color: var(--color-muted); }
.form-input,
.form-select,
.form-textarea {
  background-color: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  color: var(--color-text);
  padding: 0.65rem 0.85rem;
  font-size: 0.875rem;
  font-family: var(--font-base);
  width: 100%;
  transition: border-color 0.15s ease;
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.15);
}
.form-error { font-size: 0.8rem; color: var(--color-danger); margin-top: 0.25rem; }

/* --- Badges de Status --- */
.badge {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}
.badge-success  { background-color: rgba(34, 197, 94, 0.15);  color: var(--color-success); }
.badge-warning  { background-color: rgba(245, 158, 11, 0.15); color: var(--color-warning); }
.badge-primary  { background-color: rgba(108, 99, 255, 0.15); color: var(--color-primary); }
.badge-danger   { background-color: rgba(239, 68, 68, 0.15);  color: var(--color-danger); }

/* --- Grade de Cards --- */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.25rem;
}

/* --- Tabela --- */
.table-wrapper { overflow-x: auto; }
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}
thead th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--color-border);
}
tbody td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid var(--color-border);
}
tbody tr:hover { background-color: rgba(255,255,255,0.02); }

/* --- Toast / Notificação --- */
#toast-container {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  z-index: 9999;
}
.toast {
  padding: 0.9rem 1.25rem;
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  box-shadow: var(--shadow-card);
  animation: slideIn 0.3s ease;
}
.toast-success { background-color: var(--color-success); }
.toast-error   { background-color: var(--color-danger); }
.toast-info    { background-color: var(--color-primary); }

@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to   { transform: translateX(0);   opacity: 1; }
}

/* --- Loading Spinner --- */
.spinner {
  width: 20px; height: 20px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* --- Responsividade Mobile --- */
@media (max-width: 768px) {
  .app-layout {
    grid-template-columns: 1fr;
  }
  .sidebar {
    display: none; /* mostrar via JS com .sidebar.open */
  }
}
```

---

## 5. Frontend: Consumo da API com JavaScript

### 5.1 Função Central de Fetch (Usar em TODOS os módulos)

> **Arquivo:** `public/js/api.js`
> Todos devem importar e usar esta função. **Nunca escreva fetch() diretamente no código do módulo.**

```javascript
// Constante com a URL base da API
const API_URL = 'http://localhost:8000/api';

/**
 * Função central de requisição HTTP para a API REST.
 * Gerencia automaticamente o Token Bearer e trata erros.
 *
 * @param {string} endpoint  - Ex: '/startups', '/editais/5'
 * @param {string} method    - 'GET', 'POST', 'PUT', 'DELETE'
 * @param {object} body      - Dados a enviar (para POST/PUT). null para GET/DELETE.
 * @returns {Promise<object>} - O objeto JSON retornado pela API.
 */
async function apiRequest(endpoint, method = 'GET', body = null) {
    // Recupera o token do localStorage (salvo no login)
    const token = localStorage.getItem('auth_token');

    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    };

    // Adiciona o token apenas se existir
    if (token) {
        options.headers['Authorization'] = `Bearer ${token}`;
    }

    // Adiciona o corpo da requisição para POST e PUT
    if (body) {
        options.body = JSON.stringify(body);
    }

    try {
        const response = await fetch(`${API_URL}${endpoint}`, options);
        const resultado = await response.json();

        // Se a API retornou 401 (Token expirado), redireciona para login
        if (response.status === 401) {
            localStorage.removeItem('auth_token');
            window.location.href = '/pages/login.html';
            return;
        }

        return resultado;

    } catch (erro) {
        console.error(`[apiRequest] Falha ao conectar com a API: ${erro.message}`);
        mostrarToast('Erro de conexão com o servidor.', 'error');
        return { success: false, message: 'Erro de conexão.' };
    }
}

/**
 * Exibe uma notificação toast na tela.
 * @param {string} mensagem
 * @param {'success'|'error'|'info'} tipo
 */
function mostrarToast(mensagem, tipo = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.textContent = mensagem;
    container.appendChild(toast);

    // Remove o toast após 4 segundos
    setTimeout(() => toast.remove(), 4000);
}
```

---

### 5.2 Exemplo de Página HTML Completa (Listagem de Startups)

> **Arquivo:** `public/pages/startups.html`

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carteira de Startups — Radar de Editais</title>
  <link rel="stylesheet" href="../css/app.css">
</head>
<body>

<div class="app-layout">

  <!-- Sidebar de Navegação -->
  <aside class="sidebar">
    <div class="sidebar-logo">🎯 Radar</div>
    <a href="editais.html"  class="sidebar-link">📋 Editais</a>
    <a href="startups.html" class="sidebar-link active">🏢 Startups</a>
    <a href="kanban.html"   class="sidebar-link">📊 Kanban</a>
    <a href="perfil.html"   class="sidebar-link">👤 Perfil</a>
  </aside>

  <!-- Conteúdo Principal -->
  <main class="main-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem">
      <h1 style="font-size:1.5rem; font-weight:700">Carteira de Startups</h1>
      <button class="btn btn-primary" onclick="abrirModalCriar()">
        + Nova Startup
      </button>
    </div>

    <!-- Grade de Cards -->
    <div class="cards-grid" id="lista-startups">
      <p style="color: var(--color-muted)">Carregando...</p>
    </div>
  </main>
</div>

<!-- Modal de Criar/Editar Startup -->
<div id="modal-startup" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:500px;">
    <h2 class="card-title" id="modal-titulo">Nova Startup</h2>

    <form id="form-startup">
      <div class="form-group">
        <label class="form-label">CNPJ</label>
        <input type="text" id="input-cnpj" class="form-input" placeholder="00.000.000/0001-00">
        <span class="form-error" id="erro-cnpj"></span>
      </div>
      <div class="form-group">
        <label class="form-label">Nome Fantasia</label>
        <input type="text" id="input-nome" class="form-input" placeholder="Ex: Tech Solutions Ltda">
        <span class="form-error" id="erro-nome"></span>
      </div>
      <div class="form-group">
        <label class="form-label">Nível TRL</label>
        <select id="input-trl" class="form-select">
          <option value="">Selecionar...</option>
          <option value="TRL 1">TRL 1 — Princípios básicos</option>
          <option value="TRL 4">TRL 4 — Validado em laboratório</option>
          <option value="TRL 7">TRL 7 — Demonstrado em ambiente real</option>
          <option value="TRL 9">TRL 9 — Sistema aprovado/operacional</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Pitch (Resumo do Negócio)</label>
        <textarea id="input-pitch" class="form-textarea" rows="4" placeholder="Descreva o negócio em até 500 caracteres..."></textarea>
      </div>

      <div style="display:flex; gap:0.75rem; justify-content:flex-end">
        <button type="button" class="btn btn-outline" onclick="fecharModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btn-salvar">
          <span id="spinner-salvar"></span> Salvar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Container de Toasts -->
<div id="toast-container"></div>

<script src="../js/api.js"></script>
<script src="../js/startups.js"></script>
</body>
</html>
```

---

### 5.3 JavaScript do Módulo (CRUD Completo)

> **Arquivo:** `public/js/startups.js`

```javascript
// Estado local do módulo
let startupEditandoId = null;

// === INICIALIZAÇÃO ===
document.addEventListener('DOMContentLoaded', () => {
    verificarAutenticacao();
    carregarStartups();
    document.getElementById('form-startup').addEventListener('submit', salvarStartup);
});

// Redireciona para login se não houver token
function verificarAutenticacao() {
    if (!localStorage.getItem('auth_token')) {
        window.location.href = '/pages/login.html';
    }
}

// === LISTAR ===
async function carregarStartups() {
    const lista = document.getElementById('lista-startups');
    lista.innerHTML = '<p style="color:var(--color-muted)">Carregando...</p>';

    const resultado = await apiRequest('/startups');

    if (!resultado || !resultado.success) {
        lista.innerHTML = '<p style="color:var(--color-danger)">Erro ao carregar startups.</p>';
        return;
    }

    const startups = resultado.data.data; // paginação: .data.data

    if (startups.length === 0) {
        lista.innerHTML = '<p style="color:var(--color-muted)">Nenhuma startup cadastrada ainda.</p>';
        return;
    }

    // Renderiza os cards dinamicamente
    lista.innerHTML = startups.map(s => `
        <div class="card">
            <div class="card-title">${s.nome_fantasia}</div>
            <div class="card-meta">CNPJ: ${s.cnpj}</div>
            ${s.trl_nivel ? `<span class="badge badge-primary">${s.trl_nivel}</span>` : ''}
            <p style="font-size:0.85rem; color:var(--color-muted); margin-top:0.75rem">
                ${s.pitch ? s.pitch.substring(0, 100) + '...' : 'Sem pitch cadastrado.'}
            </p>
            <div style="display:flex; gap:0.5rem; margin-top:1rem">
                <button class="btn btn-outline" onclick="abrirModalEditar(${s.id})">✏️ Editar</button>
                <button class="btn btn-danger"  onclick="excluirStartup(${s.id})">🗑️ Excluir</button>
            </div>
        </div>
    `).join('');
}

// === CRIAR / EDITAR — Abrir Modal ===
function abrirModalCriar() {
    startupEditandoId = null;
    document.getElementById('modal-titulo').textContent = 'Nova Startup';
    document.getElementById('form-startup').reset();
    limparErros();
    document.getElementById('modal-startup').style.display = 'flex';
}

async function abrirModalEditar(id) {
    startupEditandoId = id;
    document.getElementById('modal-titulo').textContent = 'Editar Startup';

    const resultado = await apiRequest(`/startups/${id}`);
    if (!resultado.success) return;

    const s = resultado.data;
    document.getElementById('input-cnpj').value  = s.cnpj;
    document.getElementById('input-nome').value  = s.nome_fantasia;
    document.getElementById('input-trl').value   = s.trl_nivel || '';
    document.getElementById('input-pitch').value = s.pitch || '';

    document.getElementById('modal-startup').style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modal-startup').style.display = 'none';
}

// === SALVAR (Criar ou Editar) ===
async function salvarStartup(event) {
    event.preventDefault();
    limparErros();

    const btnSalvar  = document.getElementById('btn-salvar');
    const spinner    = document.getElementById('spinner-salvar');

    // Desativa o botão e exibe spinner durante a requisição
    btnSalvar.disabled = true;
    spinner.innerHTML  = '<span class="spinner"></span>';

    const payload = {
        cnpj:          document.getElementById('input-cnpj').value,
        nome_fantasia: document.getElementById('input-nome').value,
        trl_nivel:     document.getElementById('input-trl').value,
        pitch:         document.getElementById('input-pitch').value,
    };

    let resultado;

    if (startupEditandoId) {
        // PUT para atualizar
        resultado = await apiRequest(`/startups/${startupEditandoId}`, 'PUT', payload);
    } else {
        // POST para criar
        resultado = await apiRequest('/startups', 'POST', payload);
    }

    btnSalvar.disabled = false;
    spinner.innerHTML  = '';

    if (!resultado.success) {
        // Exibe erros de validação nos campos correspondentes
        if (resultado.errors) {
            if (resultado.errors.cnpj)          document.getElementById('erro-cnpj').textContent = resultado.errors.cnpj[0];
            if (resultado.errors.nome_fantasia)  document.getElementById('erro-nome').textContent = resultado.errors.nome_fantasia[0];
        }
        mostrarToast(resultado.message || 'Erro ao salvar.', 'error');
        return;
    }

    mostrarToast(resultado.message, 'success');
    fecharModal();
    carregarStartups(); // Recarrega a lista
}

// === EXCLUIR ===
async function excluirStartup(id) {
    if (!confirm('Tem certeza que deseja excluir esta startup? Esta ação não pode ser desfeita.')) return;

    const resultado = await apiRequest(`/startups/${id}`, 'DELETE');

    if (resultado.success) {
        mostrarToast('Startup removida com sucesso.', 'success');
        carregarStartups();
    } else {
        mostrarToast('Erro ao excluir startup.', 'error');
    }
}

// === UTILITÁRIO ===
function limparErros() {
    document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
}
```

---

## 6. Autenticação (Login, Cadastro e Token Sanctum)

### 6.1 Backend — AuthController

> **Arquivo:** `app/Http/Controllers/Auth/AuthController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // POST /api/auth/cadastro
    public function cadastro(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed', // exige password_confirmation
        ]);

        $user = User::create([
            'name'     => $dados['name'],
            'email'    => $dados['email'],
            'password' => Hash::make($dados['password']),
        ]);

        // Cria um token de acesso pessoal para o novo usuário
        $token = $user->createToken('radar_editais')->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => ['token' => $token, 'user' => $user],
            'message' => 'Cadastro realizado! Bem-vindo ao Radar.',
        ], 201);
    }

    // POST /api/auth/login
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Verifica se o usuário existe e a senha está correta
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'E-mail ou senha incorretos.',
            ], 401);
        }

        // Deleta tokens antigos e cria um novo (1 sessão por vez)
        $user->tokens()->delete();
        $token = $user->createToken('radar_editais')->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => ['token' => $token, 'user' => $user],
            'message' => 'Login realizado com sucesso!',
        ], 200);
    }

    // POST /api/auth/logout (protegido)
    public function logout(Request $request): JsonResponse
    {
        // Revoga o token atual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso.',
        ], 200);
    }
}
```

### 6.2 Frontend — auth.js

> **Arquivo:** `public/js/auth.js`

```javascript
// === LOGIN ===
async function fazerLogin(event) {
    event.preventDefault();

    const email    = document.getElementById('input-email').value;
    const password = document.getElementById('input-password').value;

    const resultado = await apiRequest('/auth/login', 'POST', { email, password });

    if (resultado.success) {
        // Salva o token e os dados do usuário no LocalStorage
        localStorage.setItem('auth_token', resultado.data.token);
        localStorage.setItem('auth_user',  JSON.stringify(resultado.data.user));

        mostrarToast('Login realizado!', 'success');
        window.location.href = '/pages/editais.html'; // redireciona
    } else {
        document.getElementById('erro-login').textContent = resultado.message;
    }
}

// === LOGOUT ===
async function fazerLogout() {
    await apiRequest('/auth/logout', 'POST');
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
    window.location.href = '/pages/login.html';
}

// === DADOS DO USUÁRIO LOGADO ===
function getUsuarioLogado() {
    const user = localStorage.getItem('auth_user');
    return user ? JSON.parse(user) : null;
}
```

---

## 7. Filtros, Busca e Paginação

### 7.1 Backend — Filtros no Controller

```php
public function index(Request $request): JsonResponse
{
    $query = Edital::query();

    // Busca textual: ?search=inovação
    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('titulo',   'like', '%' . $request->search . '%')
              ->orWhere('objetivo', 'like', '%' . $request->search . '%');
        });
    }

    // Filtro por fonte: ?fonte[]=FINEP&fonte[]=FAPESC
    if ($request->filled('fonte') && is_array($request->fonte)) {
        $query->whereIn('fonte', $request->fonte);
    }

    // Filtro por público: ?publico[]=Empresa&publico[]=ICT
    if ($request->filled('publico') && is_array($request->publico)) {
        $query->where(function ($q) use ($request) {
            foreach ($request->publico as $index => $pub) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $q->$method('publico', 'like', '%' . $pub . '%');
            }
        });
    }

    $editais = $query->latest('data_publicacao')
                     ->paginate(12)
                     ->withQueryString(); // mantém os filtros na paginação

    return response()->json(['success' => true, 'data' => $editais], 200);
}
```

### 7.2 Frontend — Busca e Paginação

```javascript
let paginaAtual = 1;

async function carregarEditais() {
    const search  = document.getElementById('input-busca').value;
    const fontes  = [...document.querySelectorAll('input[name="fonte"]:checked')].map(el => el.value);

    // Monta a query string com os filtros
    const params = new URLSearchParams();
    if (search)        params.append('search', search);
    if (fontes.length) fontes.forEach(f => params.append('fonte[]', f));
    params.append('page', paginaAtual);

    const resultado = await apiRequest(`/editais?${params.toString()}`);

    if (!resultado.success) return;

    renderizarEditais(resultado.data.data);
    renderizarPaginacao(resultado.data);
}

function renderizarPaginacao(paginacao) {
    const container = document.getElementById('paginacao');
    container.innerHTML = `
        <button class="btn btn-outline"
            onclick="irParaPagina(${paginacao.current_page - 1})"
            ${paginacao.current_page <= 1 ? 'disabled' : ''}>
            ← Anterior
        </button>
        <span style="color:var(--color-muted)">
            Página ${paginacao.current_page} de ${paginacao.last_page}
        </span>
        <button class="btn btn-outline"
            onclick="irParaPagina(${paginacao.current_page + 1})"
            ${paginacao.current_page >= paginacao.last_page ? 'disabled' : ''}>
            Próxima →
        </button>
    `;
}

function irParaPagina(pagina) {
    paginaAtual = pagina;
    carregarEditais();
}
```

---

## 8. Upload de Arquivos (PDFs e Imagens)

### 8.1 Backend

```php
// No Controller:
public function uploadPdf(Request $request): JsonResponse
{
    $request->validate([
        'arquivo' => 'required|file|mimes:pdf|max:10240', // máx 10MB
    ]);

    // Salva na pasta storage/app/public/editais_pdf/
    $caminho = $request->file('arquivo')->store('editais_pdf', 'public');

    return response()->json([
        'success' => true,
        'data'    => ['caminho' => $caminho],
        'message' => 'Arquivo enviado com sucesso.',
    ], 201);
}
```

### 8.2 Frontend

```javascript
async function enviarPdf(event) {
    event.preventDefault();
    const arquivo = document.getElementById('input-pdf').files[0];
    if (!arquivo) return;

    // Para upload de arquivo, usa FormData (não JSON)
    const formData = new FormData();
    formData.append('arquivo', arquivo);

    const token = localStorage.getItem('auth_token');
    const response = await fetch(`${API_URL}/editais/upload-pdf`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` },
        // ⚠️ NÃO definir Content-Type aqui — o navegador define automaticamente
        body: formData,
    });

    const resultado = await response.json();
    if (resultado.success) {
        mostrarToast('PDF enviado! Processando em segundo plano...', 'info');
    }
}
```

---

## 9. WebSockets: Notificações em Tempo Real

### 9.1 Backend — Disparar um Evento

```php
// Criar o evento:
// php artisan make:event PropostaGeradaEvent

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PropostaGeradaEvent implements ShouldBroadcast
{
    public function __construct(
        public int    $userId,
        public string $mensagem,
        public array  $dados = []
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        // Canal privado por usuário: "usuario.{id}"
        return new PrivateChannel("usuario.{$this->userId}");
    }

    // Nome do evento que o JavaScript vai ouvir
    public function broadcastAs(): string
    {
        return 'proposta.gerada';
    }
}

// Disparar o evento (no Job ou Controller):
broadcast(new PropostaGeradaEvent(
    userId: auth()->id(),
    mensagem: 'Sua minuta de proposta foi gerada!',
));
```

### 9.2 Frontend — Ouvir Eventos com Echo

```javascript
// Instala Laravel Echo e Pusher:
// npm install laravel-echo pusher-js

// No JS, após o login:
function iniciarWebSocket(userId) {
    const echo = new Echo({
        broadcaster: 'reverb',
        key: 'sua-chave-reverb',
        wsHost: 'localhost',
        wsPort: 8080,
        forceTLS: false,
    });

    // Ouve o canal privado do usuário logado
    echo.private(`usuario.${userId}`)
        .listen('.proposta.gerada', (evento) => {
            console.log('Evento recebido:', evento);
            mostrarToast(evento.mensagem, 'success');

            // Atualiza a tela com os dados do evento
            if (evento.dados.texto_minuta) {
                document.getElementById('area-minuta').textContent += evento.dados.texto_minuta;
            }
        });
}
```

---

## 10. Filas e Jobs Assíncronos (Queues)

### 10.1 Criar e Disparar um Job

```php
// Criar o job:
// php artisan make:job ProcessarPdfEdital

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessarPdfEdital implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public int    $editalId,
        public string $caminhoPdf
    ) {}

    // Este método roda em segundo plano (via worker)
    public function handle(): void
    {
        // 1. Lê o PDF e extrai texto
        // 2. Envia para a IA analisar
        // 3. Salva o resultado no banco
        // 4. Notifica o usuário via WebSocket
    }
}

// Disparar no Controller (retorna imediatamente ao usuário):
ProcessarPdfEdital::dispatch($edital->id, $caminho);
```

**Rodar o worker de filas:**
```bash
php artisan queue:work --tries=3
```

---

## 11. Testes Automatizados (PHPUnit)

### 11.1 Teste de Endpoint da API

> **Arquivo:** `tests/Feature/StartupTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartupTest extends TestCase
{
    use RefreshDatabase; // Reseta o BD antes de cada teste

    private User $user;

    // Configuração que roda antes de cada teste
    protected function setUp(): void
    {
        parent::setUp();
        // Cria um usuário de teste com Sanctum
        $this->user = User::factory()->create();
    }

    /** @test */
    public function usuario_pode_criar_startup(): void
    {
        $response = $this->actingAs($this->user) // simula login
            ->postJson('/api/startups', [
                'cnpj'          => '12.345.678/0001-99',
                'nome_fantasia' => 'Startup Teste Ltda',
                'trl_nivel'     => 'TRL 4',
            ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true])
                 ->assertJsonPath('data.nome_fantasia', 'Startup Teste Ltda');

        // Verifica se o registro foi criado no BD
        $this->assertDatabaseHas('startups', [
            'cnpj' => '12.345.678/0001-99',
        ]);
    }

    /** @test */
    public function usuario_nao_autenticado_nao_pode_listar_startups(): void
    {
        $response = $this->getJson('/api/startups');
        $response->assertStatus(401);
    }

    /** @test */
    public function campos_obrigatorios_sao_validados(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/startups', []); // payload vazio

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['cnpj', 'nome_fantasia']);
    }
}
```

**Comandos para rodar os testes:**
```bash
# Rodar todos os testes
php artisan test

# Rodar apenas o arquivo específico
php artisan test tests/Feature/StartupTest.php

# Rodar com detalhes verbosos
php artisan test --verbose
```

---

## 12. Glossário Técnico para a Banca

Estude e saiba explicar cada conceito abaixo com suas palavras:

| Termo | Explicação Simples |
| --- | --- |
| **API REST Stateless** | "O servidor não guarda sessão. Cada requisição deve enviar o Token de autenticação no cabeçalho `Authorization`." |
| **Laravel Sanctum** | "Biblioteca oficial do Laravel que gera Tokens Bearer para autenticar as requisições da API sem usar cookies de sessão." |
| **Eloquent ORM** | "Ferramenta do Laravel que converte tabelas do banco de dados em Classes PHP (Models), permitindo fazer consultas sem escrever SQL puro." |
| **Migration** | "Arquivo PHP que descreve a estrutura de uma tabela. É como um controle de versão para o banco de dados. `php artisan migrate` executa as migrações." |
| **Form Request** | "Classe PHP que centraliza as regras de validação de um formulário, separando essa responsabilidade do Controller." |
| **Queue / Job** | "Sistema de fila de tarefas. Tarefas pesadas (como ler PDFs e chamar APIs de IA) são enviadas para a fila e processadas em segundo plano sem travar a tela." |
| **WebSocket** | "Canal de comunicação permanente e bidirecional. O servidor pode 'empurrar' dados para o navegador sem que o usuário precise fazer um novo pedido (diferente do HTTP normal)." |
| **CORS** | "Política de segurança do navegador que autoriza requisições de um domínio/porta diferente. Configurado no Laravel para permitir que o Frontend consuma a API." |
| **Fetch API** | "Recurso nativo do JavaScript para fazer requisições HTTP assíncronas de forma simples usando `async/await`." |
| **Bearer Token** | "Token de acesso enviado no cabeçalho HTTP `Authorization: Bearer {token}`. É a 'chave de entrada' para as rotas protegidas da API." |
| **`$fillable`** | "Array na Model do Laravel que define quais campos podem ser preenchidos em massa (`Model::create([...])`). Protege contra atribuição em massa não autorizada." |
| **`updateOrCreate`** | "Método Eloquent que busca um registro pela condição e o atualiza se existir, ou cria um novo se não existir. Garante idempotência." |
| **Idempotência** | "Propriedade que garante que executar a mesma operação múltiplas vezes produz o mesmo resultado sem duplicar dados." |
