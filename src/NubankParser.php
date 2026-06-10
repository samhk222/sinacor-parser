<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser;

use Carbon\Carbon;
use Samhk222\SinacorParser\DTO\BrokerageNote;
use Samhk222\SinacorParser\DTO\Customer;
use Samhk222\SinacorParser\DTO\FinancialSummary;
use Samhk222\SinacorParser\DTO\Trade;
use Samhk222\SinacorParser\Enums\OperationType;

class NubankParser extends AbstractSinacorParser
{
    public const BROKER = 'NUBANK';

    // -------------------------------------------------------------------------
    // parseNote
    // -------------------------------------------------------------------------

    protected function parseNote(string $content): BrokerageNote
    {
        $header   = $this->extractHeader($content);
        $trades   = $this->extractTrades($content);
        $summary  = $this->extractSummary($content);
        $customer = $this->extractCustomer($content);

        return new BrokerageNote(
            broker:         self::BROKER,
            noteNumber:     $header['note_number'],
            tradeDate:      $header['trade_date'],
            settlementDate: $header['settlement_date'],
            customer:       $customer,
            trades:         $trades,
            summary:        $summary,
        );
    }

    // -------------------------------------------------------------------------
    // extractHeader
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    protected function extractHeader(string $content): array
    {
        // "Nr. Nota Folha Data pregão\n25090 1 10/06/2025"
        preg_match(
            '/Nr\.\s*Nota\s+Folha\s+Data\s+preg[aã]o\s+(\d+)\s+\d+\s+(\d{2}\/\d{2}\/\d{4})/ui',
            $content,
            $m
        );

        $tradeDate = isset($m[2])
            ? Carbon::createFromFormat('d/m/Y', $m[2])
            : null;

        // "Líquido para 12/06/2025\t1.936,47D"
        preg_match(
            '/[Ll][íi]quido\s+para\s+(\d{2}\/\d{2}\/\d{4})/u',
            $content,
            $liq
        );

        $settlementDate = isset($liq[1])
            ? Carbon::createFromFormat('d/m/Y', $liq[1])
            : null;

        return [
            'note_number'     => $m[1] ?? '',
            'trade_date'      => $tradeDate,
            'settlement_date' => $settlementDate,
        ];
    }

    // -------------------------------------------------------------------------
    // extractCustomer
    // -------------------------------------------------------------------------

    protected function extractCustomer(string $content): Customer
    {
        // "13176670 - 0 CLIENTE" (linha após "Cliente")
        preg_match(
            '/Cliente\s+\d[\d\s\-]+\s+([A-ZÁÉÍÓÚÂÊÎÔÛÃÕÀÜ][A-ZÁÉÍÓÚÂÊÎÔÛÃÕÀÜ\s]+)/u',
            $content,
            $m
        );

        // "000.000.000-00"
        preg_match('/C\.P\.F.*?(\d{3}[\.\s]?\d{3}[\.\s]?\d{3}[\-\s]?\d{2})/u', $content, $cpf);

        return new Customer(
            name: isset($m[1]) ? trim($m[1]) : '',
            cpf:  isset($cpf[1]) ? (preg_replace('/\D/', '', $cpf[1]) ?? '') : '',
        );
    }

    // -------------------------------------------------------------------------
    // extractTrades
    //
    // Formato real extraído pelo smalot/pdfparser (tab-separated por linha):
    //   B3 RV LISTADO CVISTA\tISHARE SP500 CI\t2 376,10\t752,20D
    //   B3 RV LISTADO CVISTA\tIT NOW IDIV CI\t7 100,10\t700,70D
    //
    // Campos por linha:
    //   - "B3 RV LISTADO [C|V]VISTA"  → operação colada ao tipo de mercado
    //   - nome do ativo
    //   - "quantidade preco"           → dois valores separados por espaço
    //   - "valorD" ou "valorC"         → valor bruto com D/C colado
    // -------------------------------------------------------------------------

    /** @return Trade[] */
    protected function extractTrades(string $content): array
    {
        $start = strpos($content, 'Negocios realizados');
        $end   = strpos($content, 'Resumo dos Negócios');

        if ($start === false || $end === false) {
            return [];
        }

        $section = substr($content, $start, $end - $start);

        $trades  = [];

        // Cada linha: "B3 RV LISTADO CVISTA\tATIVO\t[OBS\t]QTD PRECO\tVALORD"
        // O campo OBS (ex: @, #, @#, 2) é opcional — aparece entre o ativo e a quantidade
        $pattern = '/B3\s+RV\s+LISTADO\s+([CV])VISTA\t(.+?)\t(?:[^\d\t][^\t]*)?\t?(\d+)\s+([\d.,]+)\t([\d.,]+)[DC]/u';

        preg_match_all($pattern, $section, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $trades[] = new Trade(
                operation:   OperationType::fromSinacor(strtoupper($m[1])),
                market:      'VISTA',
                brokerAsset: trim($m[2]),
                quantity:    (int) $m[3],
                unitPrice:   $this->parseMoney($m[4]),
                grossAmount: $this->parseMoney($m[5]),
            );
        }

        return $trades;
    }

    // -------------------------------------------------------------------------
    // extractSummary
    //
    // Formato real (tab-separated, label\tvalorD):
    //   Valor líquido das operações\t1.935,90D
    //   Taxa de liquidação/CCP\t0,48D
    //   Emolumentos\t0,09D
    //   Taxa de Transferência de Ativos\t0,00D
    //   Líquido para 12/06/2025\t1.936,47D
    // -------------------------------------------------------------------------

    /** @return array<string, float> */
    protected function extractSummary(string $content): array
    {
        $get = function (string $pattern) use ($content): float {
            preg_match($pattern, $content, $m);
            return isset($m[1]) ? $this->parseMoney($m[1]) : 0.0;
        };

        return [
            'gross_operations' => $get('/Valor\s+l[íi]quido\s+das\s+opera[çc][õo]es\s+([\d.,]+)/ui'),
            'liquidation_fee'  => $get('/Taxa\s+de\s+liquida[çc][aã]o.*?([\d.,]+)[DC]/ui'),
            'exchange_fee'     => $get('/Emolumentos\s+([\d.,]+)/ui'),
            'custody_fee'      => $get('/Taxa\s+de\s+Transfer[eê]ncia\s+de\s+Ativos\s+([\d.,]+)/ui'),
            'net_value'        => $get('/[Ll][íi]quido\s+para\s+\d{2}\/\d{2}\/\d{4}\s+([\d.,]+)/u'),
        ];
    }
}
