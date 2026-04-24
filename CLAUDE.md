# flyokai/amp-csv-reader

Async CSV reader for AMPHP 3.x with generator-based parsing and queue buffering.

See [AGENTS.md](AGENTS.md) for detailed documentation.

## Quick Reference

- **Entry point**: `CsvReader` implements `IteratorAggregate` — use in `foreach`
- **Parsing**: Generator state machine → `str_getcsv()` for final interpretation
- **Backpressure**: Configurable `bufferSize` (0 = unlimited)
- **Multi-line**: Escape-mode tracking for quoted fields spanning lines
- **Helper**: `array2csv()` for reverse operation (array → CSV string)
- **No headers**: Rows are always numeric arrays
