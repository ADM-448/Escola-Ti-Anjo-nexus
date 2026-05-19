
# Anjo Nexus Backend - Módulo de Captação & Inteligência Preditiva (`branch: developer`)

Este repositório concentra a inteligência core do ecossistema **Anjo Nexus / AnjoNexus**. A arquitetura foi desenhada como um monolito híbrido robusto, hospedado em infraestrutura de nuvem, que desempenha três papéis fundamentais:
1. **Extração e Higienização (ETL):** Automação server-side via `Roach PHP` e `Browsershot` para varredura completa do portal da FINEP.
2. **Dashboard de Operação Web:** Relação nativa via **Laravel Blade, HTML5 e CSS3** para auditoria, disparo de robôs e logs.
3. **Engine de API RESTful:** Distribuição de endpoints seguros (MySQL) e integração cognitiva com a **DeepSeek AI** para servir o aplicativo mobile em React Native.

---

## 🏗️ Arquitetura do Ecossistema

O sistema opera de forma desacoplada para otimizar os recursos do servidor em nuvem:

* **Painel Administrativo (Web):** Renderizado diretamente pelo servidor utilizando rotas Web (`routes/web.php`) alimentadas por templates Blade estilizados com CSS3 puro.
* **Distribuição de Dados (API):** Rotas de API (`routes/api.php`) que expõem os editais limpos do banco de dados MySQL no formato JSON.
* **Orquestração Cognitiva (IA):** Pipeline que recebe a requisição do Mobile, monta o contexto baseado no perfil da indústria e dispara engenharia de prompt para a API do DeepSeek gerar relatórios de pitch em background.

---

## 🛠️ Requisitos e Stack Tecnológica

* **PHP 8.2+** & **Laravel 11+**
* **MySQL 8.0+** (Camada de persistência relacional)
* **Node.js** & **Puppeteer** (Para emulação do motor V8 do Chromium via Browsershot)
* **Roach PHP** & **Symfony DomCrawler** (Ciclo de vida dos Spiders e parsing do DOM)

---

## 🚀 Instalação e Configuração Local (Do Zero)

Siga os passos abaixo para configurar o ambiente limpo na sua máquina e garantir a integridade das dependências:

### 1. Clonar e Instalar Dependências Core
Com o projeto criado na sua pasta de desenvolvimento, instale os pacotes PHP via Composer:
```bash
composer install

```

### 2. Configurar a Integração de Automação (Node.js)

Instale o Puppeteer localmente na raiz do projeto Laravel para habilitar a renderização do Browsershot:

```bash
npm install puppeteer

```

### 3. Configuração do Arquivo de Ambiente (`.env`)

Duplique o arquivo `.env.example`, gere a chave de criptografia do Laravel e configure suas credenciais locais do MySQL e as chaves da API da DeepSeek:

```bash
cp .env.example .env
php artisan key:generate

```

Abra o arquivo `.env` e ajuste as variáveis estruturais:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=anjonexus2
DB_USERNAME=root
DB_PASSWORD=sua_senha_aqui

QUEUE_CONNECTION=database

DEEPSEEK_API_KEY=sk-acfbfa8dfeab4755a5c274e0d612b713
DEEPSEEK_BASE_URL=https://api.deepseek.com/v1
```

### 4. Estruturar o Banco de Dados (MySQL)

Crie o banco de dados `anjonexus2` no seu gerenciador (XAMPP/MySQL Workbench) e execute as migrations para subir a estrutura de tabelas:

```bash
php artisan migrate

```

### 5. Inicializar o Servidor Nuvem Local

```bash
php artisan serve

```

* O Painel Administrativo Web (Blade) estará disponível em: `http://localhost:8000`

---

## 🤖 Execução dos Módulos Integrados

### Disparar a Esteira Coletora (FinepSpider)

Para rodar o script que injeta o código de monitoramento de alteração estática do DOM e captura as dezenas de páginas da FINEP em lote:

```bash
php artisan roach:run FinepSpider

```

### Validar Endpoint para o Frontend Mobile (React Native)

O aplicativo móvel irá consumir os dados de Inteligência Artificial realizando requisições assíncronas via Axios para o endpoint abaixo:

* **Rota:** `POST http://localhost:8000/api/v1/pitch-report`
* **Payload de Entrada (JSON):**

```json
{
  "edital_id": "FINEP-Mais-Inovacao-2026",
  "perfil_empresa": "Indústria Metal-Mecânica PME"
}

```

* **Retorno Estruturado (JSON):** Devolve a Análise de Viabilidade (Fit Score), a Defesa Técnica de Inovação gerada pelo DeepSeek AI e o Plano de Aplicação estruturado pronto para consumo da interface portátil.

```json
{
  "status": "success",
  "origin": "Servidor Nuvem (API Laravel)",
  "payload": {
    "message": "Hello World do ecossistema de inteligência preditiva!",
    "match_analysis": {
      "fit_score": "94%",
      "status_edital": "Compatível"
    }
  }
}

```
## 🛰️ Testando a API REST (Simulação Mobile)

Como o endpoint da API é do tipo **`POST`**, você deve enviar uma requisição estruturada contendo as informações que o aplicativo móvel usará para cruzar os dados com a inteligência artificial.

### Como configurar no Postman / Insomnia:
1. Altere o método HTTP de `GET` para **`POST`**.
2. Insira a URL local: `http://localhost:8000/api/v1/pitch-report`
3. Vá até a aba **Body** (Corpo), selecione a opção **raw** e mude o formato para **JSON**.
4. Cole o objeto JSON abaixo no corpo da requisição:

```json
{
  "edital_id": "FINEP-Mais-Inovacao-2026",
  "perfil_empresa": "Indústria Metal-Mecânica PME"
}
---

💡 **Nota de Desenvolvimento:** Este repositório representa a branch de desenvolvimento (`developer`). Certifique-se de manter o arquivo `.env` fora do controle de versão (Git) para proteger as credenciais de banco e chaves privadas da API de inteligência artificial.

```
