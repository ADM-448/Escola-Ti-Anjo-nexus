Aqui está um modelo de `README.md` completo, técnico e altamente profissional. Ele documenta toda a jornada de engenharia reversa, os desafios enfrentados com a infraestrutura da FINEP (Liferay) e a arquitetura da solução robusta que você implementou.

---

# FinepSpider - Módulo de Captura Automatizada (ETL)

Este módulo é responsável pela extração, transformação e carga (ETL) dos dados de editais e linhas de financiamento diretamente do portal da **FINEP** para alimentar o ecossistema do **AnjoNexus / Margem Exata**.

A solução utiliza uma arquitetura híbrida de raspagem de dados, combinando o ecossistema de spiders do **Roach PHP** com a renderização dinâmica de Javascript através do **Spatie Browsershot (Puppeteer/Node.js)**.

---

## 🛠️ Tecnologias e Dependências Core

* **Laravel 11+** (Framework principal)
* **Roach PHP** (Engine de gerenciamento de Spiders e Middleware de Carga)
* **Spatie Browsershot / Puppeteer** (Emulação de navegador Chromium headless)
* **Symfony DomCrawler** (Parseamento de nós HTML estruturados)
* **Node.js & Chrome Server-Side** (Infraestrutura de execução)

---

## 📈 A Jornada de Engenharia de Dados (Desafios vs. Soluções)

O desenvolvimento deste spider passou por uma série de refatorações estratégicas para superar os mecanismos de proteção, obsolescência e instabilidade do portal da FINEP (baseado na plataforma corporativa *Liferay*).

| Fase / Desafio | O que foi tentado | O que quebrou? | Solução Adotada (Nível Júnior Plus/Pleno) |
| --- | --- | --- | --- |
| **1. Fonte de Dados Incompleta** | Consumir a API JSON interna de Chamadas Públicas (`/o/c/chamadapublicas`). | Os dados estratégicos (*Objetivo, Público-Alvo, Condições*) vinham como `NULL`, pois pertencem a outra estrutura do site. | **Pivot de Arquitetura:** Alteração do alvo para a vitrine principal de `/oportunidades`, onde a FINEP exibe os dados ricos consolidados. |
| **2. Paginação Invisível (AJAX)** | Navegação padrão por troca de URLs de paginação. | O portal utiliza SPA/AJAX (Cenário B). A URL não muda ao avançar de página, limitando a raspagem a apenas 8 registros. | **JS Injection:** Injeção de scripts assíncronos customizados via Puppeteer para simular cliques físicos no botão dinâmico de avançar (`.lexicon-icon-angle-right`). |
| **3. Relógio-Bomba do Chrome** | Execução de laços de repetição síncronos dentro do navegador. | `ProtocolError: Runtime.evaluate timed out`. O motor do Chrome explodia ao segurar a conexão por mais de 3 minutos. | **Desarme de Protocolo & Promises:** Configuração do `protocolTimeout` para 10 minutos e conversão do script JS em uma `Promise` gerenciável. |
| **4. Condição de Corrida (Race Condition)** | Validar o carregamento checando a mudança do número da página ativa no rodapé. | O Liferay atualiza o número da página *antes* de renderizar os novos cards na tela. O robô capturava dados duplicados. | **Ancoragem de DOM Total:** O robô passou a monitorar o estado do HTML inteiro do bloco de cards, esperando a mutação completa do layout antes de prosseguir. |
| **5. Elementos Fixados (Pinned Cards)** | Validar o carregamento checando a mudança de título do primeiro card. | A FINEP fixa editais de destaque no topo. O título do primeiro card nunca mudava, gerando loops infinitos. | **Rastreador de HTML Dinâmico:** Implementação de um monitor de assinatura estática do contêiner `.lista-cards`. |
| **6. Colisão de Chaves (Upsert Mismatch)** | Gerar a chave única (`external_id`) usando apenas a URL do edital. | Múltiplos programas diferentes apontam para o mesmo link geral, fazendo o banco de dados sobrescrever registros legítimos. | **Blindagem de Chave Composta:** Criação de um hash `md5()` combinando de forma única o `Título + URL` do edital. |

---

## 🏗️ Detalhes da Arquitetura Implementada

A versão final do `FinepSpider` opera sob o conceito de **Cofre Virtual Assíncrono**. Em vez de fazer o PHP gerenciar dezenas de requisições HTTP individuais lentas, delegamos o trabalho pesado de navegação ao motor V8 do Chromium em uma única seção.

```
[FinepSpider] ──> Instancia o Browsershot ──> Abre a Vitrine (/oportunidades)
                                                      │
 ┌────────────────────────────────────────────────────┘
 ▼
[JS Promise Injetada]
 │
 ├── 1. Copia o HTML dos 8 cards da tela
 ├── 2. Joga as strings dentro de uma <div id="cofre-de-cards"> invisível
 ├── 3. Executa o clique na setina de avançar
 ├── 4. Monitora o DOM. Se o HTML mutar ──> Repete o ciclo
 └── 5. Fim da paginação? Dá um resolve() com o HTML acumulado de TODAS as páginas
                                                      │
 ┌────────────────────────────────────────────────────┘
 ▼
[Symfony DomCrawler] ──> Faz o parse de centenas de cards salvos no cofre
 │
 ├── Limpeza de Tooltips (Injeções de regex eliminam sujeiras textuais do botão "i" do Público-Alvo)
 └── Dispara o Yield para a Pipeline do Laravel
                                                      │
                                                      ▼
                                       [SalvarNoBancoProcessor]
                                        (updateOrCreate via MD5 Composto)

```

### O Tratamento de Dados (Sanitização)

Durante a captura do campo **Público-Alvo**, a estrutura do HTML da FINEP colava o texto descritivo com o texto de auxílio do ícone de *tooltip* informativa ("i"). O spider foi equipado com um tratamento via Expressão Regular (`preg_replace`) para isolar o público real e sanitizar a string antes de persistir no banco de dados.

---

## 🚀 Como Executar o Fluxo de Extração

Para rodar o coletor em modo de limpeza de cache/estrutura e carga completa, execute a sequência abaixo no terminal da sua aplicação:

1. **Resetar e estruturar as tabelas do Banco de Dados:**
```bash
php artisan migrate:fresh

```


2. **Disparar a engine de raspagem automatizada:**
```bash
php artisan roach:run FinepSpider

```



> ⚙️ **Nota de Performance:** Devido à eficiência da arquitetura de buffer do cofre em JavaScript e à redução do delay adaptativo para 1.2s, a extração total das dezenas de páginas do portal da FINEP passou de um tempo estimado de **30 minutos** para uma execução sólida de aproximadamente **1 a 2 minutos**, de forma totalmente idempotente.




<!-- rapar depois documentações exigidas, verificar se ICT é exigido, valor min e max, datas de abertura de inscrições e fechamento do edital, quem pode participar, até que dia pode submeter proposta -->