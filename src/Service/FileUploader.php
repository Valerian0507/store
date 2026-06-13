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
        } catch (FileException) {
            throw new \RuntimeException('Erreur lors du téléchargement du fichier.');
        }

        return $filename;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
