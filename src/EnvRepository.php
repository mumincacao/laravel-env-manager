<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager;

use Illuminate\Support\Arr;
use Mumincacao\LaravelEnvManager\Enums\EnvStatus;

class EnvRepository
{
    /**
     * @var array<string, string|null>
     */
    private readonly array $originalData;

    /**
     * @param array<string, string|null> $data
     */
    public function __construct(
        private array $data,
    ) {
        ksort($this->data);
        $this->originalData = $this->data;
    }

    public function set(string $key, ?string $value): void
    {
        $this->data[$key] = $value;
        ksort($this->data);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key): ?string
    {
        return $this->data[$key] ?? null;
    }

    public function getOriginal(string $key): ?string
    {
        return $this->originalData[$key] ?? null;
    }

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_unique(Arr::sort(array_merge(array_keys($this->data), array_keys($this->originalData))));
    }

    public function reset(): void
    {
        $this->data = $this->originalData;
    }

    public function isClean(): bool
    {
        return $this->data === $this->originalData;
    }

    public function getStatus(string $key): EnvStatus
    {
        $value = $this->get($key);
        $originalValue = $this->getOriginal($key);

        return match (true) {
            $value === $originalValue => EnvStatus::Keep,
            $value !== null && $originalValue === null => EnvStatus::Added,
            $value === null && $originalValue !== null => EnvStatus::Removed,
            default => EnvStatus::Modified,
        };
    }
}
