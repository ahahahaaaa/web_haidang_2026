<?php

namespace App\Services\Admin;

use App\Models\SeoOptimizationAsset;
use Illuminate\Database\QueryException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryDeletionService
{
    public function delete(Media $media): bool
    {
        if ($this->isProtected($media)) {
            return false;
        }

        try {
            return (bool) $media->delete();
        } catch (QueryException $exception) {
            if ($this->isForeignKeyReferenceViolation($exception)) {
                return false;
            }

            throw $exception;
        }
    }

    public function isProtected(Media $media): bool
    {
        return SeoOptimizationAsset::query()
            ->where('media_id', $media->getKey())
            ->exists();
    }

    protected function isForeignKeyReferenceViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = strtolower($exception->getMessage());

        return $sqlState === '23503'
            || ($sqlState === '23000' && (
                $driverCode === 1451
                || str_contains($message, 'foreign key constraint')
            ));
    }
}
