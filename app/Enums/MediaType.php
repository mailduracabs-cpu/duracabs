<?php

namespace App\Enums;

enum MediaType: string
{
    case Banner = 'banner';
    case Vehicle = 'vehicle';
    case Tour = 'tour';
    case Destination = 'destination';
    case Offer = 'offer';
    case Service = 'service';
    case Profile = 'profile';
    case Review = 'review';
    case Icon = 'icon';
    case Document = 'document';
    case Other = 'other';

    /**
     * Human-readable media type label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Banner => 'Banner',
            self::Vehicle => 'Vehicle',
            self::Tour => 'Tour',
            self::Destination => 'Destination',
            self::Offer => 'Offer',
            self::Service => 'Service',
            self::Profile => 'Profile',
            self::Review => 'Review',
            self::Icon => 'Icon',
            self::Document => 'Document',
            self::Other => 'Other',
        };
    }

    /**
     * Base storage directory for this media type.
     */
    public function directory(): string
    {
        return match ($this) {
            self::Banner => 'banners',
            self::Vehicle => 'vehicles',
            self::Tour => 'tours',
            self::Destination => 'destinations',
            self::Offer => 'offers',
            self::Service => 'services',
            self::Profile => 'profiles',
            self::Review => 'reviews',
            self::Icon => 'icons',
            self::Document => 'documents',
            self::Other => 'other',
        };
    }

    /**
     * Determine whether the media type is a document.
     */
    public function isDocument(): bool
    {
        return $this === self::Document;
    }

    /**
     * Images are converted to WebP.
     * Documents retain their original format.
     */
    public function supportsWebp(): bool
    {
        return !$this->isDocument();
    }

    /**
     * WebP quality used for the single optimized image.
     */
    public function quality(): int
    {
        return match ($this) {
            self::Banner => 82,
            self::Vehicle => 84,
            self::Tour => 82,
            self::Destination => 82,
            self::Offer => 82,
            self::Service => 84,
            self::Profile => 85,
            self::Review => 84,
            self::Icon => 90,
            self::Document => 90,
            self::Other => 82,
        };
    }

    /**
     * Maximum dimensions for the single optimized WebP image.
     *
     * The image will never be enlarged beyond its original dimensions.
     *
     * @return array{width:int,height:int}
     */
    public function maxSize(): array
    {
        return match ($this) {
            self::Banner => [
                'width' => 1600,
                'height' => 650,
            ],

            self::Vehicle => [
                'width' => 1400,
                'height' => 1050,
            ],

            self::Tour => [
                'width' => 1400,
                'height' => 1050,
            ],

            self::Destination => [
                'width' => 1400,
                'height' => 1050,
            ],

            self::Offer => [
                'width' => 1200,
                'height' => 675,
            ],

            self::Service => [
                'width' => 1000,
                'height' => 1000,
            ],

            self::Profile => [
                'width' => 1000,
                'height' => 1000,
            ],

            self::Review => [
                'width' => 1000,
                'height' => 1000,
            ],

            self::Icon => [
                'width' => 512,
                'height' => 512,
            ],

            /*
             * Image documents such as RC, insurance and licence scans
             * remain readable while avoiding excessively large files.
             *
             * PDF documents are not resized or converted.
             */
            self::Document => [
                'width' => 2000,
                'height' => 2000,
            ],

            self::Other => [
                'width' => 1400,
                'height' => 1050,
            ],
        };
    }

    /**
     * Determine whether the image should use a fixed crop.
     *
     * Banner and offer images need predictable dimensions.
     * Other images preserve their original aspect ratio.
     */
    public function shouldCrop(): bool
    {
        return match ($this) {
            self::Banner,
            self::Offer,
            self::Profile,
            self::Icon => true,

            default => false,
        };
    }

    /**
     * Allowed values for Filament select fields.
     *
     * @return array<string,string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}