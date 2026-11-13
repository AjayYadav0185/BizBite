<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Database backup — runs nightly via the scheduler (routes/console.php).
 *
 *   SQLite : consistent online copy via `VACUUM INTO` (safe while billing).
 *   MySQL  : mysqldump --single-transaction
 *   Postgres: pg_dump
 *
 * Files land in storage/app/backups as gzip, oldest pruned after --keep days.
 * Copy that folder off the machine (Google Drive / S3 / USB) — a backup on
 * the same disk does not survive a dead disk.
 */
final class BackupCommand extends Command
{
    protected $signature = 'bizbite:backup {--keep=14 : Days of backups to keep}';

    protected $description = 'Back up the BizBite database to storage/app/backups';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $stamp = now()->format('Ymd-His');

        try {
            $file = match ($config['driver']) {
                'sqlite' => $this->sqlite($dir, $stamp),
                'mysql', 'mariadb' => $this->dump($dir, $stamp, 'sql', [
                    'mysqldump', '--single-transaction', '--quick', '--routines',
                    '-h', $config['host'], '-P', (string) $config['port'], '-u', $config['username'], $config['database'],
                ], ['MYSQL_PWD' => (string) $config['password']]),
                'pgsql' => $this->dump($dir, $stamp, 'sql', [
                    'pg_dump', '--no-owner', '-h', $config['host'], '-p', (string) $config['port'], '-U', $config['username'], $config['database'],
                ], ['PGPASSWORD' => (string) $config['password']]),
                default => throw new \RuntimeException("Unsupported driver {$config['driver']}"),
            };
        } catch (\Throwable $e) {
            report($e);
            $this->error('Backup FAILED: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->prune($dir, (int) $this->option('keep'));
        $this->info('✓ Backup written: '.$file.' ('.number_format(filesize($file) / 1024, 1).' KB)');

        return self::SUCCESS;
    }

    private function sqlite(string $dir, string $stamp): string
    {
        $raw = "{$dir}/bizbite-{$stamp}.sqlite";
        DB::statement('VACUUM INTO ?', [$raw]);

        return $this->gzip($raw);
    }

    /** @param  array<int, string>  $command */
    private function dump(string $dir, string $stamp, string $ext, array $command, array $env): string
    {
        $raw = "{$dir}/bizbite-{$stamp}.{$ext}";
        $process = new Process($command, null, $env, null, 600);
        $handle = fopen($raw, 'wb');
        $process->run(function ($type, $buffer) use ($handle): void {
            if ($type === Process::OUT) {
                fwrite($handle, $buffer);
            }
        });
        fclose($handle);

        if (! $process->isSuccessful()) {
            @unlink($raw);
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'dump command failed');
        }

        return $this->gzip($raw);
    }

    private function gzip(string $path): string
    {
        $gz = $path.'.gz';
        $in = fopen($path, 'rb');
        $out = gzopen($gz, 'wb6');
        while (! feof($in)) {
            gzwrite($out, fread($in, 1 << 20));
        }
        fclose($in);
        gzclose($out);
        unlink($path);

        return $gz;
    }

    private function prune(string $dir, int $keepDays): void
    {
        $cutoff = now()->subDays(max($keepDays, 1))->getTimestamp();
        foreach (glob($dir.'/bizbite-*.gz') ?: [] as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}
