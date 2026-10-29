<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{


    public function __construct(
        private string $targetDirectory,
        private SluggerInterface $slugger,
    ) {}

    public function upload(UploadedFile $file, string $reference): string
    {
        $safeRef = $this->slugger->slug($reference)->lower()->toString();
        $ext = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $suffix = bin2hex(random_bytes(3));

        $filename = sprintf('%s-%s.%s', $safeRef, $suffix, $ext);

        try {
            $file->move($this->targetDirectory, $filename);
        } catch (FileException $e) {
            throw new \RuntimeException('Unable to move the uploaded file.', previous: $e);
        }

        return $filename;
    }

    public function remove(string $filename): void
    {
        $path = $this->targetDirectory . DIRECTORY_SEPARATOR . basename($filename);

        if (is_file($path)) {
            unlink($path);
        }
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
