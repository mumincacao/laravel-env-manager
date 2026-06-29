<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Actions;

class GrepAction extends ListAction
{
    public function description(): string
    {
        return 'Search for environment variables';
    }

    public function execute(): void
    {
        $keys = $this->repository->keys();
        if (count($keys) === 0) {
            $this->handler->info('No environment variables found.');

            return;
        }

        $keyword = $this->inputKeyword();
        $matchedKeys = $this->filterKeysByKeyword($keys, $keyword);
        if (count($matchedKeys) === 0) {
            $this->handler->info('No matching environment variables found.');

            return;
        }

        $this->handler->info('Matching environment variables:');
        $this->printVars($matchedKeys);
    }

    private function inputKeyword(): string
    {
        while (true) {
            $keyword = $this->handler->ask('Enter search word:');
            if ($keyword !== '' && $keyword !== null) {
                break;
            }
            $this->handler->error('Search word cannot be empty. Please enter again.');
        }

        return $keyword;
    }

    /**
     * @param  list<string>  $keys
     *
     * @return list<string>
     */
    private function filterKeysByKeyword(array $keys, string $keyword): array
    {
        return array_filter($keys, function ($key) use ($keyword) {
            if (str_contains($key, $keyword)) {
                return true;
            }
            if (str_contains($this->repository->get($key) ?? '', $keyword)) {
                return true;
            }
            if (str_contains($this->repository->getOriginal($key) ?? '', $keyword)) {
                return true;
            }

            return false;
        });
    }
}
