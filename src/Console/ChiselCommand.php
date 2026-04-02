<?php

namespace Laravel\Chisel\Console;

use Illuminate\Console\Command;
use Illuminate\Process\Factory;
use Laravel\Chisel\NodePackageManager;
use Symfony\Component\Process\Process as SymfonyProcess;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class ChiselCommand extends Command
{
    protected $signature = 'chisel
        {--path=chisel.php : Path to chisel.php}
        {--answers= : JSON string of answers to skip interactive prompts}
        {--delete-script : Delete the chisel.php after a successful run}';

    protected $description = 'Run the chisel.php file to add or remove features';

    public function handle(): int
    {
        $directory = $this->laravel->basePath();
        $scriptPath = $directory.'/'.$this->option('path');

        if (! file_exists($scriptPath)) {
            $this->components->error('No chisel script found at '.$this->option('path'));

            return self::FAILURE;
        }

        if (! $this->option('answers')) {
            warning('This will modify files in: '.$directory);
            note('Make sure you can restore changes (e.g. git checkout).');

            if (! confirm('Continue?', default: false)) {
                return self::SUCCESS;
            }
        }

        $process = (new Factory)
            ->path($directory)
            ->env(['LARAVEL_INSTALLER_AUTOLOADER' => $this->laravel->basePath('vendor/autoload.php')])
            ->forever();

        if (! $this->option('answers') && $this->input->isInteractive() && SymfonyProcess::isTtySupported()) {
            $process->tty();
        }

        $args = [PHP_BINARY, $scriptPath];

        if ($this->option('answers')) {
            $args[] = (string) $this->option('answers');
        }

        $result = $process->run($args, function (string $type, string $line): void {
            $this->output->write('    '.$line);
        });

        if ($result->failed()) {
            $this->components->error('Chisel script failed with exit code: '.$result->exitCode());

            return $result->exitCode() ?? self::FAILURE;
        }

        info('Chisel script completed successfully.');

        $this->rebuildAssets($directory);
        $this->handleScriptDeletion($scriptPath);

        return self::SUCCESS;
    }

    protected function rebuildAssets(string $directory): void
    {
        $packageManager = NodePackageManager::detect($directory);

        info('Installing dependencies with '.$packageManager->value.'...');

        $install = (new Factory)
            ->path($directory)
            ->forever()
            ->run($packageManager->installProcessCommand(), function (string $type, string $line): void {
                $this->output->write('    '.$line);
            });

        if (! $install->successful()) {
            warning($packageManager->installCommand().' failed. You may need to run "'.$packageManager->installCommand().'" and "'.$packageManager->buildCommand().'" manually.');

            return;
        }

        info('Building assets...');

        $build = (new Factory)
            ->path($directory)
            ->forever()
            ->run($packageManager->buildProcessCommand(), function (string $type, string $line): void {
                $this->output->write('    '.$line);
            });

        if ($build->successful()) {
            info('Assets built successfully.');
        } else {
            warning('Asset build failed. You may need to run "'.$packageManager->buildCommand().'" manually.');
        }
    }

    protected function handleScriptDeletion(string $scriptPath): void
    {
        if (! file_exists($scriptPath)) {
            return;
        }

        if ($this->option('delete-script')) {
            unlink($scriptPath);
            info('Deleted chisel script: '.$this->option('path'));

            return;
        }

        if ($this->input->isInteractive() && confirm('Delete '.$this->option('path').'?', default: false)) {
            unlink($scriptPath);
            info('Deleted chisel script: '.$this->option('path'));

            return;
        }

        note('Kept chisel script at '.$this->option('path').'. Pass --delete-script to remove it automatically.');
    }
}
