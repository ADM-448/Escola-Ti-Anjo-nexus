<?php

namespace App\Spiders;

use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use Symfony\Component\DomCrawler\Crawler; // Permite navegar no HTML com seletores CSS (como jQuery no PHP)
use Illuminate\Support\Str;               // Helper do Laravel — usamos o Str::ascii() para remover acentos

class FapprSpider extends BasicSpider
{
    // URL da HOME da Fundação Araucária (FAPPR).
    // Começamos na home porque o link da seção "Atos e Notas" muda a cada biênio
    // (ex: "Atos e Notas 2025-2026", "Atos e Notas 2027-2028") — precisamos descobrir dinamicamente.
    public array $startUrls = [
        'https://www.fappr.pr.gov.br/'
    ];

    // Processa uma URL por vez — a FAPPR é um portal governamental que pode bloquear por rate limit
    public int $concurrency = 1;

    // Após cada yield $this->item([...]), o Roach encaminha os dados para este Processor,
    // que persiste o edital no banco via updateOrCreate (evita duplicatas)
    public array $itemProcessors = [
        \App\Spiders\Processors\SalvarNoBancoProcessor::class,
    ];

    /**
     * ETAPA 1 — Descoberta Dinâmica da Atoteca
     *
     * O Roach chama este método com a resposta da Home.
     * Em vez de processar editais aqui, só descobrimos a URL correta da seção
     * de atos e encaminhamos o Roach para abri-la via yield $this->request().
     */
    public function parse(Response $response): \Generator
    {
        $urlAtotecaMaisRecente = null;

        // Varre TODOS os links (<a>) da Home em busca do menu "Atos e Notas XXXX-XXXX"
        foreach ($response->filter('a') as $node) {
            $anchor = new Crawler($node); // Envolve o nó DOM em um Crawler para usar ->text() e ->attr()
            $textoMenu = $anchor->text();

            // str_starts_with é mais legível e eficiente que strpos() para verificar prefixo
            if (str_starts_with(trim($textoMenu), 'Atos e Notas')) {
                $urlAtotecaMaisRecente = $anchor->attr('href'); // Pega o valor do atributo href do link
                break; // Primeiro link encontrado = mais recente (HTML renderizado de cima para baixo)
            }
        }

        // Segurança: se o portal mudar o nome do menu, usa uma URL fixa conhecida como fallback
        if (!$urlAtotecaMaisRecente) {
            dump("AVISO: Não foi possível mapear o menu dinâmico. Usando fallback seguro.");
            $urlAtotecaMaisRecente = 'https://www.fappr.pr.gov.br/Pagina/Atos-e-Notas-2025-2026';
        } else if (!str_starts_with($urlAtotecaMaisRecente, 'http')) {
            // Se o href for relativo (ex: "/Pagina/Atos-e-Notas-2025-2026"),
            // concatena com o domínio base para formar a URL absoluta
            $urlAtotecaMaisRecente = 'https://www.fappr.pr.gov.br' . $urlAtotecaMaisRecente;
        }

        dump("➔ FAPPR: Atoteca mais recente detectada dinamicamente: " . $urlAtotecaMaisRecente);

        // yield $this->request() é o mecanismo do Roach para encadear páginas:
        // em vez de retornar dados, ele agenda uma nova requisição HTTP e
        // direciona a resposta para o método 'parseAtoteca' abaixo.
        yield $this->request('GET', $urlAtotecaMaisRecente, 'parseAtoteca');
    }

