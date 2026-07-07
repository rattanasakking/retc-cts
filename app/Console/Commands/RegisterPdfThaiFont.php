<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RegisterPdfThaiFont extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:register-pdf-thai-font {--source= : Directory containing tahoma.ttf and tahomabd.ttf}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register a Thai-capable font ("thaisans") with dompdf, so PDF exports render Thai text correctly';

    /**
     * Execute the console command.
     *
     * Dompdf's bundled DejaVu Sans does not render Thai glyphs. This copies
     * a Thai-capable TrueType font into the project (dompdf's local-file
     * protocol rule requires the source to be inside the app's chroot) and
     * registers it as the "thaisans" font family used by the PDF export view.
     *
     * NOTE: Tahoma ships with Windows and is not freely redistributable —
     * this command reads it from the local OS font directory rather than
     * bundling it in the repo. On non-Windows hosts, pass --source pointing
     * at a properly licensed Thai font instead (e.g. Google's Noto Sans Thai
     * or TH Sarabun New), with files named tahoma.ttf / tahomabd.ttf, or
     * adjust this command to match your font's filenames.
     */
    public function handle(): int
    {
        $source = $this->option('source') ?: 'C:\\Windows\\Fonts';
        $regular = $source.DIRECTORY_SEPARATOR.'tahoma.ttf';
        $bold = $source.DIRECTORY_SEPARATOR.'tahomabd.ttf';

        if (! File::exists($regular)) {
            $this->error("Font file not found: {$regular}");
            $this->line('Pass --source=/path/to/fonts pointing at a directory with tahoma.ttf (+ tahomabd.ttf for bold).');

            return self::FAILURE;
        }

        $destinationDir = storage_path('app/private/font-sources');
        File::ensureDirectoryExists($destinationDir);

        File::copy($regular, $destinationDir.'/tahoma.ttf');
        if (File::exists($bold)) {
            File::copy($bold, $destinationDir.'/tahomabd.ttf');
        }

        $fontMetrics = app('dompdf.wrapper')->getDomPDF()->getFontMetrics();

        $ok = $fontMetrics->registerFont(
            ['family' => 'thaisans', 'weight' => 'normal', 'style' => 'normal'],
            'file://'.$destinationDir.'/tahoma.ttf'
        );

        if (File::exists($destinationDir.'/tahomabd.ttf')) {
            $fontMetrics->registerFont(
                ['family' => 'thaisans', 'weight' => 'bold', 'style' => 'normal'],
                'file://'.$destinationDir.'/tahomabd.ttf'
            );
        }

        if (! $ok) {
            $this->error('Font registration failed.');

            return self::FAILURE;
        }

        $this->info('Registered "thaisans" font family for PDF exports.');

        return self::SUCCESS;
    }
}
