<?php

declare(strict_types=1);

namespace Tests\Unit;

use Mumincacao\LaravelEnvManager\Enums\EnvStatus;
use Mumincacao\LaravelEnvManager\EnvRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnvRepository::class)]
class EnvRepositoryTest extends TestCase
{
    private const SAMPLE_DATA = [
        'APP_NAME' => 'Laravel',
        'APP_ENV' => 'local',
        'APP_KEY' => 'base64:somekey',
    ];

    public function testReadData(): void
    {
        $repository = new EnvRepository(self::SAMPLE_DATA);

        foreach (self::SAMPLE_DATA as $key => $value) {
            $this->assertTrue($repository->has($key));
            $this->assertSame($value, $repository->get($key));
            $this->assertSame($value, $repository->getOriginal($key));
        }

        $this->assertFalse($repository->has('NON_EXISTENT_KEY'));
        $this->assertNull($repository->get('NON_EXISTENT_KEY'));
        $this->assertNull($repository->getOriginal('NON_EXISTENT_KEY'));

        $this->assertEqualsCanonicalizing(array_keys(self::SAMPLE_DATA), $repository->keys());
        $this->assertEqualsCanonicalizing(self::SAMPLE_DATA, $repository->all());
    }

    public function testModifyData(): void
    {
        $repository = new EnvRepository(self::SAMPLE_DATA);
        $this->assertTrue($repository->isClean());

        $repository->set('APP_ENV', 'production');
        $this->assertSame('production', $repository->get('APP_ENV'));
        $this->assertSame('local', $repository->getOriginal('APP_ENV'));
        $this->assertSame(EnvStatus::Modified, $repository->getStatus('APP_ENV'));
        $this->assertFalse($repository->isClean());

        $repository->set('NEW_KEY', 'new_value');
        $this->assertTrue($repository->has('NEW_KEY'));
        $this->assertSame('new_value', $repository->get('NEW_KEY'));
        $this->assertNull($repository->getOriginal('NEW_KEY'));
        $this->assertSame(EnvStatus::Added, $repository->getStatus('NEW_KEY'));
        $this->assertFalse($repository->isClean());

        $repository->remove('APP_KEY');
        $this->assertFalse($repository->has('APP_KEY'));
        $this->assertNull($repository->get('APP_KEY'));
        $this->assertSame('base64:somekey', $repository->getOriginal('APP_KEY'));
        $this->assertSame(EnvStatus::Removed, $repository->getStatus('APP_KEY'));
        $this->assertSame(EnvStatus::Keep, $repository->getStatus('APP_NAME'));
        $this->assertFalse($repository->isClean());
    }

    public function testResetData(): void
    {
        $repository = new EnvRepository(self::SAMPLE_DATA);

        $repository->set('APP_ENV', 'production');
        $repository->remove('APP_KEY');

        $this->assertNotEqualsCanonicalizing(self::SAMPLE_DATA, $repository->all());
        $this->assertFalse($repository->isClean());

        $repository->reset();

        $this->assertEqualsCanonicalizing(self::SAMPLE_DATA, $repository->all());
        $this->assertTrue($repository->isClean());
    }

    public function testSortKeys(): void
    {
        $repository = new EnvRepository([
            'B_KEY' => 'value2',
            'A_KEY' => 'value1',
            'C_KEY' => 'value3',
        ]);

        $this->assertSame(['A_KEY', 'B_KEY', 'C_KEY'], $repository->keys());

        $repository->set('0_KEY', 'value0');
        $this->assertSame(['0_KEY', 'A_KEY', 'B_KEY', 'C_KEY'], $repository->keys());
    }
}
