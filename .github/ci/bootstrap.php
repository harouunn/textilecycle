<?php

/**
 * Bootstrap réservé à PHPUnit : migrations additives sur SQLite en mémoire.
 * Les modules, les tests et les fichiers vendor restent inchangés.
 */
$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';

$path = $root.'/vendor/laravel/framework/src/Illuminate/Foundation/Testing/RefreshDatabase.php';
$source = file_get_contents($path);
$original = '$this->artisan(\'migrate:fresh\', $this->migrateFreshUsing());';
$replacement = <<<'PHP'
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
    throw new \RuntimeException('CI : seule une base SQLite :memory: est autorisée.');
}
$this->artisan('migrate', ['--force' => true])->assertExitCode(0);
PHP;

// Échouer explicitement si Laravel change cette méthode, au lieu de lancer
// sa préparation destructive ou de modifier silencieusement une autre méthode.
if (! is_string($source) || substr_count($source, $original) !== 1) {
    throw new RuntimeException('Bootstrap CI incompatible avec RefreshDatabase : adaptation à vérifier.');
}

// Charger la même définition de trait avec cette seule substitution en mémoire.
// Aucune écriture dans vendor et aucune substitution de logique métier.
eval(substr(str_replace($original, $replacement, $source), 5));
