<?php

namespace App\Service\File;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private string $propertyImagesDirectory;
    private string $userImagesDirectory;
    private SluggerInterface $slugger;

    public function __construct(
        string $propertyImagesDirectory,
        string $userImagesDirectory,
        SluggerInterface $slugger
    ) {
        $this->propertyImagesDirectory = $propertyImagesDirectory;
        $this->userImagesDirectory = $userImagesDirectory;
        $this->slugger = $slugger;
    }

    /**
     * Upload a property image
     */
    public function uploadPropertyImage(UploadedFile $file, int $propertyId): string
    {
        return $this->upload($file, $this->propertyImagesDirectory, 'property_' . $propertyId);
    }

    /**
     * Upload a user profile image
     */
    public function uploadUserImage(UploadedFile $file, int $userId): string
    {
        return $this->upload($file, $this->userImagesDirectory, 'user_' . $userId);
    }

    /**
     * Upload a verification document
     */
    public function uploadVerificationDocument(UploadedFile $file, int $userId): string
    {
        $directory = $this->userImagesDirectory . '/verification';
        return $this->upload($file, $directory, 'verification_' . $userId);
    }

    /**
     * Generic file upload method
     */
    private function upload(UploadedFile $file, string $directory, string $prefix = ''): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $prefix . '_' . $safeFilename . '_' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($directory, $fileName);
        } catch (FileException $e) {
            throw new FileException('Could not upload file: ' . $e->getMessage());
        }

        return $fileName;
    }

    /**
     * Remove a file
     */
    public function removeFile(string $filepath): bool
    {
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }

    /**
     * Get the URL for a property image
     */
    public function getPropertyImageUrl(string $filename): string
    {
        return '/media/properties/' . $filename;
    }

    /**
     * Get the URL for a user image
     */
    public function getUserImageUrl(string $filename): string
    {
        return '/media/users/' . $filename;
    }

    /**
     * Check if a file exists
     */
    public function fileExists(string $filepath): bool
    {
        return file_exists($filepath) && is_file($filepath);
    }

    /**
     * Get the full path for a property image
     */
    public function getPropertyImagePath(string $filename): string
    {
        return $this->propertyImagesDirectory . '/' . $filename;
    }

    /**
     * Get the full path for a user image
     */
    public function getUserImagePath(string $filename): string
    {
        return $this->userImagesDirectory . '/' . $filename;
    }
}