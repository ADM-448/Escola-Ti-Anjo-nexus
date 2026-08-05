# 🎯 Novos Requisitos: Radar de Editais para Consultorias de Startups

Este documento complementa a especificação inicial, trazendo novos **Requisitos Funcionais (RF)** e **Requisitos Não-Funcionais (RNF)** pensados especificamente para transformar a plataforma em uma solução **B2B robusta para Consultorias de Captação e Inovação** que gerenciam múltiplas startups clientes.

---

## 🚀 Requisitos Funcionais (RF)

### Módulo 8: Gestão da Carteira de Clientes (Multi-Startup)
* **RF-29 (Carteira de Startups Clientes):** O sistema deve permitir que a consultoria cadastre, edite e gerencie múltiplos perfis de startups sob uma única conta de consultoria, armazenando CNPJ, Porte, Faturamento, Nível de TRL (Technology Readiness Level), CNAEs e Histórico de Captações.
* **RF-30 (Matriz de Match e Elegibilidade Automatizada):** O sistema deve cruzar automaticamente os requisitos do edital (região, limite de faturamento, TRL mínimo, necessidade de ICT parceira) com o perfil da startup cliente, gerando um **Score de Elegibilidade** (ex: 90% de Match) e destacando eventuais impedimentos legais/técnicos.
* **RF-31 (Pipeline Kanban de Oportunidades):** O sistema deve oferecer um painel Kanban configurável para acompanhar o fluxo de cada cliente por edital (Estágios: *Oportunidade Identificada -> Análise de Elegibilidade -> Coleta de Documentos -> Elaboração de Proposta -> Submetida -> Aguardando Resultado -> Recurso -> Aprovado -> Prestação de Contas*).

---

### Módulo 9: Leitura Inteligente de Editais e PDFs (Parser por IA)
* **RF-32 (Extração Automática de Regulamento via OCR/IA):** O sistema deve processar anexos e PDFs oficiais dos editais extraindo automaticamente regras de contrapartida, limite de orçamento por rubrica, cronograma completo e documentos obrigatórios.
* **RF-33 (Checklist de Conformidade Documental):** O sistema deve gerar um checklist automático de certidões e documentos exigidos (ex: CND Federal, FGTS, Balanço Patrimonial, Contrato Social, Declaração de Inexistência de Nepotismo) associado à startup e com controle de validade dos arquivos.

---

### Módulo 10: Gestão de Prazos, Recursos e Notificações Multicanal
* **RF-34 (Calendário Unificado de Marcos e Entregáveis):** O sistema deve disponibilizar um calendário interativo com datas de abertura, encerramento de submissão, divulgação de resultados preliminares, janela de recursos e prestação de contas de todos os clientes.
* **RF-35 (Gestão e Minuta de Recursos Administrativos):** O sistema deve fornecer módulo para gestão da fase de contestação/recurso administrativo, gerando via IA minutas de peça recursal baseadas nos motivos de inabilitação ou divergência de pontuação do edital.
* **RF-36 (Notificações Multicanal - WhatsApp e E-mail):** O sistema deve disparar alertas automáticos via WhatsApp e E-mail para os consultores e representantes das startups sobre pendências de documentos e proximidade do prazo final de submissão (ex: faltam 72h, 24h e 4h).

---

### Módulo 11: Gestão de Equipe da Consultoria e Permissões (RBAC)
* **RF-37 (Controle de Acesso Baseado em Papéis - RBAC):** O sistema deve suportar papéis de acesso diferenciados:
  * *Administrador/Sócio*: Acesso total às finanças, métricas e todos os clientes.
  * *Consultor/Elaborador*: Acesso restrito às startups e propostas atribuídas a ele.
  * *Cliente/Startup (External View)*: Acesso de visualização restrito apenas às suas próprias propostas e solicitações de documentos.
* **RF-38 (Atribuição de Tarefas e Responsáveis):** O sistema deve permitir atribuir consultores específicos para cada proposta/cliente, com criação de tarefas com prazo e responsável.

---

### Módulo 12: Portal White-Label do Cliente & Relatórios Executivos
* **RF-39 (Portal do Cliente / Extranet para Startups):** O sistema deve oferecer um portal personalizável (com logo e cores da consultoria) onde a startup cliente pode visualizar o progresso de suas submissões, enviar documentos pendentes e aprovar minutas de propostas.
* **RF-40 (Relatório Executivo em PDF "Radar de Oportunidades"):** O sistema deve permitir gerar relatórios personalizados em PDF com a marca da consultoria para apresentação de oportunidades mapeadas e recomendadas para a diretoria da startup cliente.
* **RF-41 (Dashboard de Taxa de Sucesso / Capture Rate):** O sistema deve apresentar relatórios gerenciais exibindo o volume total captado em R$, taxa de aprovação de projetos, volume sob análise e performance individual por consultor.

---

### Módulo 13: Engenharia de Orçamento e Biblioteca de Inovação
* **RF-42 (Elaborador de Orçamento e Matriz de Rubricas):** O sistema deve permitir a montagem do plano de aplicação financeira (Bolsas, Equipamentos, Serviços de Terceiros, Material de Consumo) validando automaticamente se os percentuais respeitam o teto estipulado no edital.
* **RF-43 (Biblioteca de Blocos de Texto e Reutilização):** O sistema deve manter um repositório de conteúdos validados (Metodologia de P&D, Descrição de Inovação, Modelo de Negócios) para reuso inteligente em propostas de editais semelhantes.

---

## ⚙️ Requisitos Não-Funcionais (RNF)

* **RNF-09 (Processamento Assíncrono de PDFs e Queues):** A extração e interpretação de editaís pesados em PDF via IA deve ser executada em background utilizando **Laravel Queues/Redis**, evitando o bloqueio da interface do usuário.
* **RNF-10 (Isolamento de Segredo Industrial e LGPD entre Clientes):** O sistema deve garantir que o contexto das propostas da *Startup A* jamais vazem ou sejam reutilizados no contexto de geração de propostas da *Startup B*, mesmo compartilhando o mesmo motor de IA.
* **RNF-11 (Trilha de Auditoria - Audit Trail):** O sistema deve registrar em logs auditáveis e imutáveis todas as alterações feitas em propostas, uploads de documentos, downloads e trocas de status, identificando usuário, IP e timestamp.
* **RNF-12 (Integrabilidade via API / Webhooks para CRMs):** O sistema deve expor webhooks e APIs para integrar com CRMs de vendas/consultoria (ex: HubSpot, Pipedrive) para criação automática de negócios quando uma nova oportunidade compatível for detectada.
* **RNF-13 (Exportação Fiel em Formato .DOCX):** As minutas de propostas geradas pela IA e exportadas em Word (`.docx`) devem manter formatação profissional pré-configurada (sumário, títulos normalizados ABNT, tabelas de orçamento e cabeçalhos).
