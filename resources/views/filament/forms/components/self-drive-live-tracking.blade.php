@php
    /** @var \App\Models\SelfDriveBooking|null $record */
    $record = $getRecord();

    $latest = $record
        ? \App\Models\SelfDriveBooking::query()->find($record->getKey())
        : null;

    $lat = $latest?->customer_live_lat;
    $lng = $latest?->customer_live_lng;
    $sharing = (bool) ($latest?->location_sharing_enabled ?? false);
    $updatedAt = $latest?->customer_live_location_updated_at
        ? \Carbon\Carbon::parse($latest->customer_live_location_updated_at)
        : null;

    $ageSeconds = $updatedAt ? $updatedAt->diffInSeconds(now()) : null;
    $fresh = $sharing && $lat !== null && $lng !== null
        && $ageSeconds !== null && $ageSeconds <= 120;

    $statusText = ! $sharing
        ? 'OFFLINE'
        : ($fresh ? 'LIVE' : 'STALE');

    $statusColor = ! $sharing
        ? '#64748b'
        : ($fresh ? '#16a34a' : '#f59e0b');

    $mapsUrl = ($lat !== null && $lng !== null)
        ? 'https://www.google.com/maps/search/?api=1&query='
            . urlencode($lat . ',' . $lng)
        : null;

    $embedUrl = ($lat !== null && $lng !== null)
        ? 'https://maps.google.com/maps?q='
            . urlencode($lat . ',' . $lng)
            . '&z=16&output=embed'
        : null;
@endphp

<div wire:poll.10s
     style="border:1px solid #e2e8f0;border-radius:18px;padding:16px;background:#fff;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;">
        <div>
            <div style="font-size:17px;font-weight:800;color:#0f172a;">Customer Live Tracking</div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">
                Auto refresh every 10 seconds
            </div>
        </div>

        <div style="display:inline-flex;align-items:center;gap:7px;font-weight:800;color:{{ $statusColor }};">
            <span style="width:9px;height:9px;border-radius:999px;background:{{ $statusColor }};display:inline-block;"></span>
            {{ $statusText }}
        </div>
    </div>

    @if ($embedUrl)
        <div style="overflow:hidden;border-radius:16px;border:1px solid #e2e8f0;background:#f8fafc;">
            <iframe
                src="{{ $embedUrl }}"
                width="100%"
                height="390"
                style="border:0;display:block;"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen>
            </iframe>
        </div>
    @else
        <div style="height:260px;border-radius:16px;border:1px dashed #cbd5e1;background:#f8fafc;
                    display:flex;align-items:center;justify-content:center;text-align:center;padding:24px;color:#64748b;">
            <div>
                <div style="font-size:38px;margin-bottom:8px;">📍</div>
                <div style="font-weight:800;color:#334155;">Waiting for live location</div>
                <div style="font-size:13px;margin-top:5px;">
                    Location will appear after the running trip starts sending GPS updates.
                </div>
            </div>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px;margin-top:14px;">
        <div style="padding:12px;border-radius:12px;background:#f8fafc;">
            <div style="font-size:12px;color:#64748b;">Current Coordinates</div>
            <div style="font-weight:800;color:#0f172a;margin-top:3px;">
                @if ($lat !== null && $lng !== null)
                    {{ number_format((float) $lat, 7, '.', '') }},
                    {{ number_format((float) $lng, 7, '.', '') }}
                @else
                    Not available
                @endif
            </div>
        </div>

        <div style="padding:12px;border-radius:12px;background:#f8fafc;">
            <div style="font-size:12px;color:#64748b;">Last Updated</div>
            <div style="font-weight:800;color:#0f172a;margin-top:3px;">
                {{ $updatedAt ? $updatedAt->diffForHumans() : 'Never' }}
            </div>
        </div>

        <div style="padding:12px;border-radius:12px;background:#f8fafc;">
            <div style="font-size:12px;color:#64748b;">Location Sharing</div>
            <div style="font-weight:800;color:{{ $sharing ? '#16a34a' : '#64748b' }};margin-top:3px;">
                {{ $sharing ? 'Active' : 'Stopped' }}
            </div>
        </div>
    </div>

    @if ($mapsUrl)
        <div style="margin-top:14px;">
            <a href="{{ $mapsUrl }}"
               target="_blank"
               rel="noopener noreferrer"
               style="display:inline-flex;align-items:center;justify-content:center;gap:8px;
                      min-height:42px;padding:0 16px;border-radius:10px;background:#2563eb;
                      color:#fff;font-weight:800;text-decoration:none;">
                Open in Google Maps
            </a>
        </div>
    @endif
</div>
