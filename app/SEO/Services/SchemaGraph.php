<?php

declare(strict_types=1);

namespace App\SEO\Services;

use JsonException;

final class SchemaGraph
{
    /**
     * Schema.org context URL.
     */
    private const CONTEXT = 'https://schema.org';

    /**
     * Internal schema version for future maintenance.
     */
    private const VERSION = '2.0';

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $schemas = [];

    /**
     * Tracks schema @id values to prevent duplicates.
     *
     * @var array<string, true>
     */
    private array $registeredIds = [];

    /**
     * Create a new SchemaGraph instance.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Return the internal schema engine version.
     */
    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * Add one schema object or a list of schema objects.
     *
     * @param array<string, mixed>|array<int, array<string, mixed>>|null $schema
     */
    public function add(?array $schema): self
    {
        if ($schema === null || $schema === []) {
            return $this;
        }

        if ($this->isSchemaList($schema)) {
            return $this->addMany($schema);
        }

        $normalised = $this->normaliseSchema($schema);

        if (! $this->isValidSchema($normalised)) {
            return $this;
        }

        $schemaId = $this->extractSchemaId($normalised);

        if ($schemaId !== null) {
            if (isset($this->registeredIds[$schemaId])) {
                return $this;
            }

            $this->registeredIds[$schemaId] = true;
        }

        $this->schemas[] = $normalised;

        return $this;
    }

    /**
     * Add multiple schema objects.
     *
     * @param array<int, array<string, mixed>> $schemas
     */
    public function addMany(array $schemas): self
    {
        foreach ($schemas as $schema) {
            if (is_array($schema)) {
                $this->add($schema);
            }
        }

        return $this;
    }

    /**
     * Add a schema only when the supplied condition is true.
     *
     * The schema may be provided directly or through a callback.
     *
     * @param array<string, mixed>|array<int, array<string, mixed>>|callable(): array|null $schema
     */
    public function when(bool $condition, array|callable|null $schema): self
    {
        if (! $condition) {
            return $this;
        }

        $resolved = is_callable($schema)
            ? $schema()
            : $schema;

        return $this->add(
            is_array($resolved) ? $resolved : null
        );
    }

    /**
     * Add a schema only when the supplied value is not empty.
     *
     * @param mixed $value
     * @param callable(mixed): array<string, mixed>|array<int, array<string, mixed>>|null $callback
     */
    public function whenFilled(mixed $value, callable $callback): self
    {
        if ($this->isEmptyValue($value)) {
            return $this;
        }

        $schema = $callback($value);

        return $this->add(
            is_array($schema) ? $schema : null
        );
    }

    /**
     * Replace an existing schema by its @id.
     *
     * If the @id does not exist, the schema is added normally.
     *
     * @param array<string, mixed> $schema
     */
    public function replace(array $schema): self
    {
        $normalised = $this->normaliseSchema($schema);

        if (! $this->isValidSchema($normalised)) {
            return $this;
        }

        $schemaId = $this->extractSchemaId($normalised);

        if ($schemaId === null) {
            return $this->add($normalised);
        }

        foreach ($this->schemas as $index => $existingSchema) {
            if ($this->extractSchemaId($existingSchema) !== $schemaId) {
                continue;
            }

            $this->schemas[$index] = $normalised;
            $this->registeredIds[$schemaId] = true;

            return $this;
        }

        return $this->add($normalised);
    }

    /**
     * Merge new values into an existing schema identified by @id.
     *
     * If the target schema does not exist, a new schema is added.
     *
     * @param array<string, mixed> $schema
     */
    public function merge(array $schema): self
    {
        $normalised = $this->normaliseSchema($schema);

        if (! $this->isValidSchema($normalised)) {
            return $this;
        }

        $schemaId = $this->extractSchemaId($normalised);

        if ($schemaId === null) {
            return $this->add($normalised);
        }

        foreach ($this->schemas as $index => $existingSchema) {
            if ($this->extractSchemaId($existingSchema) !== $schemaId) {
                continue;
            }

            $merged = array_replace_recursive(
                $existingSchema,
                $normalised
            );

            $this->schemas[$index] = $this->normaliseSchema($merged);

            return $this;
        }

        return $this->add($normalised);
    }

    /**
     * Remove a schema by its @id.
     */
    public function remove(string $schemaId): self
    {
        $schemaId = trim($schemaId);

        if ($schemaId === '') {
            return $this;
        }

        $this->schemas = array_values(array_filter(
            $this->schemas,
            fn (array $schema): bool =>
                $this->extractSchemaId($schema) !== $schemaId
        ));

        unset($this->registeredIds[$schemaId]);

        return $this;
    }

    /**
     * Determine whether a schema with the supplied @id exists.
     */
    public function has(string $schemaId): bool
    {
        return isset($this->registeredIds[trim($schemaId)]);
    }

    /**
     * Return a schema by its @id.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $schemaId): ?array
    {
        $schemaId = trim($schemaId);

        if ($schemaId === '') {
            return null;
        }

        foreach ($this->schemas as $schema) {
            if ($this->extractSchemaId($schema) === $schemaId) {
                return $schema;
            }
        }

        return null;
    }

    /**
     * Return all schemas without the outer @context/@graph wrapper.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return array_values($this->schemas);
    }

    /**
     * Return the total number of registered schema objects.
     */
    public function count(): int
    {
        return count($this->schemas);
    }

