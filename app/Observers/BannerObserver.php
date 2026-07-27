<?php

namespace App\Observers;

use App\Models\Banners;
use App\Services\Media\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BannerObserver
{
    public function __construct(
        protected MediaService $mediaService
    ) {
    }

    /**
     * Handle banner creation.
     */
    public function created(Banners $banner): void
    {
        //
    }

    /**
     * Handle banner update.
     */
    public function updated(Banners $banner): void
    {
        //
    }

    /**
     * Handle banner deletion.
     */
    public function deleted(Banners $banner): void
    {
        if (
            filled($banner->image) &&
            Storage::disk('public')->exists($banner->image)
        ) {
            Storage::disk('public')->delete($banner->image);
        }
    }

    public function restored(Banners $banner): void
    {
        //
    }

    public function forceDeleted(Banners $banner): void
    {
        //
    }
}