    /**
     * ETAPA 2 — Raspagem dos Editais na Página da Atoteca
     *
     * Chamado automaticamente pelo Roach quando a URL da Atoteca é carregada.
     * Aqui filtramos os links e extraímos apenas os editais originais (CPs).
     */
    public function parseAtoteca(Response $response): \Generator
    {
        // Varre links dentro de estruturas comuns de conteúdo do portal (article, div de conteúdo, li)
        $links = $response->filter('article a, .conteudo-pagina a, li a');
        dump("FAPPR: Analisando " . $links->count() . " linhas da Atoteca vigente.");

        // Contador de quantos editais SEGUIDOS já existem no banco.
        // Estratégia de parada inteligente: se 5 seguidos já existem, os mais antigos também existem
        // — não há necessidade de continuar varrendo a lista inteira.
        $jaExistentesSeguidos = 0;
        $limiteParaParar = 5; // Constante de parada — ajustar se a coleta perder dados

        foreach ($links as $node) {
            $anchor = new Crawler($node);
            $textoLinha = $anchor->text();
            $linkPdf    = $anchor->attr('href');

            // FILTRO 1 — Whitelist por "CP" (Chamada Pública)
            // Só nos interessam linhas que contenham "CP " (com espaço, para não pegar "CPU", etc.)
            // strtoupper garante que "cp 01/2025" e "CP 01/2025" são tratados igual
            if (!str_contains(strtoupper($textoLinha), 'CP ')) {
                continue; // Linha não é uma chamada pública — ignora e vai para o próximo link
            }

            // FILTRO 2 — Blacklist de termos de andamento (filtro reverso)
            // Mesmo que o link passe pelo filtro "CP", pode ser um documento de RESULTADO,
            // ERRATA, ADITIVO, etc. — queremos APENAS o edital original.
            //
            // Str::ascii() remove acentos ANTES do strtoupper() para que "ALTERAÇÃO"
            // vire "ALTERACAO" e bata corretamente com a blacklist (que não tem acentos).
            $textoLinhaSemAcento = strtoupper(\Illuminate\Support\Str::ascii($textoLinha));

            $termosDeAndamento = [
                'RESULTADO',    // Publicação de resultado (não é o edital original)
                'ERRATA',       // Correção do edital publicada separadamente
                'RETIFICACO',   // Retificação (sem acento para bater com o Str::ascii)
                'ALTERACO',     // Alteração de regras
                'CRONOGRAMA',   // Atualização de datas
                'DIVULGACO',    // Divulgação de lista
                'ELEGIVEIS',    // Lista de projetos elegíveis
                'HOMOLOGACO',   // Homologação de projetos aprovados
                'CONTRATACO',   // Contratos firmados
                'INCLUSAO',     // Inclusão de proponente
                'ADITIVO',      // Termo aditivo ao contrato
                'TERMO',        // Termos em geral (rescisão, etc.)
                'PRORROGACAO',  // Extensão de prazo
                'SUBSTITUICAO'  // Substituição de membro de equipe
            ];

            foreach ($termosDeAndamento as $ruido) {
                if (str_contains($textoLinhaSemAcento, $ruido)) {
                    // continue 2 = sai de DOIS níveis de loop aninhado:
                    // sai do foreach($termosDeAndamento) E do foreach($links) de uma vez só,
                    // pulando diretamente para o próximo link da lista.
                    continue 2;
                }
            }

            // Remove espaços duplos e quebras de linha do texto bruto do link
            $tituloLimpo = trim(preg_replace('/\s+/', ' ', $textoLinha));

            // Se o href for relativo (começa com "/"), converte para URL absoluta
            if ($linkPdf && !str_starts_with($linkPdf, 'http')) {
                $linkPdf = 'https://www.fappr.pr.gov.br' . $linkPdf;
            }

            // Gera um ID único determinístico baseado no título + link.
            // md5() garante sempre a mesma hash para o mesmo par título/link,
            // permitindo detectar duplicatas sem precisar de ID externo.
            $externalId = md5($tituloLimpo . $linkPdf);

            // Verifica se este edital já existe no banco ANTES de fazer o yield
            if (\App\Models\Edital::where('external_id', $externalId)->exists()) {
                $jaExistentesSeguidos++;

                // Se atingiu o limite de existentes seguidos, assume que o restante
                // da lista já foi coletado em execuções anteriores e para o loop
                if ($jaExistentesSeguidos >= $limiteParaParar) {
                    dump("-> [FAPPR] Parada otimizada acionada. Coletas seguintes já atualizadas.");
                    break; // Sai completamente do foreach
                }
                continue; // Apenas pula este item, continua verificando os próximos
            }

            // Novo edital encontrado — zera o contador de seguidos para reiniciar a contagem
            $jaExistentesSeguidos = 0;

            // Emite o item para o Pipeline (SalvarNoBancoProcessor).
            // objetivo e elegibilidade ficam vazios aqui — serão preenchidos depois
            // pelo comando 'editais:detalhar' (DetalharEditais.php)
            yield $this->item([
                'external_id'            => $externalId,
                'titulo'                 => $tituloLimpo,
                'data_publicacao'        => date('Y-m-d'), // Data de coleta (a FAPPR não exibe data no link)
                'link'                   => $linkPdf,      // Link direto para o PDF do edital
                'fonte'                  => 'FAPPR',
                'objetivo'               => '',            // Preenchido depois pelo DetalharEditais
                'condicao_financiamento' => $linkPdf,      // Para a FAPPR, o próprio PDF é a condição
                'operacao'               => 'Subvenção Econômica / Fomento',
                'publico'                => 'Startup / Empresa / ICT',
            ]);
        }
    }
}