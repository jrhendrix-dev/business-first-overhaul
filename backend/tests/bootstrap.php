<?php
declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

DG\BypassFinals::enable();

// Load .env cascade (.env, .env.test, .env.local, etc.)
if (method_exists(Dotenv::class, 'bootEnv')) {
    // usePutenv makes values available to getenv()/putenv, which some libs use
    (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__) . '/.env');
}

// Ensure test defaults but allow overrides from the shell/CI
$_SERVER['APP_ENV']  ??= $_ENV['APP_ENV']  ?? 'test';
$_SERVER['APP_DEBUG'] ??= $_ENV['APP_DEBUG'] ?? '1';

/**
 * Build the test DB once per process:
 *  - create DB if missing
 *  - run migrations (fallback to schema:update if migrations aren’t installed)
 * Set SKIP_TEST_DB_BUILD=1 to disable (e.g. when a CI step already did it).
 */
(static function (): void {
    if (getenv('SKIP_TEST_DB_BUILD') === '1') {
        return;
    }

    // If using SQLite :memory:, nothing to do
    $dbUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '';
    if (str_contains($dbUrl, 'sqlite:///:memory:')) {
        return;
    }

    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $kernel = new \App\Kernel('test', (bool) ($_SERVER['APP_DEBUG'] ?? true));
    $app    = new Application($kernel);
    $app->setAutoExit(false);
    $out = new NullOutput();

    $app->run(new ArrayInput(['command' => 'doctrine:database:create', '--if-not-exists' => true, '--env' => 'test']), $out);

    $exit = $app->run(new ArrayInput(['command' => 'doctrine:migrations:migrate', '--no-interaction' => true, '--env' => 'test']), $out);

    if ($exit !== 0) {
        $app->run(new ArrayInput(['command' => 'doctrine:schema:update', '--force' => true, '--env' => 'test']), $out);
    }

    $kernel->shutdown();
})();

if (!empty($_SERVER['APP_DEBUG'])) {
    umask(0000);
}
