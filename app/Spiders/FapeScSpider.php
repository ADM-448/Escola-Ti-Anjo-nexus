<?php

namespace App\Spiders;

use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use Symfony\Component\DomCrawler\Crawler; // Permite navegar no HTML com seletores CSS (como jQuery no PHP)

class FapeScSpider extends BasicSpider
{
    // Trait de higienização de texto (descomente se precisar limpar os dados antes de salvar)
    // use \App\Traits\LimpaTextoTrait;

    // Página de chamadas abertas da FAPESC — é aqui que ficam listados os editais ativos
    public array $startUrls = [
        'https://fapesc.sc.gov.br/chamadas-abertas/'
    ];

    // Concorrência 1 = uma requisição por vez.
    // Protege a memória do servidor e evita sobrecarga no portal da FAPESC.
    public int $concurrency = 1;

    // Pipeline de saída: após cada yield $this->item([...]), o Roach
    // entrega os dados para este Processor, que os persiste no MySQL.
    public array $itemProcessors = [
        \App\Spiders\Processors\SalvarNoBancoProcessor::class,
    ];

    /**
     * Método principal — chamado automaticamente pelo Roach com a resposta da $startUrl.
     * É um Generator: usa yield para emitir múltiplos itens um por vez (sem carregar tudo na RAM).
     */
    public function parse(Response $response): \Generator
    {
        // A FAPESC usa o plugin "Ultimate Post Kit" do Elementor (WordPress).
        // Os editais são renderizados como cards com as classes CSS .upk-list-wrap e .upk-item.
        // filter() retorna uma coleção com TODOS os nós que casam com o seletor.
        $cards = $response->filter('.upk-list-wrap .upk-item');

        dump("FAPESC: Mapeados " . $cards->count() . " editais na página inicial.");

        // Itera sobre cada card de edital encontrado na página
        foreach ($cards as $node) {
            // $node é um objeto DOMElement puro (do PHP). Para usar seletores CSS nele,
            // precisamos envolve-lo em um novo Crawler — assim temos o ->filter() disponível
            // APENAS dentro deste card, sem vazar para o resto do DOM da página.
            $card = new Crawler($node);

            // Validação defensiva: verifica se o card tem o link do título antes de acessá-lo.
            // Se a estrutura HTML do site mudar e o seletor não existir, pula sem travar.
            if (!$card->filter('.upk-title a')->count()) {
                continue;
            }

            // Extrai o texto visível do link e o atributo href
            $titulo = $card->filter('.upk-title a')->text();
            $link   = $card->filter('.upk-title a')->attr('href');

            // upk-meta é o bloco de metadados do card (data, categoria, etc.)
            // Se não existir, usa a data de hoje como fallback para não deixar o campo vazio
            $dataTexto = $card->filter('.upk-meta')->count() ? $card->filter('.upk-meta')->text() : date('Y-m-d');

            // Remove espaços duplos, tabs e quebras de linha do título bruto
            $tituloLimpo = trim(preg_replace('/\s+/', ' ', $titulo));

            // yield envia este item para o SalvarNoBancoProcessor e suspende o método aqui.
            // O Roach processa cada item individualmente antes de retomar o foreach.
            yield $this->item([
                // md5(título + link) gera um hash único e determinístico:
                // sempre o mesmo par título/link gera a mesma hash, permitindo
                // que o updateOrCreate no Processor detecte duplicatas sem ID externo.
                'external_id'            => md5($tituloLimpo . $link),
                'titulo'                 => $tituloLimpo,
                'data_publicacao'        => trim($dataTexto), // trim() remove espaços que podem vir do HTML
                'link'                   => $link,
                'fonte'                  => 'FAPESC',
                'objetivo'               => '', // Será preenchido depois pelo comando editais:detalhar
                'condicao_financiamento' => '', // Idem
                'operacao'               => '', // Idem
                'publico'                => '', // Idem
            ]);
        }
    }
}