<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Backup de la base de datos MongoDB';

    public function handle()
    {
        $this->info('Iniciando backup de la base de datos...');

        try {
            // Crear directorio de backup si no existe
            $backupPath = storage_path('app/backups');
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            // Nombre del archivo de backup
            $filename = 'backup-' . Carbon::now()->format('Y-m-d-H-i-s') . '.gz';
            $filepath = $backupPath . '/' . $filename;

            // Comando para hacer backup de MongoDB
            $command = sprintf(
                'mongodump --uri="%s" --archive="%s" --gzip',
                env('MONGODB_URI'),
                $filepath
            );

            // Ejecutar el comando
            exec($command, $output, $returnVar);

            if ($returnVar === 0) {
                $this->info('Backup completado exitosamente: ' . $filename);
                
                // Registrar en el log
                \Log::info('Backup de base de datos completado', [
                    'filename' => $filename,
                    'size' => filesize($filepath),
                    'created_at' => Carbon::now()
                ]);
            } else {
                throw new \Exception('Error al ejecutar el comando de backup');
            }
        } catch (\Exception $e) {
            $this->error('Error al realizar el backup: ' . $e->getMessage());
            \Log::error('Error en backup de base de datos', [
                'error' => $e->getMessage(),
                'time' => Carbon::now()
            ]);
        }
    }
} 