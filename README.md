# sinacor-parser

Parser de **notas de corretagem** no padrão **SINACOR (B3)** para PHP 8.2+.

Extrai notas, movimentações, ativos e resumo financeiro de PDFs de corretoras brasileiras.

---

## Instalação

```bash
composer require samhk222/sinacor-parser
```

> Requer `pdftotext` instalado no sistema (pacote `poppler-utils`):
> ```bash
> sudo apt install poppler-utils   # Ubuntu/Debian
> brew install poppler             # macOS
> ```

---

## Uso básico

```php
use Samhk222\SinacorParser\NubankParser;

$parser = new NubankParser();

// Todas as movimentações
$movements = $parser->movements('/caminho/para/nota.pdf');

foreach ($movements as $m) {
    echo $m->brokerAsset . ' | ' . $m->operation->value . ' | ' . $m->quantity;
}
```

---

## Mapeamento de ativos

O nome do ativo na nota (`ISHARE SP500 CI`) difere do ticker oficial (`IVVB11`).  
Use um **resolver** para fazer esse mapeamento:

### Via array PHP

```php
use Samhk222\SinacorParser\NubankParser;
use Samhk222\SinacorParser\Resolvers\ConfigAssetResolver;

$resolver = new ConfigAssetResolver([
    'ISHARE SP500 CI' => ['ticker' => 'IVVB11', 'type' => 'ETF'],
    'IT NOW IDIV'     => ['ticker' => 'IDIV11', 'type' => 'ETF'],
    'GARE REND'       => ['ticker' => 'GARE11', 'type' => 'FII'],
]);

$parser = new NubankParser($resolver);
```

### Via CSV

```php
use Samhk222\SinacorParser\Resolvers\CsvAssetResolver;

$resolver = new CsvAssetResolver(__DIR__ . '/assets.csv');
$parser   = new NubankParser($resolver);
```

Formato do CSV (`broker_asset,ticker,type`):
```csv
broker_asset,ticker,type
ISHARE SP500 CI,IVVB11,ETF
GARE REND,GARE11,FII
```

### Encadeando resolvers

```php
use Samhk222\SinacorParser\Resolvers\ChainAssetResolver;

$resolver = new ChainAssetResolver(
    new ConfigAssetResolver($myMap),       // tenta primeiro
    new CsvAssetResolver('assets.csv'),    // fallback
);
```

---

## Métodos disponíveis

```php
$parser->notes($pdf);           // BrokerageNote[]
$parser->movements($pdf);       // Movement[]
$parser->assets($pdf);          // string[] — tickers únicos
$parser->unmappedAssets($pdf);  // string[] — ativos sem ticker
$parser->summary($pdf);         // ImportSummary
```

---

## Exemplo de retorno — `movements()`

```php
[
    [
        'movement_hash'  => 'abc123...',
        'broker'         => 'NUBANK',
        'note_number'    => '25090',
        'trade_date'     => '2025-06-10',
        'broker_asset'   => 'ISHARE SP500 CI',
        'ticker'         => 'IVVB11',
        'ticker_mapped'  => true,
        'mapping_source' => 'config',
        'asset_type'     => 'ETF',
        'operation'      => 'BUY',
        'quantity'       => 2,
        'unit_price'     => 376.10,
        'gross_amount'   => 752.20,
    ],
]
```

---

## Exemplo de retorno — `summary()`

```php
[
    'total_notes'     => 1,
    'total_movements' => 3,
    'total_unmapped'  => 0,
    'unmapped_assets' => [],
]
```

---

## Corretoras suportadas

| Corretora | Status  |
|-----------|---------|
| Nubank    | ✅ v0.1 |
| XP        | 🔜 v0.2 |
| Rico      | 🔜 v0.2 |
| Clear     | 🔜 v0.2 |
| BTG       | 🔜 v0.3 |
| Inter     | 🔜 v0.3 |

Todas seguem o padrão SINACOR — cerca de 80% da lógica é compartilhada.  
Contribuições são bem-vindas!

---

## Adicionando uma nova corretora

Crie uma classe que estende `AbstractSinacorParser` e implemente:

```php
class XPParser extends AbstractSinacorParser
{
    public const BROKER = 'XP';

    protected function parseNote(string $content): BrokerageNote { ... }
    protected function extractHeader(string $content): array     { ... }
    protected function extractTrades(string $content): array     { ... }
    protected function extractSummary(string $content): array    { ... }
}
```

---

## Desenvolvimento

```bash
composer install
composer test    # Pest
composer stan    # PHPStan level 8
composer check   # stan + test
```

---

## Licença

MIT © [Samuel Ferreira](https://github.com/samhk222)
