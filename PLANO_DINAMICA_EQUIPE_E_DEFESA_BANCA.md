# 🛡️ Plano de Dinâmica da Equipe e Estratégia de Defesa para a Banca (UniCesumar)

Este documento estabelece a metodologia de trabalho, a divisão funcional e o plano de capacitação técnica da equipe para o desenvolvimento do projeto **Radar de Editais B2B** no formato **API Stateless (Backend Laravel + Frontend Separado)**, com foco absoluto na **aprovação individual de todos os 4 integrantes perante a banca examinadora**.

---

## 🎯 Contexto & Desafio Acadêmico

* **Formato da Aplicação:** API REST Stateless (Backend Laravel 12 desacoplado do Frontend HTML5/CSS3/JavaScript).
* **Composição do Grupo (4 Alunos):**
  * **1 Aluno (Tech Lead):** Conhecimento intermediário em PHP/Laravel e básico em JS/CSS.
  * **3 Alunos:** Conhecimento básico em HTML, CSS, JS e iniciando no ecossistema PHP/Laravel.
* **Prazo:** 1 Trimestre + 40% do segundo trimestre (~17 semanas / ~4,2 meses).
* **Regra Crítica da Banca:** Cada aluno será arguido **individualmente** pelos professores examinadores. Para passar, todo integrante deve entender o funcionamento geral do negócio e dominar tecnicamente a camada de código do seu módulo (do Controller ao JavaScript).

---

## 💡 A Metodologia: "Fullstack por Módulo + Código Espelho"

Para evitar que os alunos iniciantes fiquem restritos apenas a HTML/CSS e sejam reprovados na arguição de código da banca, adotaremos a abordagem **Fullstack Vertical por Módulo**:

1. **Golden Template (Código Molde):** O Tech Lead constrói o **Módulo 1 completo** nas duas primeiras semanas. Este código servirá como referência didática padronizada de como criar uma Rota, Controller, Model, Request e consumo via `fetch()` em JavaScript.
2. **Propriedade Vertical (Ownership):** Cada integrante será o "dono" de 1 a 2 módulos completos, programando tanto o Backend (Laravel) quanto o Frontend (HTML/JS) do seu módulo com o suporte do Tech Lead.

---

## 👥 Divisão de Atribuições & Preparação para a Arguição da Banca

```
                       ┌─────────────────────────────────────────┐
                       │   Tech Lead (Aluno 1): Módulo Modelo    │
                       │  (Arquitetura Base, Sanctum, Scraping)  │
                       └────────────────────┬────────────────────┘
                                            │ (Referência Didática)
         ┌──────────────────────────────────┼──────────────────────────────────┐
         ▼                                  ▼                                  ▼
 👤 Aluno 2 (Módulo Auth/Perfil)    👤 Aluno 3 (Módulo Editais)      👤 Aluno 4 (Módulo Kanban)
 ├── Back: Auth & Perfil            ├── Back: Busca & Filtros        ├── Back: Clientes & Kanban
 └── Front: Form Login & Perfil     └── Front: Cards & Filtros       └── Front: Quadro Kanban
```

---

### 👤 Integrante 1 (Tech Lead) - Arquitetura, IA & Scraping Avançado
* **Atribuições de Código:**
  * Configuração da infraestrutura Stateless no Laravel 12 (Sanctum, CORS e JSON Standard).
  * Desenvolvimento do **Módulo 1 (Golden Template)**.
  * Motor de Web Scraping (`FinepSpider` com Roach PHP e Spatie Browsershot).
  * Geração de Minutas por Inteligência Artificial (Google Gemini API).
  * Comunicação em tempo real com WebSockets (`Laravel Reverb`).
* **Perguntas que defenderá na Banca:**
  * *"Como funciona a arquitetura Stateless e o desacoplamento da API?"*
  * *"Como o robô de scraping supera a paginação AJAX do Liferay da FINEP?"*
  * *"Como é feita a transmissão do streaming de dados da IA via WebSocket?"*

---

### 👤 Integrante 2 - Módulo de Autenticação, Perfil e Créditos
* **Atribuições de Código:**
  * **Backend:** Controladores e rotas de Cadastro, Autenticação, Atualização de Perfil de Startup e Saldo de Créditos (`RF-01` a `RF-07`, `RF-13` e `RF-20`).
  * **Frontend:** Telas de Login, Registro, Formulário de Perfil da Empresa e Indicador de Progresso em JS/HTML/CSS.
