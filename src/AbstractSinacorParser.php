<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser;

use Samhk222\SinacorParser\DTO\BrokerageNote;
use Samhk222\SinacorParser\DTO\ImportSummary;
use Samhk222\SinacorParser\DTO\Movement;
use Samhk222\SinacorParser\DTO\Trade;
use Samhk222\SinacorParser\Exceptions\PdfReadException;
use Samhk222\SinacorParser\Resolvers\AssetResolverInterface;
use Samhk222\SinacorParser\Resolvers\NullAssetResolver;
use Smalot\PdfParser\Parser as PdfParser;

abstract class AbstractSinacorParser implements BrokerageNoteParserInterface
{
    protected AssetResolverInterface $resolver;

    public function __construct(?AssetResolverInterface $resolver = null)
    {
        $this->resolver = $resolver ?? new NullAssetResolver();
    }

    // -------------------------------------------------------------------------
    // Implementações públicas
    // -------------------------------------------------------------------------

    public function notes(string $pdfPath): array
    {
        $text = $this->extractText($pdfPath);
        $blocks = $this->splitNoteBlocks($text);

        return array_map(fn(string $block) => $this->parseNote($block), $blocks);
    }

    public function movements(string $pdfPath): array
    {
        $movements = [];

        foreach ($this->notes($pdfPath) as $note) {
            foreach ($note->trades as $trade) {
                $movements[] = Movement::fromTrade($note, $trade, $this->resolver);
            }
        }

        return $movements;
    }

    public function assets(string $pdfPath): array
    {
        $tickers = [];

        foreach ($this->movements($pdfPath) as $movement) {
            $tickers[] = $movement->ticker ?? $movement->brokerAsset;
        }

        return array_values(array_unique($tickers));
    }

    public function unmappedAssets(string $pdfPath): array
    {
        return array_values(
            array_filter(
                array_map(
                    fn(Movement $m) => $m->tickerMapped ? null : $m->brokerAsset,
                    $this->movements($pdfPath)
                )
            )
        );
    }

    public function summary(string $pdfPath): ImportSummary
    {
        $movements = $this->movements($pdfPath);
        $notes = $this->notes($pdfPath);

        $unmapped = array_filter($movements, fn(Movement $m) => !$m->tickerMapped);

        return new ImportSummary(
            totalNotes: count($notes),
            totalMovements: count($movements),
            totalUnmapped: count($unmapped),
            unmappedAssets: array_values(array_unique(
                array_map(fn(Movement $m) => $m->brokerAsset, $unmapped)
            )),
        );
    }

    // -------------------------------------------------------------------------
    // Métodos internos
    // -------------------------------------------------------------------------

    protected function extractText(string $pdfPath): string
    {
        if (!file_exists($pdfPath)) {
            throw new PdfReadException("Arquivo não encontrado: {$pdfPath}");
        }

        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($pdfPath);

            return $pdf->getText();
        } catch (\Throwable $e) {
            throw new PdfReadException(
                "Falha ao ler o PDF: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Divide o texto completo em blocos por nota de negociação.
     *
     * @return string[]
     */
    protected function splitNoteBlocks(string $text): array
    {
        // Separador padrão SINACOR
        $blocks = preg_split(
            '/(?=NOTA\s+DE\s+NEGOCIAÇÃO)/u',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        return array_values(array_filter($blocks !== false ? $blocks : [], fn(string $b) => strlen(trim($b)) > 50));
    }

    // -------------------------------------------------------------------------
    // Métodos abstratos (cada corretora implementa)
    // -------------------------------------------------------------------------

    abstract protected function parseNote(string $content): BrokerageNote;

    /** @return array<string, mixed> */
    abstract protected function extractHeader(string $content): array;

    /** @return Trade[] */
    abstract protected function extractTrades(string $content): array;

    /** @return array<string, float> */
    abstract protected function extractSummary(string $content): array;

    // -------------------------------------------------------------------------
    // Utilitários compartilhados
    // -------------------------------------------------------------------------

    /**
     * Converte string monetária brasileira para float.
     * Ex.: "1.935,90" → 1935.90
     */
    protected function parseMoney(string $value): float
    {
        $clean = str_replace(['.', ','], ['', '.'], trim($value));

        return (float)$clean;
    }
}
