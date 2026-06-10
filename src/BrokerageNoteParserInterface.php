<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser;

use Samhk222\SinacorParser\DTO\BrokerageNote;
use Samhk222\SinacorParser\DTO\Movement;
use Samhk222\SinacorParser\DTO\ImportSummary;

interface BrokerageNoteParserInterface
{
    /**
     * Retorna todas as notas de corretagem do PDF.
     *
     * @return BrokerageNote[]
     */
    public function notes(string $pdfPath): array;

    /**
     * Retorna todas as movimentações (trades) do PDF.
     *
     * @return Movement[]
     */
    public function movements(string $pdfPath): array;

    /**
     * Retorna lista de tickers únicos encontrados no PDF.
     *
     * @return string[]
     */
    public function assets(string $pdfPath): array;

    /**
     * Retorna tickers que não foram mapeados pelo resolver.
     *
     * @return string[]
     */
    public function unmappedAssets(string $pdfPath): array;

    /**
     * Retorna um resumo da importação.
     */
    public function summary(string $pdfPath): ImportSummary;
}