    /**
     * Determine whether the graph contains no schema objects.
     */
    public function isEmpty(): bool
    {
        return $this->schemas === [];
    }

    /**
     * Reset the complete graph.
     */
    public function clear(): self
    {
        $this->schemas = [];
        $this->registeredIds = [];

        return $this;
    }

    /**
     * Return the complete JSON-LD graph as an array.
     *
     * @return array{
     *     @context: string,
     *     @graph: array<int, array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            '@context' => self::CONTEXT,
            '@graph' => $this->all(),
        ];
    }

    /**
     * Encode the complete graph as JSON-LD.
     *
     * @throws JsonException
     */
    public function toJson(bool $pretty = false): string
    {
        $options = JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE;

        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }

        return json_encode(
            $this->toArray(),
            $options
        );
    }

    /**
     * Safely encode the graph without throwing an exception.
     */
    public function toSafeJson(bool $pretty = false): string
    {
        try {
            return $this->toJson($pretty);
        } catch (JsonException) {
            return '{}';
        }
    }

    /**
     * Render a complete application/ld+json script tag.
     */
    public function render(bool $pretty = false): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        return sprintf(
            '<script type="application/ld+json">%s</script>',
            $this->toSafeJson($pretty)
        );
    }

    /**
     * Validate the final graph structure.
     *
     * @return array{
     *     valid: bool,
     *     errors: array<int, string>,
     *     warnings: array<int, string>,
     *     schema_count: int,
     *     schema_version: string
     * }
     */
    public function validate(): array
    {
        $errors = [];
        $warnings = [];

        if ($this->schemas === []) {
            $warnings[] = 'Schema graph is empty.';
        }

        $seenIds = [];

        foreach ($this->schemas as $index => $schema) {
            $position = $index + 1;

            if (! isset($schema['@type'])) {
                $errors[] = "Schema {$position} is missing @type.";
            }

            if (
                isset($schema['@type'])
                && ! is_string($schema['@type'])
                && ! is_array($schema['@type'])
            ) {
                $errors[] = "Schema {$position} has an invalid @type value.";
            }

            $schemaId = $this->extractSchemaId($schema);

            if ($schemaId !== null) {
                if (isset($seenIds[$schemaId])) {
                    $errors[] = "Duplicate schema @id detected: {$schemaId}";
                }

                $seenIds[$schemaId] = true;
            } else {
                $warnings[] = "Schema {$position} does not contain an @id.";
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'schema_count' => $this->count(),
            'schema_version' => self::VERSION,
        ];
    }

    /**
     * Determine whether the supplied array is a list of schema objects.
     *
     * @param array<mixed> $value
     */
    private function isSchemaList(array $value): bool
    {
        if (! array_is_list($value)) {
            return false;
        }

        if ($value === []) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_array($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether a schema object has the minimum valid structure.
     *
     * @param array<string, mixed> $schema
     */
    private function isValidSchema(array $schema): bool
    {
        if ($schema === []) {
            return false;
        }

        if (! array_key_exists('@type', $schema)) {
            return false;
        }

        $type = $schema['@type'];

        if (is_string($type)) {
            return trim($type) !== '';
        }

        if (is_array($type)) {
            return array_values(array_filter(
                $type,
                static fn (mixed $item): bool =>
                    is_string($item) && trim($item) !== ''
            )) !== [];
        }

        return false;
    }

    /**
     * Extract and normalise a schema @id.
     *
     * @param array<string, mixed> $schema
     */
    private function extractSchemaId(array $schema): ?string
    {
        $schemaId = $schema['@id'] ?? null;

        if (! is_string($schemaId)) {
            return null;
        }

        $schemaId = trim($schemaId);

        return $schemaId !== ''
            ? $schemaId
            : null;
    }

    /**
     * Remove null, empty-string and empty-array values recursively.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function normaliseSchema(array $schema): array
    {
        $normalised = $this->cleanArray($schema);

        if (
            isset($normalised['@type'])
            && is_array($normalised['@type'])
        ) {
            $normalised['@type'] = array_values(array_unique(
                array_filter(
                    $normalised['@type'],
                    static fn (mixed $type): bool =>
                        is_string($type) && trim($type) !== ''
                )
            ));
        }

        return $normalised;
    }

    /**
     * Recursively clean arrays while preserving numeric list indexes.
     *
     * @param array<mixed> $value
     * @return array<mixed>
     */
    private function cleanArray(array $value): array
    {
        $isList = array_is_list($value);
        $cleaned = [];

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $item = $this->cleanArray($item);
            } elseif (is_string($item)) {
                $item = trim($item);
            }

            if ($this->isEmptyValue($item)) {
                continue;
            }

            if ($isList) {
                $cleaned[] = $item;
            } else {
                $cleaned[$key] = $item;
            }
        }

        return $cleaned;
    }

    /**
     * Determine whether a value should be removed from JSON-LD.
     */
    private function isEmptyValue(mixed $value): bool
    {
        return $value === null
            || $value === ''
            || $value === [];
    }
}