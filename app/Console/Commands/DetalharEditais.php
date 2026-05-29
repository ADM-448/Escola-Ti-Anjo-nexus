<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Edital;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DetalharEditais extends Command
{
    protected $signature = 'editais:detalhar';
    protected $description = 'Varre as páginas institucionais e extrai metadados, valores e prazos via HTML';

    public function handle()
    {
        $editaisPendentes = Edital::whereNull('objetivo')->orWhere('objetivo', '')->get();
        $total = $editaisPendentes->count();

        if ($total === 0) {
            $this->info('Todos os editais já estão detalhados!');
            return 0;
        }

        $this->info("Encontrados {$total} editais pendentes no ecossistema. Iniciando extração de metadados...");

        foreach ($editaisPendentes as $edital) {
            $this->line("🔍 Extraindo dados de [{$edital->fonte}]: {$edital->titulo}");

            if ($edital->fonte === 'FAPPR') {
                $edital->update([
                    'objetivo' => 'Esta é uma chamada pública direta da Fundação Araucária. As diretrizes completas de subvenção, linhas de pesquisa e cronograma detalhado de recursos encontram-se integralmente disponíveis no arquivo PDF anexo.',
                    'valor_total' => 'Verificar no PDF',
                    'valor_minimo_proposta' => 'Não estipulado',
                    'valor_maximo_proposta' => 'Subvenção Variável',
                    'publico' => 'Startup / Empresa / ICT',
                    'exige_ict' => true,
                    'documentacoes_exigidas' => "• Proposta técnica estruturada\n• Certidões de regularidade fiscal\n• Documentação de elegibilidade da Startup",
                    'elegibilidade_detalhada' => "• Startups e Empresas de base tecnológica do Paraná\n• Pesquisadores vinculados a ICTs (Institutos de Ciência e Tecnologia)\n• Consórcios empresariais em parceria com o ecossistema local"
                ]);
                $this->info("✅ [FAPPR] Dados padrão aplicados com sucesso!");
                continue;
            }

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                ])->get($edital->link);

                if (!$response->successful()) {
                    $this->error("Erro ao acessar link: {$edital->link}");
                    continue;
                }

                $crawler = new Crawler($response->body());

                // FIX: Inicialização de TODAS as variáveis de transporte no topo do escopo
                $textoObjetivo = '';
                $valorTotal = null;
                $valorMinimo = null;
                $valorMaximo = null;
                $prazoSubmissao = null;
                $dataAbertura = null;
                $elegibilidade = '';
                $documentos = '';
                $exigeIct = false;

                // ==========================================
                // LÓGICA 1: RASPAGEM DA FINEP
                // ==========================================
                if ($edital->fonte === 'FINEP') {
                    if ($crawler->filter('#componente-central, .texto-edital, article, .item-page')->count()) {
                        $textoObjetivo = trim($crawler->filter('#componente-central, .texto-edital, article, .item-page')->first()->text());
                    }

                    // Varre elementos de texto buscando os padrões refinados
                    $crawler->filter('#componente-central p, #componente-central li, tr, p, li')->each(function (Crawler $node) use (&$valorTotal, &$valorMinimo, &$valorMaximo, &$prazoSubmissao, &$dataAbertura, &$elegibilidade, &$exigeIct, &$documentos) {
                        $texto = trim($node->text());
                        $textoMinusculo = strtolower($texto);

                        // 1. Verificação de ICT
                        if (str_contains($textoMinusculo, 'exige-se ict') || str_contains($textoMinusculo, 'parceria obrigatoria com ict') || str_contains($textoMinusculo, 'associada a uma ict')) {
                            $exigeIct = true;
                        }

                        // 2. Extração de Valor Mínimo
                        if (preg_match('/(?:valor minimo|limite minimo|subvenção minima|piso)\s*(?:de|será de)?\s*(?:R\$\s*)([0-9.,]+)/i', $texto, $matches)) {
                            $valorMinimo = 'R$ ' . trim($matches[1], '.');
                        }

                        // 3. Extração de Valor Máximo / Teto
                        if (preg_match('/(?:teto|limite maximo|valor maximo|por proposta de ate)\s*(?:de|será de)?\s*(?:R\$\s*)([0-9.,]+)/i', $texto, $matches)) {
                            $valorMaximo = 'R$ ' . trim($matches[1], '.');
                        }

                        // 4. Extração de Valor Global
                        if (preg_match('/(?:valor global|recurso|total disponível|orçamento)\s*(?:estipulado|de|é de)?\s*(?:R\$\s*)([0-9.,]+)/i', $texto, $matches)) {
                            if (strlen($matches[1]) > 5) {
                                $valorTotal = 'R$ ' . trim($matches[1], '.');
                            }
                        }

                        // 5. Data de Abertura das Inscrições
                        if (preg_match('/(?:abertura das inscriç|inicio das submiss|lançamento do edital)\s*(?:em|a partir de)?\s*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i', $texto, $matches)) {
                            if (!$dataAbertura) {
                                try {
                                    $dataAbertura = Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');
                                } catch (\Exception $e) {
                                }
                            }
                        }

                        // 6. Prazo Final de Submissão (Fechamento)
                        if (preg_match('/(?:Encerramento|Limite para submissão|Prazo final|Inscrições até)\s*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i', $texto, $matches)) {
                            if (!$prazoSubmissao) {
                                try {
                                    $prazoSubmissao = Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');
                                } catch (\Exception $e) {
                                }
                            }
                        }

                        // 7. Documentações Exigidas
                        if (str_contains($textoMinusculo, 'documentos exigidos') || str_contains($textoMinusculo, 'documentação de habilitação') || str_contains($textoMinusculo, 'relação de documentos')) {
                            if (strlen($texto) > 15 && strlen($texto) < 400 && !str_contains($documentos, Str::limit($texto, 20))) {
                                $documentos .= "• " . $texto . "\n";
                            }
                        }
                    });
                }

                // ==========================================
                // LÓGICA 2: RASPAGEM DA FAPESC
                // ==========================================
                if ($edital->fonte === 'FAPESC') {
                    $seletorConteudo = $crawler->filter('.elementor-widget-theme-post-content, .elementor-text-editor, article')->count()
                        ? '.elementor-widget-theme-post-content, .elementor-text-editor, article'
                        : 'body';

                    if ($crawler->filter($seletorConteudo)->count()) {
                        $textoObjetivo = trim($crawler->filter($seletorConteudo)->first()->text());
                    }

                    $crawler->filter('.elementor-text-editor p, .elementor-text-editor li, p, li, tr')->each(function (Crawler $node) use (&$valorTotal, &$valorMinimo, &$valorMaximo, &$prazoSubmissao, &$dataAbertura, &$elegibilidade, &$exigeIct, &$documentos) {
                        $texto = trim($node->text());
                        $textoMinusculo = strtolower($texto);

                        // 1. Verificação de ICT
                        if (str_contains($textoMinusculo, 'exige-se ict') || str_contains($textoMinusculo, 'parceria obrigatoria com ict') || str_contains($textoMinusculo, 'associada a uma ict')) {
                            $exigeIct = true;
                        }

                        // 2. Quem pode participar (Elegibilidade)
                        if (
                            preg_match('/(?:público-alvo|proponentes|elegibilidade|quem pode participar|destinado a)\s*:/i', $texto) ||
                            str_contains($textoMinusculo, 'empresas de micro') ||
                            str_contains($textoMinusculo, 'startups catarinenses')
                        ) {
                            if (strlen($texto) > 10 && strlen($texto) < 300 && !str_contains($elegibilidade, Str::limit($texto, 20))) {
                                $elegibilidade .= "• " . $texto . "\n";
                            }
                        }

                        // 3. Recursos e Valores Financeiros
                        if (preg_match('/(?:global|total|investimento de|aporte de)\s*(?:de|será de)?\s*(?:R\$\s*)([0-9.,]+)/i', $texto, $matches)) {
                            if (!$valorTotal && strlen($matches[1]) > 5) {
                                $valorTotal = 'R$ ' . trim($matches[1], '.');
                            }
                        }
                        if (preg_match('/(?:valor minimo|limite minimo|piso)\s*(?:de|será de)?\s*(?:R\$\s*)([0-9.,]+)/i', $texto, $matches)) {
                            $valorMinimo = 'R$ ' . trim($matches[1], '.');
                        }
                        if (preg_match('/(?:teto|valor maximo|limite maximo)\s*(?:de|será de)?\s*(?:R\$\s*)([0-9.,]+)/i', $texto, $matches)) {
                            $valorMaximo = 'R$ ' . trim($matches[1], '.');
                        }

                        // 4. Prazos (Abertura e Fechamento)
                        if (preg_match('/(?:abertura das inscriç|inicio das submiss|lançamento do edital)\s*(?:em|a partir de)?\s*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i', $texto, $matches)) {
                            if (!$dataAbertura) {
                                try {
                                    $dataAbertura = Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');
                                } catch (\Exception $e) {
                                }
                            }
                        }
                        if (preg_match('/(?:submissão|inscriç|prazo até)\s*(?:até|o dia)?\s*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i', $texto, $matches)) {
                            if (!$prazoSubmissao) {
                                try {
                                    $prazoSubmissao = Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');
                                } catch (\Exception $e) {
                                }
                            }
                        }

                        // 5. Documentações Exigidas
                        if (str_contains($textoMinusculo, 'documentos exigidos') || str_contains($textoMinusculo, 'documentação de habilitação') || str_contains($textoMinusculo, 'relação de documentos')) {
                            if (strlen($texto) > 15 && strlen($texto) < 400 && !str_contains($documentos, Str::limit($texto, 20))) {
                                $documentos .= "• " . $texto . "\n";
                            }
                        }
                    });
                }

                $textoObjetivoLimpo = trim(preg_replace('/\s+/', ' ', $textoObjetivo));

                // Salva tudo de forma explícita e consistente mapeando os novos campos do banco
                $edital->update([
                    'objetivo' => !empty($textoObjetivoLimpo) ? Str::limit($textoObjetivoLimpo, 2000) : 'Disponível na página oficial.',
                    'valor_total' => $valorTotal ?? 'Consultar Edital',
                    'valor_minimo_proposta' => $valorMinimo ?? 'Não estipulado',
                    'valor_maximo_proposta' => $valorMaximo ?? 'Consultar tabelas anexas',
                    'prazo_submissao' => $prazoSubmissao,
                    'data_abertura_inscricoes' => $dataAbertura,
                    'exige_ict' => $exigeIct,
                    'documentacoes_exigidas' => !empty($documentos) ? trim($documentos) : 'A relação completa de certidões e anexos técnicos deve ser consultada no arquivo oficial.',
                    'elegibilidade_detalhada' => !empty($elegibilidade) ? trim($elegibilidade) : 'Critérios de participação disponíveis no termo de referência.'
                ]);

                $this->info("✅ [{$edital->fonte}] Detalhado e enriquecido localmente!");

                sleep(rand(1, 2));

            } catch (\Exception $e) {
                $this->error("Erro ao processar edital ID {$edital->id}: " . $e->getMessage());
            }
        }

        $this->info('Varredura de detalhamento concluída com sucesso!');
        return 0;
    }
}