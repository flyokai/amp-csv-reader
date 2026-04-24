# flyokai/amp-csv-reader

Async CSV file reader built on AMPHP 3.x. Non-blocking streaming of large CSV files using generator-based parsing and queue-based row buffering.

## Key Abstractions

### CsvReader

`CsvReader` implements `IteratorAggregate` — use in `foreach` for async row iteration.

**Constructor:**
```php
new CsvReader(
    ReadableStream $stream,      // AMPHP file stream
    int $bufferSize = 0,         // Queue depth (0 = unlimited)
    string $separator = ',',
    string $enclosure = '"',
    string $escape = "\\",
    ?Cancellation $stopCancellation = null
)
```

**Usage:**
```php
$file = File\openFile('data.csv', 'r');
$csvReader = new CsvReader($file, 500);

foreach ($csvReader as $row) {
    // $row is numeric array from str_getcsv()
}
```

**Lifecycle:**
1. `getIterator()` lazily creates a `Queue` and schedules `read()` on the event loop
2. `read()` pulls chunks from the stream, pushes to parser
3. `parse()` generator tracks escape mode (quoted fields), accumulates buffer
4. `consumeBuffer()` calls `str_getcsv()` on complete rows, pushes to queue
5. Consumer iterates via `ConcurrentIterator` from the queue

**Methods:**
- `isComplete(): bool` — whether all rows have been parsed
- `onComplete(Closure $callback): void` — register completion callback

### Helper Function

`array2csv(array $data, $delimiter, $enclosure, $escape): string` — converts array to CSV string using memory stream.

## Internal Flow

```
ReadableStream → Parser (generator, yields "\n" as sentinel)
    → Buffer accumulation (handles multi-line quoted fields)
    → consumeBuffer() → str_getcsv()
    → Queue → ConcurrentIterator → foreach
```

## Gotchas

- **No header handling**: Rows are always numeric arrays. No associative array or column mapping support.
- **Empty rows skipped**: `consumeBuffer()` silently drops rows that trim to empty.
- **bufferSize=0 means unlimited**: Large files with slow consumers can grow memory unbounded.
- **Single-use**: Cannot re-iterate after completion. `getIterator()` is lazy but non-resettable.
- **Cancellation flushes buffer**: `CancelledException` from the stop token still flushes remaining buffer before completing.
- **Multi-line fields**: Handled via escape-mode tracking — newlines inside quoted fields are preserved in buffer, row only flushed when outside all quotes.
