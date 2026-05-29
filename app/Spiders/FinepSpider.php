<?php

namespace App\Spiders;

use App\Traits\LimpaTextoTrait;
use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use Spatie\Browsershot\Browsershot; // Biblioteca que controla um Chrome real via Puppeteer/Node.js

class FinepSpider extends BasicSpider
{
    // Trait utilitária do projeto que aplica limpeza de texto (remove espaços, tags, etc.)
    use LimpaTextoTrait;

    // URL de entrada do Spider — é a primeira página que o Roach vai acessar
    public array $startUrls = [
        'https://www.finep.gov.br/oportunidades'
    ];

    // Concorrência = 1 significa que o Spider processa uma requisição por vez.
    // Essencial aqui pois usamos um browser real (Chrome), que consome muita memória.
    public int $concurrency = 1;

    // Pipeline de saída: após o yield $this->item([...]), o Roach encaminha os dados
    // para esta classe, que é responsável por salvar tudo no banco MySQL.
    public array $itemProcessors = [
        \App\Spiders\Processors\SalvarNoBancoProcessor::class,
    ];

    /**
     * Método principal chamado pelo Roach ao receber a resposta da $startUrl.
     * É um Generator (usa yield), o que permite "emitir" múltiplos itens
     * de forma lazy sem carregar tudo na memória de uma vez.
     */
    public function parse(Response $response): Generator
    {
        // Captura a URL atual para usá-la como contexto de autenticação no Browsershot
        $url = (string) $response->getUri();

        dump("Iniciando varredura via API interna (fetch no contexto do browser)...");

        try {
            /*
             * POR QUE BROWSERSHOT E NÃO HTTP SIMPLES?
             *
             * A FINEP usa o Liferay (CMS corporativo) que exige um token de sessão
             * nos headers de cada requisição à API. Esse token é gerado dinamicamente
             * pelo JavaScript do portal após o login/carregamento da página.
             *
             * Se fizermos um Http::get() direto do PHP, o Liferay rejeita com 403.
             * A solução é abrir o Chrome de verdade (Browsershot), deixar a sessão
             * se autenticar normalmente, e então executar o fetch() da API DE DENTRO
             * do browser — assim os cookies e headers de sessão já estão presentes.
             */

            // Script JavaScript que roda DENTRO do Chrome aberto pelo Browsershot.
            // Retorna uma Promise pois fetch() é assíncrono no JS.
            $script = "
                new Promise(async (resolve, reject) => {
                    try {
                        const PAGE_SIZE = 250;          // Máximo de itens por página da API
                        const API_BASE  = '/o/c/chamadapublicas'; // Endpoint relativo da API Liferay
                        const SORT      = 'sort=dataDePublicacao:desc'; // Ordena do mais novo para o mais antigo

                        // ── 1. Busca a 1ª página para descobrir quantas páginas existem no total ──
                        const primeiraResp = await fetch(
                            API_BASE + '?' + SORT + '&search=&page=1&pageSize=' + PAGE_SIZE,
                            { headers: { 'Accept': 'application/json' } }
                        );

                        if (!primeiraResp.ok) {
                            reject('HTTP ' + primeiraResp.status);
                            return;
                        }

                        const primeiroJson = await primeiraResp.json();
                        const lastPage     = primeiroJson.lastPage || 1; // Total de páginas (fallback = 1)
                        let   todosItens   = primeiroJson.items || [];   // Acumula todos os editais

                        console.log('[FinepSpider] Total de páginas:', lastPage);
                        console.log('[FinepSpider] Itens na página 1:', todosItens.length);

                        // ── 2. Itera as páginas restantes (da 2 até lastPage) ──
                        for (let pagina = 2; pagina <= lastPage; pagina++) {
                            const resp = await fetch(
                                API_BASE + '?' + SORT + '&search=&page=' + pagina + '&pageSize=' + PAGE_SIZE,
                                { headers: { 'Accept': 'application/json' } }
                            );

                            if (!resp.ok) {
                                console.warn('[FinepSpider] Falha na página ' + pagina + ': HTTP ' + resp.status);
                                continue; // Pula esta página com erro e tenta a próxima
                            }

                            const dados = await resp.json();
                            const itensDaPagina = dados.items || [];
                            // concat() retorna um novo array — não muta o original
                            todosItens = todosItens.concat(itensDaPagina);
                            console.log('[FinepSpider] Página ' + pagina + '/' + lastPage + ' → ' + itensDaPagina.length + ' itens');
                        }

                        // Serializa o array final para JSON e devolve para o PHP via resolve()
                        resolve(JSON.stringify(todosItens));

                    } catch (err) {
                        reject(err.toString());
                    }
                });
            ";

            // Abre o Chrome real na URL do portal e executa o script JS acima.
            // O evaluate() aguarda a Promise resolver e retorna o resultado como string PHP.
            $jsonString = Browsershot::url($url)
                ->setNodeBinary('C:/nodejs/node.exe')          // Caminho do Node.js instalado no Windows
                ->setNpmBinary('C:/nodejs/npm.cmd')            // Caminho do npm (necessário para o Puppeteer)
                ->setChromePath('C:/Program Files/Google/Chrome/Application/chrome.exe')
                ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']) // Flags para rodar Chrome sem interface gráfica
                ->setOption('protocolTimeout', 600000) // Timeout do protocolo DevTools em ms (10 minutos) — API pode ser lenta
                ->timeout(600)                         // Timeout total da operação em segundos (10 minutos)
                ->waitUntilNetworkIdle()               // Aguarda a rede ficar ociosa antes de executar o script (garante sessão carregada)
                ->evaluate($script);                   // Injeta e executa o JS; retorna o valor do resolve()

            // Decodifica o JSON retornado pelo JS em array PHP associativo
            $itens = json_decode($jsonString, true);

            // Linhas de debug (descomentar quando precisar inspecionar a estrutura da API):
            // dump($itens[0]); // Mostra o primeiro edital completo retornado pela API
            // die();           // Para a execução aqui para ler o terminal com calma

            // Validação: se o JS falhou ou a API retornou vazio, aborta com aviso
            if (!is_array($itens) || empty($itens)) {
                dump("AVISO: A API retornou vazio ou JSON inválido. Resposta bruta:");
                dump($jsonString);
                return;
            }

            dump("Concluído! API retornou " . count($itens) . " editais no total.");

            foreach ($itens as $item) {
                // ── Mapeamento dos campos confirmado inspecionando a resposta real da API Liferay ──

                // UUID nativo do Liferay — identificador único globalmente, sem risco de colisão entre editais
                $externalId = $item['externalReferenceCode'] ?? '';

                // Campo correto do JSON é 'titulo' (não 'nome', não 'title' — já foi validado na API)
                $titulo = $this->limpaTexto($item['titulo'] ?? 'Sem Título');

                // Usamos o título bruto (sem limpar) como base para gerar o slug,
                // pois a limpeza pode alterar caracteres que o slug precisa processar
                $tituloParaSlug = $item['titulo'] ?? 'Sem Título';

                // Str::slug() converte "Finep Mais Inovação 2025" em "finep-mais-inovacao-2025"
                // (remove acentos, substitui espaços por hífen, tudo minúsculo)
                $slug = \Illuminate\Support\Str::slug($tituloParaSlug, '-');

                // A FINEP usa uma SPA (Single Page Application): a URL não muda ao navegar,
                // a âncora (#slug) é o que carrega o edital correto via JavaScript no front-end
                $linkCompleto = 'https://www.finep.gov.br/financiamento-via-credito#' . $slug;

                // descricaoRawText = texto do objetivo SEM tags HTML (campo específico da API Liferay)
                $objetivo = $this->limpaTexto($item['descricaoRawText'] ?? '');

                // tipoDeOportunidade é um objeto {key, name} — pegamos o name legível
                $operacao = $this->limpaTexto($item['tipoDeOportunidade']['name'] ?? '');

                // tipoCooperacao também é um objeto — usamos a key (mais padronizada)
                $condicao = $this->limpaTexto($item['tipoCooperacao']['key'] ?? '');

                // publicoAlvo é um ARRAY de objetos [{key, name}] pois um edital pode ter múltiplos públicos.
                // array_column() extrai só os valores do campo 'name' de cada objeto do array.
                // array_filter() remove entradas vazias antes do implode.
                $publicoAlvo = $item['publicoAlvo'] ?? [];
                if (is_array($publicoAlvo) && !empty($publicoAlvo)) {
                    $nomes = array_column($publicoAlvo, 'name');
                    $publico = $this->limpaTexto(implode(', ', array_filter($nomes)));
                } else {
                    $publico = 'Não especificado';
                }

                // yield envia este item para o Pipeline (SalvarNoBancoProcessor) e
                // SUSPENDE a execução do método aqui, retomando no próximo item do foreach.
                // É assim que Generator funciona: processa um item por vez, sem acumular na memória.
                yield $this->item([
                    'external_id'     => $externalId,
                    'titulo'          => $titulo,
                    // strtotime() converte o formato ISO da API (ex: "2025-06-15T00:00:00Z") para timestamp Unix,
                    // e date('Y-m-d') formata no padrão aceito pelo MySQL
                    'data_publicacao' => isset($item['dataDePublicacao'])
                        ? date('Y-m-d', strtotime($item['dataDePublicacao']))
                        : date('Y-m-d'), // Fallback: data de hoje se o campo não existir
                    'link'            => $linkCompleto,
                    'fonte'           => 'FINEP',
                    'objetivo'        => $objetivo,
                    'condicao_financiamento' => $condicao,
                    'operacao'        => $operacao,
                    'publico'         => $publico,
                ]);
            }

        } catch (\Throwable $e) {
            // \Throwable captura tanto Exception quanto Error (erros fatais do PHP),
            // o que é mais seguro que \Exception sozinho em contextos críticos
            dump("Erro na execução do FinepSpider: " . $e->getMessage());
            dump($e->getTraceAsString()); // Stack trace completo para debug no terminal
        }
    }
}