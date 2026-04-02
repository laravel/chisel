<?php

use Illuminate\Console\Application as ArtisanApplication;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Laravel\Chisel\ChiselServiceProvider;
use Laravel\Chisel\Console\ChiselCommand as ConsoleChiselCommand;
use Symfony\Component\Console\Tester\CommandTester;

final class TestLaravelApp extends Container
{
    public function __construct(
        private readonly string $basePath,
        private readonly bool $runningInConsole = true,
    ) {}

    public function basePath(string $path = ''): string
    {
        return $path === '' ? $this->basePath : $this->basePath.'/'.$path;
    }

    public function runningInConsole(): bool
    {
        return $this->runningInConsole;
    }

    public function runningUnitTests(): bool
    {
        return true;
    }
}

function createCommandProject(string $path, bool $installFails = false, bool $buildFails = false): void
{
    $npmLog = var_export($path.'/npm.log', true);
    $installFailure = $installFails ? "if [ \"\$1\" = \"install\" ]; then exit 1; fi\n" : '';
    $buildFailure = $buildFails ? "if [ \"\$1\" = \"run\" ] && [ \"\$2\" = \"build\" ]; then exit 1; fi\n" : '';

    mkdir($path.'/vendor', 0777, true);
    mkdir($path.'/bin', 0777, true);

    file_put_contents($path.'/vendor/autoload.php', "<?php\n\nrequire ".var_export(realpath(__DIR__.'/../vendor/autoload.php'), true).";\n");
    file_put_contents($path.'/flag.txt', 'before');
    file_put_contents($path.'/example-chisel.php', <<<'PHP'
<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER');

use Laravel\Chisel\Chisel;

echo "script:running\n";

Chisel::in(__DIR__)
    ->withAnswers($argv[1] ?? null)
    ->file('flag.txt')
    ->replace('before', 'after');
PHP);

    file_put_contents($path.'/bin/npm', <<<SH
#!/bin/sh
printf '%s\n' "\$*" >> {$npmLog}
{$installFailure}{$buildFailure}
echo "npm:\$*"
SH);
    chmod($path.'/bin/npm', 0755);
}

function createPackageManagerCommandProject(string $path, string $packageManager): void
{
    $log = var_export($path.'/'.$packageManager.'.log', true);
    $composerScripts = match ($packageManager) {
        'yarn' => ['dev' => 'yarn run dev'],
        'pnpm' => ['dev' => 'pnpm dev'],
        'bun' => ['dev' => 'bun run dev'],
    };

    mkdir($path.'/vendor', 0777, true);
    mkdir($path.'/bin', 0777, true);

    file_put_contents($path.'/vendor/autoload.php', "<?php\n\nrequire ".var_export(realpath(__DIR__.'/../vendor/autoload.php'), true).";\n");
    file_put_contents($path.'/flag.txt', 'before');
    file_put_contents($path.'/composer.json', json_encode(['scripts' => $composerScripts], JSON_THROW_ON_ERROR));
    file_put_contents($path.'/example-chisel.php', <<<'PHP'
<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER');

use Laravel\Chisel\Chisel;

echo "script:running\n";

Chisel::in(__DIR__)
    ->withAnswers($argv[1] ?? null)
    ->file('flag.txt')
    ->replace('before', 'after');
PHP);

    file_put_contents($path.'/bin/'.$packageManager, <<<SH
#!/bin/sh
printf '%s\n' "\$*" >> {$log}
echo "{$packageManager}:\$*"
SH);
    chmod($path.'/bin/'.$packageManager, 0755);
}

function createCommandTester(string $path): CommandTester
{
    $app = new TestLaravelApp($path);
    $command = new ConsoleChiselCommand;
    $command->setLaravel($app);

    return new CommandTester($command);
}

beforeEach(function (): void {
    $this->tempDir = __DIR__.'/../tests-output/command-project-'.uniqid();

    mkdir($this->tempDir, 0777, true);

    ArtisanApplication::forgetBootstrappers();
});

