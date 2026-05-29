# 🎮 GameReview API — Rotas para Teste

## 🌐 Base URL

| Ambiente | URL |
|----------|-----|
| **Local** | `http://localhost:8000` |
| **Railway** | `https://web-production-a3ec9.up.railway.app` |

---

## 🔐 Autenticação

### POST /api/login
Gera um token de acesso.

**URL:** `POST /api/login`

**Body (JSON):**
```json
{
    "email": "usuario@esoft.com",
    "password": "Abc123"
}
```

**Resposta 200:**
```json
{
    "token": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
}
```

---

## 🎮 Jogos

> ⚡ Todas as rotas abaixo são **públicas** — não precisam de token.

---

### GET /api/jogos
Retorna a lista completa de jogos.

**URL:** `GET /api/jogos`

**Resposta 200:**
```json
[
    {
        "id": 1,
        "nome": "The Legend of Zelda",
        "tipo": "Aventura",
        "nota": 10,
        "review": "Um clássico absoluto."
    },
    {
        "id": 2,
        "nome": "FIFA 23",
        "tipo": "Esporte",
        "nota": 7,
        "review": "Bom para jogar com amigos."
    }
]
```

---

### GET /api/jogos/{id}
Busca um jogo pelo ID.

**URL:** `GET /api/jogos/1`

**Resposta 200:**
```json
{
    "id": 1,
    "nome": "The Legend of Zelda",
    "tipo": "Aventura",
    "nota": 10,
    "review": "Um clássico absoluto."
}
```

**Resposta 404:**
```json
{
    "message": "Jogo não encontrado."
}
```

---

### POST /api/jogos
Cadastra um novo jogo.

**URL:** `POST /api/jogos`

**Body (JSON):**
```json
{
    "nome": "God of War",
    "tipo": "Ação",
    "nota": 10,
    "review": "Épico do início ao fim."
}
```

**Resposta 201:**
```json
{
    "id": 3,
    "nome": "God of War",
    "tipo": "Ação",
    "nota": 10,
    "review": "Épico do início ao fim."
}
```

**Resposta 422 (campos inválidos):**
```json
{
    "message": "Dados inválidos.",
    "errors": {
        "nome": ["The nome field is required."]
    }
}
```

---

### PUT /api/jogos/{id}
Atualiza todos os dados de um jogo.

**URL:** `PUT /api/jogos/1`

**Body (JSON):**
```json
{
    "nome": "Zelda: Tears of the Kingdom",
    "tipo": "Aventura",
    "nota": 10,
    "review": "Melhor jogo da geração."
}
```

**Resposta 200:**
```json
{
    "id": 1,
    "nome": "Zelda: Tears of the Kingdom",
    "tipo": "Aventura",
    "nota": 10,
    "review": "Melhor jogo da geração."
}
```

---

### DELETE /api/jogos/{id}
Remove um jogo.

**URL:** `DELETE /api/jogos/1`

**Resposta:** `204 No Content` (sem corpo)

**Resposta 404:**
```json
{
    "message": "Jogo não encontrado."
}
```

---

## 📋 Resumo Rápido

| Método | Rota | Descrição | Status |
|--------|------|-----------|--------|
| POST | `/api/login` | Autenticar | 200 |
| GET | `/api/jogos` | Listar todos | 200 |
| GET | `/api/jogos/{id}` | Buscar por ID | 200 / 404 |
| POST | `/api/jogos` | Criar jogo | 201 / 422 |
| PUT | `/api/jogos/{id}` | Atualizar jogo | 200 / 404 / 422 |
| DELETE | `/api/jogos/{id}` | Remover jogo | 204 / 404 |


