<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;

trait UpcyclingDatabase
{
    use RefreshDatabase;

    protected function migrateDatabases()
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Les tests Upcycling exigent SQLite en mémoire.');
        }
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }
}
