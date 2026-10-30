@php
    $booking = $getRecord();
    $groups = [
        'Start / Pickup Photos' => $booking?->pickup_images ?? [],
        'End / Return Photos' => $booking?->drop_images ?? [],
    ];
    $photoUrl = static function ($photo): ?string {
        if (is_array($photo)) {
            $photo = $photo['url'] ?? $photo['path'] ?? $photo['image'] ?? $photo['file'] ?? null;
        }
        if (! is_string($photo) || trim($photo) === '') return null;
        $photo = trim($photo);
        if (preg_match('~^https://~i', $photo)) return $photo;
        if (preg_match('~^http://~i', $photo)) return null;
        if (str_starts_with($photo, '//') || str_contains($photo, '..')) return null;
        if (str_starts_with($photo, '/storage/')) return url($photo);
        if (str_starts_with($photo, 'storage/')) return url('/'.$photo);
        return \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($photo, '/'));
    };
@endphp
<div class="space-y-6">
    @foreach ($groups as $heading => $photos)
        @php
            $photos = is_array($photos) ? $photos : [];
            // Accept both indexed arrays and keyed photo slots, without altering saved data.
            $items = [];
            foreach ($photos as $key => $photo) {
                $url = $photoUrl($photo);
                if ($url !== null) $items[] = ['url' => $url, 'label' => is_string($key) ? ucfirst($key) : 'Photo '.(count($items)+1)];
            }
        @endphp
        <section>
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white mb-3">{{ $heading }} ({{ count($items) }})</h3>
            @if (count($items))
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($items as $item)
                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" title="Open full photo">
                            <img src="{{ $item['url'] }}" alt="{{ $heading }} - {{ $item['label'] }}" loading="lazy" class="h-36 w-full object-contain bg-gray-50 dark:bg-gray-900" />
                            <span class="block px-2 py-1 text-xs text-gray-600 dark:text-gray-300">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">No photos uploaded yet.</p>
            @endif
        </section>
    @endforeach
</div>