* **Perguntas que defenderá na Banca:**
  * *"Como funciona a autenticação via Token Bearer com Laravel Sanctum?"*
  * *"Onde e como a senha do usuário é criptografada no banco (`Hash::make`)?"*
  * *"Como o Frontend salva e envia o token de sessão em cada requisição (`localStorage` e `Authorization Header`)?"*

---

### 👤 Integrante 3 - Módulo de Listagem de Editais, Filtros e Favoritos
* **Atribuições de Código:**
  * **Backend:** Endpoints de consulta de editais com filtros combinados (`search`, `publico[]`, `fonte[]`) e funcionalidade de favoritar (`RF-08` a `RF-11`).
  * **Frontend:** Interface visual da vitrine de editais, modal de detalhes, barra de pesquisa e filtros dinâmicos via JavaScript.
* **Perguntas que defenderá na Banca:**
  * *"Como o Eloquent ORM constrói as buscas dinâmicas (`$query->where(...)`) e a paginação (`->paginate()`)?"*
  * *"Como o JavaScript consome o endpoint `GET /api/editais` e renderiza os cards no DOM usando a Fetch API?"*
  * *"Como são lidos os parâmetros de URL (Query Parameters) na API REST?"*

---

### 👤 Integrante 4 - Módulo de Carteira de Clientes e Pipeline Kanban
* **Atribuições de Código:**
  * **Backend:** Endpoints para cadastro da carteira de startups clientes e atualização de estágios no Kanban (`RF-22` ao `RF-24`).
  * **Frontend:** Interface da tabela de clientes e quadro Kanban com arraste de cards (*drag-and-drop*).
* **Perguntas que defenderá na Banca:**
  * *"Como funcionam os relacionamentos entre tabelas no Eloquent (`hasMany` e `belongsTo`)?"*
  * *"Qual a diferença prática entre os métodos HTTP `GET`, `POST`, `PUT` e `DELETE`?"*
  * *"Como o Controller recebe e valida a alteração de status do Kanban vinda do Frontend?"*

---

## 📚 Glossário Técnico Obrigatório para a Banca

Todo integrante da equipe deve ter estes conceitos na ponta da língua:

| Conceito Técnico | Explicação Simples para a Banca |
| --- | --- |
| **API Stateless** | "É uma arquitetura onde o servidor não armazena o estado/sessão do usuário. Cada requisição enviada pelo cliente deve conter todas as informações necessárias para ser identificada (ex: Token Sanctum)." |
| **Verbos HTTP (REST)** | "`GET` é usado para buscar dados, `POST` para criar registros, `PUT/PATCH` para atualizar e `DELETE` para remover." |
| **Eloquent ORM** | "É a camada do Laravel que mapeia tabelas do banco de dados MySQL/SQLite em Objetos PHP, permitindo fazer consultas sem escrever SQL puro." |
| **CORS (Cross-Origin Resource Sharing)** | "É a política de segurança do navegador que autoriza o nosso Frontend em porta/domínio diferente a consumir os dados da nossa API Laravel." |
| **Fetch API & JSON** | "É a tecnologia nativa do JavaScript usada no navegador para fazer requisições assíncronas HTTP para a API Laravel e receber a resposta em formato JSON." |

---

## 🗓️ Cronograma em 5 Fases (~17 Semanas)

```
[Semanas 1 - 4]  ──> FASE 1: Golden Template (Tech Lead) + Setup de Perfil e Auth (Aluno 2)
[Semanas 5 - 8]  ──> FASE 2: Engine de Scraping (Tech Lead) + Tela de Editais e Filtros (Aluno 3)
[Semanas 9 - 12] ──> FASE 3: Score de Match (Tech Lead) + Kanban e Carteira de Clientes (Aluno 4)
[Semanas 13 - 15]──> FASE 4: Módulo de IA (Tech Lead) + Notificações e Minutas (Todos)
[Semanas 16 - 17]──> FASE 5: WebSockets, Simulação de Banca Falsa & Ajustes Finais
```

---

## 🛡️ Ritos de Proteção para a Defesa

1. **Regra do Comentário Didático:** Todo aluno deve incluir comentários explicativos nas funções PHP e blocos de código JS que escrever, descrevendo com suas próprias palavras o que a linha faz.
2. **Reunião Semanal de Código Cruzado (Code Walkthrough):** Toda semana, 1 integrante diferente abre o seu código para o grupo e explica como implementou sua funcionalidade.
3. **Simulações de "Banca Falsa" (Semanas 15 e 17):**
   * O Tech Lead e o grupo simulam a postura da banca examinadora da UniCesumar.
   * Serão feitas perguntas aleatórias sobre rotas, métodos HTTP, banco de dados e lógica JavaScript para testar a segurança de cada aluno antes da apresentação oficial.
