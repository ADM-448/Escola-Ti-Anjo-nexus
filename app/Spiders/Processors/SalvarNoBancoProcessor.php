<?php

namespace App\Spiders\Processors;

use App\Models\Edital;
use RoachPHP\ItemPipeline\ItemInterface;            // Interface que representa o item emitido pelo yield do Spider
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface; // Contrato que todo Processor do Roach deve implementar

/**
 * SalvarNoBancoProcessor
 *
 * É o destino final dos dados coletados pelos Spiders.
 * Quando um Spider faz yield $this->item([...]), o Roach encaminha
 * automaticamente esse item para todos os Processors declarados em $itemProcessors.
 *
 * O Roach suporta múltiplos Processors em cadeia (pipeline) —
 * poderíamos ter um para validar, outro para salvar, outro para notificar,
 * mas aqui centralizamos tudo em um único por simplicidade.
 */
class SalvarNoBancoProcessor implements ItemProcessorInterface
{
    /**
     * Método obrigatório da interface ItemProcessorInterface.
     * Permite configurar o Processor via opções externas.
     * Não usamos nenhuma opção customizada aqui, então deixamos o corpo vazio.
     */
    public function configure(array $options): void
    {
        // Nada a configurar — mantido vazio para satisfazer o contrato da interface
    }

    /**
     * Método principal: chamado pelo Roach para cada item emitido pelos Spiders.
     * Deve SEMPRE retornar o $item (mesmo sem modificar) para não quebrar a cadeia do pipeline.
     */
    public function processItem(ItemInterface $item): ItemInterface
    {
        // $item->all() converte o objeto ItemInterface em um array PHP simples,
        // tornando fácil acessar os campos com $dados['titulo'], $dados['link'], etc.
        $dados = $item->all();

        // updateOrCreate — a peça-chave da idempotência:
        // - 1º argumento: condição de BUSCA (se já existe um registro com este external_id...)
        // - 2º argumento: dados a CRIAR (se não existe) ou ATUALIZAR (se já existe)
        //
        // Isso garante que rodar o Spider duas vezes não duplica os editais no banco —
        // apenas atualiza os campos se algo mudou (titulo, link, etc.)
        Edital::updateOrCreate(
            ['external_id' => $dados['external_id']], // Chave única: hash md5(título+link) gerado pelo Spider
            [
                'titulo'                 => $dados['titulo'],
                'link'                   => $dados['link'],
                'objetivo'               => $dados['objetivo'] ?? null,               // ?? null = usa null se a chave não existir no array
                'data_publicacao'        => $dados['data_publicacao'] ?? null,
                'condicao_financiamento' => $dados['condicao_financiamento'] ?? null,
                'operacao'               => $dados['operacao'] ?? null,
                'publico'                => $dados['publico'] ?? null,
                'fonte'                  => $dados['fonte'],                          // FINEP, FAPESC ou FAPPR
            ]
        );

        // Retorna o item intacto para que outros Processors na cadeia (se houver)
        // possam continuar processando. Obrigatório pela interface do Roach.
        return $item;
    }
}