afterEach(function (): void {
    ArtisanApplication::forgetBootstrappers();

    if (! file_exists($this->tempDir)) {
        return;
    }

    if (PHP_OS_FAMILY === 'Windows') {
        system("rd /s /q \"{$this->tempDir}\"");

        return;
    }

    system("rm -rf \"{$this->tempDir}\"");
});

it('registers the artisan command through the service provider', function (): void {
    $app = new TestLaravelApp($this->tempDir);

    (new ChiselServiceProvider($app))->boot();

    $artisan = new ArtisanApplication($app, new Dispatcher($app), 'test');

    expect($artisan->has('chisel'))->toBeTrue();
});

it('fails when the target script is missing', function (): void {
    $tester = createCommandTester($this->tempDir);

    $exitCode = $tester->execute([
        '--path' => 'example-chisel.php',
        '--answers' => '{}',
    ]);

    expect($exitCode)->toBe(ConsoleChiselCommand::FAILURE)
        ->and($tester->getDisplay())->toContain('No chisel script found at example-chisel.php');
});

it('runs the chisel script, rebuilds assets, and deletes the script when requested', function (): void {
    createCommandProject($this->tempDir);

    $originalPath = getenv('PATH') ?: '';
    putenv('PATH='.$this->tempDir.'/bin:'.$originalPath);

    try {
        $tester = createCommandTester($this->tempDir);

        $exitCode = $tester->execute([
            '--path' => 'example-chisel.php',
            '--answers' => '{}',
            '--delete-script' => true,
        ]);
    } finally {
        putenv('PATH='.$originalPath);
    }

    expect($exitCode)->toBe(ConsoleChiselCommand::SUCCESS)
        ->and(file_get_contents($this->tempDir.'/flag.txt'))->toBe('after')
        ->and($this->tempDir.'/example-chisel.php')->not->toBeFile()
        ->and(file_get_contents($this->tempDir.'/npm.log'))->toContain('install')
        ->and(file_get_contents($this->tempDir.'/npm.log'))->toContain('run build')
        ->and($tester->getDisplay())->toContain('script:running')
        ->and($tester->getDisplay())->toContain('Assets built successfully.');
});

it('keeps the script when npm install fails', function (): void {
    createCommandProject($this->tempDir, installFails: true);

    $originalPath = getenv('PATH') ?: '';
    putenv('PATH='.$this->tempDir.'/bin:'.$originalPath);

    try {
        $tester = createCommandTester($this->tempDir);

        $exitCode = $tester->execute([
            '--path' => 'example-chisel.php',
            '--answers' => '{}',
        ]);
    } finally {
        putenv('PATH='.$originalPath);
    }

    expect($exitCode)->toBe(ConsoleChiselCommand::SUCCESS)
        ->and($this->tempDir.'/example-chisel.php')->toBeFile()
        ->and($tester->getDisplay())->toContain('npm install failed')
        ->and($tester->getDisplay())->toContain('Kept chisel script at example-chisel.php');
});

it('rebuilds assets with the detected package manager from composer scripts', function (): void {
    createPackageManagerCommandProject($this->tempDir, 'bun');

    $originalPath = getenv('PATH') ?: '';
    putenv('PATH='.$this->tempDir.'/bin:'.$originalPath);

    try {
        $tester = createCommandTester($this->tempDir);

        $exitCode = $tester->execute([
            '--path' => 'example-chisel.php',
            '--answers' => '{}',
        ]);
    } finally {
        putenv('PATH='.$originalPath);
    }

    expect($exitCode)->toBe(ConsoleChiselCommand::SUCCESS)
        ->and(file_get_contents($this->tempDir.'/flag.txt'))->toBe('after')
        ->and(file_get_contents($this->tempDir.'/bun.log'))->toContain('install')
        ->and(file_get_contents($this->tempDir.'/bun.log'))->toContain('run build')
        ->and($tester->getDisplay())->toContain('Installing dependencies with bun...')
        ->and($tester->getDisplay())->toContain('Assets built successfully.');
});
