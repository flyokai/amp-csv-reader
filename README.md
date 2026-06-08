# flyokai/amp-csv-reader

> User docs → [`README.md`](README.md) · Agent quick-ref → [`CLAUDE.md`](CLAUDE.md) · Agent deep dive → [`AGENTS.md`](AGENTS.md)

> Async, generator-based CSV streaming for AMPHP 3.x with backpressure-aware row buffering.

Streams a CSV file row by row without buffering the whole file in memory, while staying friendly to the AMPHP cooperative event loop. Multi-line quoted fields are handled correctly.

## Features

- `IteratorAggregate` — drop into `foreach`
- Stateful generator parser → `str_getcsv()` for final field interpretation
- Configurable bounded queue for backpressure
- Cancellation support
- `array2csv()` helper for the reverse direction

## Installation

```bash
composer require flyokai/amp-csv-reader
```

## Quick start

```php
use Amp\File;
use Flyokai\AmpCsvReader\CsvReader;

$stream = File\openFile('data.csv', 'r');
$reader = new CsvReader($stream, bufferSize: 500);

foreach ($reader as $row) {
    // $row is a numeric array — no header handling
}
```

## Constructor

```php
new CsvReader(
    Amp\ByteStream\ReadableStream $stream,
    int $bufferSize = 0,                 // 0 = unlimited
    string $separator = ',',
    string $enclosure = '"',
    string $escape = "\\",
    ?Amp\Cancellation $stopCancellation = null,
);
```

## Lifecycle

```
ReadableStream → Parser (generator)
    → Buffer accumulation (multi-line quoted fields)
    → consumeBuffer() → str_getcsv()
    → Queue → ConcurrentIterator → foreach
```

1. `getIterator()` lazily creates a `Queue` and schedules `read()` on the event loop
2. `read()` pulls chunks from the stream, feeds the parser
3. `parse()` tracks escape mode for quoted fields, accumulates a row buffer
4. Complete rows flow through `str_getcsv()` and into the queue
5. The consumer iterates via `ConcurrentIterator`

## Helpers

- `isComplete(): bool` — whether all rows have been parsed
- `onComplete(\Closure $callback): void` — fires when parsing finishes
- `array2csv(array $data, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): string` — array → CSV (uses `php://memory`)

## Gotchas

- **No header handling** — every row is a numeric array. Map columns yourself if you need them by name.
- **Empty rows are dropped** — `consumeBuffer()` silently skips lines that trim to empty.
- **`bufferSize=0` is unlimited** — slow consumers on large files will grow memory unbounded.
- **Single-use** — `getIterator()` is lazy but non-resettable. To re-read the file, open a new stream and a new reader.
- **Cancellation flushes the buffer** — the reader still emits buffered rows after cancellation before completing.

## See also

- Used by data-import pipelines built on `flyokai/amp-data-pipeline`.

## License

MIT
