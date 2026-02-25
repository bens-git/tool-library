<?php

namespace App\Console\Commands;

use App\Models\ItemImage;
use Illuminate\Console\Command;

class CompressExistingImages extends Command
{
    protected $signature = 'images:compress {--dry-run : Run without actually modifying files}';

    protected $description = 'Compress existing item images to reduce file size';

    public function handle(): int
    {
        if (!extension_loaded('gd')) {
            $this->error('The GD PHP extension is required for image compression.');
            $this->info('Please install it with: apt-get install php-gd');
            return self::FAILURE;
        }

        $this->info('Starting image compression...');
        
        $dryRun = (bool) $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('Running in dry-run mode - no files will be modified.');
        }

        $images = ItemImage::whereNotNull('path')
            ->where('path', '!=', '')
            ->get();

        $count = $images->count();
        $this->info("Found {$count} images to process.");

        $processed = 0;
        $errors = 0;
        $saved = 0;

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($images as $image) {
            try {
                $fullPath = storage_path('app/public/' . $image->path);
                
                if (!file_exists($fullPath)) {
                    $this->newLine();
                    $this->error("File not found: {$fullPath}");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                $originalSize = filesize($fullPath);
                
                $imageInfo = @getimagesize($fullPath);
                if (!$imageInfo) {
                    $this->newLine();
                    $this->error("Not a valid image: {$fullPath}");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                $source = null;
                switch ($imageInfo['mime']) {
                    case 'image/jpeg':
                        $source = @imagecreatefromjpeg($fullPath);
                        break;
                    case 'image/png':
                        $source = @imagecreatefrompng($fullPath);
                        break;
                    case 'image/gif':
                        $source = @imagecreatefromgif($fullPath);
                        break;
                }

                if (!$source) {
                    $this->newLine();
                    $this->error("Could not load image: {$fullPath}");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                $width = imagesx($source);
                $height = imagesy($source);
                
                $maxDimension = 1200;
                if ($width > $maxDimension || $height > $maxDimension) {
                    if ($width > $height) {
                        $newWidth = $maxDimension;
                        $newHeight = (int) round(($height / $width) * $maxDimension);
                    } else {
                        $newHeight = $maxDimension;
                        $newWidth = (int) round(($width / $height) * $maxDimension);
                    }
                } else {
                    $newWidth = $width;
                    $newHeight = $height;
                }

                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                $white = imagecolorallocate($newImage, 255, 255, 255);
                imagefill($newImage, 0, 0, $white);
                
                imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                if (!$dryRun) {
                    imagejpeg($newImage, $fullPath, 80);
                }
                
                imagedestroy($source);
                imagedestroy($newImage);

                $newSize = $dryRun ? (int) ($originalSize * 0.5) : filesize($fullPath);
                $saved += max(0, $originalSize - $newSize);
                $processed++;

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error processing image {$image->id}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Image compression complete!");
        $this->info("Processed: {$processed}");
        $this->info("Errors: {$errors}");
        $this->info("Space saved: " . number_format($saved / 1024, 2) . " KB");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
