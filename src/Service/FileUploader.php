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
        $suffix = bin2hex(random_bytes(3)); // 6 символов

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


// Этот код можно будет вернуть если мне не понадобиться выше что бы при удалении продукта удалялись фотографии из папки со всеми фотографиями
//<?php
//
//namespace App\Service;
//
//use Symfony\Component\HttpFoundation\File\Exception\FileException;
//use Symfony\Component\HttpFoundation\File\UploadedFile;
//
//class FileUploader
//{
//    private string $targetDirectory;
//
//    public function __construct(string $targetDirectory)
//    {
//        $this->targetDirectory = $targetDirectory;
//    }
//
//    public function upload(UploadedFile $file): string
//    {
//        $filename = uniqid().'.'.$file->guessExtension();
//
//        try {
//            $file->move($this->targetDirectory, $filename);
//        } catch (FileException $e) {
//            throw new \RuntimeException('Erreur lors du téléchargement du fichier.');
//        }
//
//        return $filename;
//    }
//}
