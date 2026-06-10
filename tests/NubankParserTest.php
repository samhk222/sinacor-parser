<?php

declare(strict_types=1);

use Samhk222\SinacorParser\NubankParser;
use Samhk222\SinacorParser\Resolvers\ConfigAssetResolver;
use Samhk222\SinacorParser\Enums\OperationType;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function nubank_parser(?array $map = null): NubankParser
{
    $resolver = $map ? new ConfigAssetResolver($map) : null;
    return new NubankParser($resolver);
}

// ---------------------------------------------------------------------------
// parseMoney
// ---------------------------------------------------------------------------

it('converte string monetária BR para float', function () {
    $parser = nubank_parser();
    $ref    = new ReflectionMethod($parser, 'parseMoney');

    expect($ref->invoke($parser, '1.935,90'))->toBe(1935.90)
        ->and($ref->invoke($parser, '376,10'))->toBe(376.10)
        ->and($ref->invoke($parser, '0,48'))->toBe(0.48);
});

// ---------------------------------------------------------------------------
// extractHeader
// ---------------------------------------------------------------------------

it('extrai cabeçalho corretamente', function () {
    $content = "Nr. Nota Folha Data pregão\n25090 1 10/06/2025\nLíquido para 12/06/2025\t1.936,47D";

    $parser = nubank_parser();
    $ref    = new ReflectionMethod($parser, 'extractHeader');
    $header = $ref->invoke($parser, $content);

    expect($header['note_number'])->toBe('25090')
        ->and($header['trade_date']->format('Y-m-d'))->toBe('2025-06-10')
        ->and($header['settlement_date']->format('Y-m-d'))->toBe('2025-06-12');
});

// ---------------------------------------------------------------------------
// extractTrades
// ---------------------------------------------------------------------------

it('extrai trades de compra no formato real do PDF', function () {
    $content = "Negocios realizados\n"
        . "B3 RV LISTADO CVISTA\tISHARE SP500 CI\t@#\t2 376,10\t752,20D\n"
        . "B3 RV LISTADO CVISTA\tIT NOW IDIV CI\t@\t7 100,10\t700,70D\n"
        . "Resumo dos Negócios";

    $parser = nubank_parser();
    $ref    = new ReflectionMethod($parser, 'extractTrades');
    $trades = $ref->invoke($parser, $content);

    expect($trades)->toHaveCount(2)
        ->and($trades[0]->operation)->toBe(OperationType::BUY)
        ->and($trades[0]->brokerAsset)->toBe('ISHARE SP500 CI')
        ->and($trades[0]->quantity)->toBe(2)
        ->and($trades[0]->unitPrice)->toBe(376.10)
        ->and($trades[0]->grossAmount)->toBe(752.20)
        ->and($trades[1]->quantity)->toBe(7);
});

it('extrai trade de venda no formato real do PDF', function () {
    $content = "Negocios realizados\n"
        . "B3 RV LISTADO VVISTA\tIVVB11\t5 397,00\t1985,00C\n"
        . "Resumo dos Negócios";

    $parser = nubank_parser();
    $ref    = new ReflectionMethod($parser, 'extractTrades');
    $trades = $ref->invoke($parser, $content);

    expect($trades[0]->operation)->toBe(OperationType::SELL);
});

// ---------------------------------------------------------------------------
// extractSummary
// ---------------------------------------------------------------------------

it('extrai resumo financeiro no formato real do PDF', function () {
    $content = "Resumo Financeiro\n"
        . "Valor líquido das operações\t1.935,90D\n"
        . "Taxa de liquidação/CCP\t0,48D\n"
        . "Taxa de Registro\t0,00D\n"
        . "Emolumentos\t0,09D\n"
        . "Taxa de Transferência de Ativos\t0,00D\n"
        . "Líquido para 12/06/2025\t1.936,47D\n";

    $parser  = nubank_parser();
    $ref     = new ReflectionMethod($parser, 'extractSummary');
    $summary = $ref->invoke($parser, $content);

    expect($summary['gross_operations'])->toBe(1935.90)
        ->and($summary['liquidation_fee'])->toBe(0.48)
        ->and($summary['exchange_fee'])->toBe(0.09)
        ->and($summary['net_value'])->toBe(1936.47);
});

// ---------------------------------------------------------------------------
// Resolver
// ---------------------------------------------------------------------------

it('mapeia ativo via ConfigAssetResolver', function () {
    $resolver = new ConfigAssetResolver([
        'ISHARE SP500 CI' => ['ticker' => 'IVVB11', 'type' => 'ETF'],
    ]);

    $result = $resolver->resolve('ISHARE SP500 CI');

    expect($result['ticker'])->toBe('IVVB11')
        ->and($result['mapped'])->toBeTrue()
        ->and($result['type'])->toBe('ETF');
});

it('retorna não mapeado para ativo desconhecido', function () {
    $resolver = new ConfigAssetResolver([]);
    $result   = $resolver->resolve('ATIVO DESCONHECIDO');

    expect($result['mapped'])->toBeFalse()
        ->and($result['ticker'])->toBeNull();
});

// ---------------------------------------------------------------------------
// PdfReadException
// ---------------------------------------------------------------------------

it('lança PdfReadException para arquivo inexistente', function () {
    $parser = nubank_parser();

    expect(fn() => $parser->notes('/tmp/nao_existe.pdf'))
        ->toThrow(\Samhk222\SinacorParser\Exceptions\PdfReadException::class);
});
