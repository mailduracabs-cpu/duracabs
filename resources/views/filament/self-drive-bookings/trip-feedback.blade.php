<div class="space-y-4">
    @forelse ($reviews as $review)
        @php
            $direction = match (true) {
                $customerId > 0 && (int) $review->reviewer_id === $customerId => 'Customer review',
                $customerId > 0 && (int) $review->subject_id === $customerId => 'Review of customer',
                default => 'Trip review',
            };
        @endphp
        <div>
            <p class="text-sm font-semibold">
                {{ $direction }} · {{ $review->rating }} / 5
                @if ((int) $review->rating === 1)
                    · One-star report
                @endif
            </p>
            @if (filled($review->comment))
                <p class="whitespace-pre-wrap break-words text-sm">{{ $review->comment }}</p>
            @endif
        </div>
    @empty
        <p class="text-sm">No feedback has been submitted for this booking.</p>
    @endforelse
</div>
