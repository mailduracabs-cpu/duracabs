<x-filament-panels::page>
    @php
        $money = static fn ($v) => '₹' . intdiv((int) $v, 100) . '.' . str_pad((string) ((int) $v % 100), 2, '0', STR_PAD_LEFT);
        $text = static fn ($v) => filled($v) ? (string) $v : 'Not specified';
    @endphp
    <style>
        .pm-board{display:grid;gap:18px}.pm-board>section{padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#fff}
        .pm-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:8px;margin:12px 0 18px}.pm-stat{border:1px solid #e5e7eb;border-radius:12px;padding:12px;text-align:left;background:#fafafa}.pm-stat strong{display:block;font-size:23px;line-height:1.3}.pm-stat.active{border-color:#d97706;background:#fff7ed}.pm-stat span{font-size:12px;color:#6b7280}
        .pm-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));gap:12px}.pm-filters label{display:grid;gap:4px;font-size:12px;font-weight:600}.pm-filters input,.pm-filters select{width:100%;padding:9px 10px;border:1px solid #d1d5db;border-radius:9px;background:transparent;font-size:14px;font-weight:400}
        .pm-table-wrap{overflow:auto;border:1px solid #e5e7eb;border-radius:12px;margin-top:16px}.pm-table{width:100%;min-width:850px;border-collapse:collapse;text-align:left;font-size:13px}.pm-table th{padding:11px;background:#f9fafb;color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}.pm-table td{padding:12px 11px;border-top:1px solid #e5e7eb;vertical-align:top}.pm-table tbody tr:hover{background:#fffbeb}.pm-table small{display:block;color:#6b7280;font-size:11px;margin-top:3px}.pm-vendor{font-weight:600;text-decoration:underline;text-decoration-style:dotted;text-underline-offset:3px}.pm-tag{display:inline-block;padding:2px 7px;border-radius:6px;background:#f3f4f6;font-size:11px;margin:2px 2px 2px 0}.pm-alert{background:#fff1f2;color:#be123c}.pm-hold{background:#ffedd5;color:#9a3412}.pm-meta{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-top:14px;font-size:12px;color:#6b7280}.pm-vendor-choices{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0}.pm-focus-header{display:flex;justify-content:space-between;gap:12px;align-items:center;padding-bottom:10px}
        .dark .pm-board>section{background:#111827;border-color:#374151}.dark .pm-stat{background:#1f2937;border-color:#374151}.dark .pm-stat.active{background:#422006;border-color:#f59e0b}.dark .pm-table th{background:#1f2937}.dark .pm-table td,.dark .pm-table-wrap{border-color:#374151}.dark .pm-table tbody tr:hover{background:#1f2937}.dark .pm-tag{background:#374151}.dark .pm-alert{background:#4c0519;color:#fecdd3}.dark .pm-hold{background:#431407;color:#fed7aa}.dark .pm-filters input,.dark .pm-filters select{background:#111827;border-color:#4b5563}
    </style>
    <div class="pm-board" x-data="{}" x-on:marketplace-view-changed.window="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
        @if (!$post)
        <section @if($autoRefresh) wire:poll.20s.visible @endif>
            <div class="pm-focus-header"><div><h2 class="text-lg font-semibold">Marketplace work queue</h2><p class="text-sm text-gray-500">Separate vendors, upcoming trips and payment disputes without opening every booking.</p></div><label class="text-sm"><input type="checkbox" wire:model.live="autoRefresh" /> Live list · 20s</label></div>
            <p class="text-xs text-gray-500">Counts follow the current search, vendor, type, status and date filters. Choose a queue below.</p>
            <div class="pm-stats">
                @foreach (['all' => 'All posts', 'open' => 'Open', 'pending' => 'Payment pending', 'hold' => 'Admin hold', 'review' => 'Refund review', 'payout' => 'Payout eligible', 'chats' => 'With chats'] as $queue => $label)
                    <button type="button" wire:click="setQueue('{{ $queue }}')" class="pm-stat {{ $paymentFilter === $queue ? 'active' : '' }}"><strong>{{ $counts[$queue] }}</strong><span>{{ $label }}</span></button>
                @endforeach
            </div>
            <div class="pm-filters">
                <label>Booking / route / vendor search<input wire:model.live.debounce.400ms="search" maxlength="100" placeholder="Booking ID, city, company or mobile" /></label>
                <label>Find a posting vendor<input wire:model.live.debounce.400ms="vendorSearch" maxlength="100" placeholder="Vendor name, mobile or ID" /></label>
                <label>Post type<select wire:model.live="kindFilter"><option value="all">All types</option><option value="booking">Bookings</option><option value="free_vehicle">Free vehicles</option></select></label>
                <label>Listing status<select wire:model.live="statusFilter"><option value="all">All statuses</option><option value="open">Open</option><option value="payment_pending">Payment in progress</option><option value="assigned">Assigned</option><option value="cancelled">Cancelled</option><option value="completed">Completed</option></select></label>
                <label>Date applies to<select wire:model.live="dateBasis"><option value="pickup">Pickup / travel date</option><option value="posted">Date posted</option></select></label>
                <label>From date<input type="date" wire:model.live="dateFrom" /></label>
                <label>To date<input type="date" wire:model.live="dateTo" /></label>
                <label>Sort<select wire:model.live="sortBy"><option value="latest">Latest posts first</option><option value="pickup">Pickup date ascending</option><option value="oldest">Oldest posts first</option><option value="commission">Highest commission first</option></select></label>
            </div>
            @if ($filterError)<p class="mt-3 text-sm text-danger-600" role="alert">{{ $filterError }}</p>@endif
            @if ($vendorSearch !== '')
                <div class="pm-vendor-choices">@forelse ($vendorChoices as $choice)<x-filament::button size="sm" color="gray" wire:click="filterVendor({{ $choice->id }})">{{ $choice->company_name }} · #{{ $choice->id }} · {{ $choice->mobile }}</x-filament::button>@empty<p class="text-sm text-gray-500">No posting vendor matches that search.</p>@endforelse</div>
            @endif
            <div class="pm-meta">
                <div>@if ($selectedVendor)<span class="pm-tag">Vendor: {{ $selectedVendor->company_name }} · #{{ $selectedVendor->id }} · {{ $selectedVendor->mobile }}</span><button type="button" wire:click="clearVendor" class="underline">Clear vendor</button>@else All posting vendors @endif · {{ $posts->total() }} matching posts</div>
                <div><label>Rows <select wire:model.live="perPage" class="rounded-lg border-gray-300"><option value="15">15</option><option value="25">25</option><option value="50">50</option></select></label> <button type="button" wire:click="clearFilters" class="ml-3 underline">Reset filters</button></div>
            </div>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Booking / posted</th><th>Route / pickup</th><th>Posting vendor</th><th>Fare / commission</th><th>Activity / payment</th><th>Review</th></tr></thead>
                    <tbody>
                    @forelse ($posts as $item)
                        <tr wire:key="market-post-{{ $item->id }}">
                            <td><strong>#{{ $item->id }}</strong> <span class="pm-tag">{{ $item->kind === 'booking' ? 'Booking' : 'Free vehicle' }}</span><small>v{{ $item->revision }} · {{ $item->created_at }}</small><span class="pm-tag">{{ $item->status }}</span></td>
                            <td><strong>{{ $item->pickup_city }} → {{ $item->drop_city ?: 'Available' }}</strong><small>Pickup {{ $item->pickup_at }}</small>@if($item->return_at)<small>Return {{ $item->return_at }}</small>@endif<small>{{ $item->vehicle_class }} · {{ $item->trip_type ?: 'Free vehicle' }}</small></td>
                            <td><button type="button" class="pm-vendor" wire:click="filterVendor({{ $item->transporter_profile_id }})">{{ $text($item->company_name) }}</button><small>Vendor #{{ $item->transporter_profile_id }}</small><small>{{ $text($item->vendor_mobile) }}</small></td>
                            <td><strong>{{ $item->pricing_mode === 'quote' && $item->status === 'open' ? 'Quote Best Price' : ($item->asking_amount !== null ? '₹' . $item->asking_amount : 'Quote') }}</strong><small>{{ $item->pricing_mode === 'quote' ? 'Quote Best Price' : 'Booking amount' }}</small><small><strong>{{ $item->pricing_mode === 'quote' && $item->status === 'open' ? 'Commission: set on payment request' : 'Commission ₹' . $item->vendor_commission }}</strong></small></td>
                            <td><span class="pm-tag">{{ $item->pending_quote_count }} pending quotes</span><span class="pm-tag">{{ (int) $item->vendor_message_count + (int) $item->admin_message_count }} messages</span><small>{{ $item->payment_status ?: 'No online payment' }}@if($item->payment_payout_status) · payout {{ $item->payment_payout_status }}@endif</small>@if($item->payment_hold)<span class="pm-tag pm-hold">ADMIN HOLD</span>@endif @if($item->payment_refund_status === 'review_required' || $item->decision_refund_status === 'review_required')<span class="pm-tag pm-alert">REFUND REVIEW</span>@elseif($item->payment_refund_status && $item->payment_refund_status !== 'none')<small>Refund {{ $item->payment_refund_status }}</small>@endif</td>
                            <td><x-filament::button size="sm" wire:click="openPost({{ $item->id }})">Open booking</x-filament::button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No posts match these filters. Reset filters to see the complete queue.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pm-meta"><span>Click a vendor name to filter all their posts. Open a booking for its chat, edit history and payment controls.</span><span>Live refresh applies only to this list.</span></div>
            <div class="mt-4">{{ $posts->links() }}</div>
        </section>
        @endif
        @if ($post)
            <div class="pm-focus-header"><x-filament::button color="gray" wire:click="backToList">← Back to filtered list</x-filament::button><span class="text-sm text-gray-500">Booking #{{ $post->id }} · filters and page are preserved</span></div>
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-semibold">Booking #{{ $post->id }} · Version {{ $post->revision }}</h2><x-filament::button color="gray" wire:click="refreshSelected">Refresh booking / ledger</x-filament::button></div>
                <dl class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach (['Posting vendor' => ($owner->company_name ?? 'Unavailable') . ' (#' . $post->transporter_profile_id . ')', 'Vendor mobile' => ($owner->mobile ?? 'Not available'), 'Status' => $post->status, 'Pickup' => $post->pickup_city, 'Drop' => ($post->drop_city ?: 'Not applicable'), 'Pickup date/time' => $post->pickup_at, 'Return date/time' => ($post->return_at ?: 'Not applicable'), 'Trip / vehicle' => ($post->trip_type ?? 'Free vehicle') . ' / ' . $post->vehicle_class, 'Fare' => ($post->pricing_mode === 'quote' && $post->status === 'open' ? 'To be agreed in chat' : ($post->asking_amount !== null ? '₹' . $post->asking_amount : 'Quote')), 'Commission' => ($post->pricing_mode === 'quote' && $post->status === 'open' ? 'Set on payment request' : '₹' . $post->vendor_commission), 'Pricing' => $post->pricing_mode . ($post->negotiable ? ' · negotiable' : ' · not negotiable'), 'Online advance' => ($post->secure_booking ? 'Required after selection' : 'Not collected online')] as $label => $value)
                        <div><dt class="text-sm text-gray-500">{{ $label }}</dt><dd class="font-medium">{{ $value }}</dd></div>
                    @endforeach
                </dl>
                <p class="mt-4"><strong>Requirements:</strong> {{ implode(', ', json_decode($post->inclusions ?: '[]', true) ?: []) ?: 'None specified' }}</p>
                <p class="mt-2 whitespace-pre-wrap"><strong>Trip notes:</strong> {{ $text($post->notes) }}</p>
                <h3 class="mt-5 font-semibold">Vendor quotes</h3>
                <div class="overflow-x-auto"><table class="mt-2 w-full text-left text-sm"><thead><tr><th class="p-2">Vendor</th><th class="p-2">Fare</th><th class="p-2">Status / Version</th><th class="p-2">Notes</th></tr></thead><tbody>
                    @forelse ($quotes as $quote)<tr class="border-t border-gray-200"><td class="p-2">{{ $quote->company_name }} (#{{ $quote->transporter_profile_id }})</td><td class="p-2">₹{{ $quote->amount }}</td><td class="p-2">{{ $quote->status }} / v{{ $quote->post_revision }}</td><td class="p-2">{{ $text($quote->notes) }}</td></tr>@empty<tr><td colspan="4" class="p-3">No quotes.</td></tr>@endforelse
                </tbody></table></div>
            </section>
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900">
                <h2 class="text-lg font-semibold">Vendor conversations / admin mediation</h2>
                <p class="text-sm text-gray-500">Choose the receiving/interested vendor. Admin replies are labeled and visible to both parties in that conversation.</p>
                <div class="my-4 flex flex-wrap gap-2">@forelse ($threads as $peer)<x-filament::button color="{{ $peerVendorId === (int) $peer->id ? 'primary' : 'gray' }}" wire:click="selectPeer({{ $peer->id }})">{{ $peer->company_name }} · {{ $peer->mobile }}</x-filament::button>@empty<span>No participating vendors yet.</span>@endforelse</div>
                @if ($peerVendorId)
                    <div class="max-h-96 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4">
                        @forelse ($messages as $message)<div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"><div class="text-xs font-semibold">{{ $message->author }} · {{ $message->created_at }}</div><p class="mt-1 whitespace-pre-wrap break-words">{{ $message->message }}</p>@if (!empty($message->fleet_details))<img src="{{ $message->fleet_details['photo_url'] }}" alt="Shared taxi photo" loading="lazy" referrerpolicy="no-referrer" class="mt-2 max-h-64 max-w-full rounded-lg object-contain" />@endif</div>@empty<p>No messages in this conversation.</p>@endforelse
                    </div>
                    <div class="my-3"><x-filament::button size="sm" color="gray" wire:click="olderMessages">Load older messages (currently up to {{ $messageLimit }} per source)</x-filament::button></div>
                    <textarea wire:model="replyText" maxlength="2000" rows="3" placeholder="Admin reply to both vendors…" class="w-full rounded-lg border-gray-300 dark:bg-gray-800"></textarea>
                    <x-filament::button class="mt-2" wire:click="sendReply" wire:loading.attr="disabled">Send admin reply</x-filament::button>
                @endif
            </section>
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900">
                <h2 class="text-lg font-semibold">Commission hold / split / penalty</h2>
                @if ($assignedFleet)<div class="my-3 rounded-lg border p-3"><p><strong>Saved driver / taxi for this assignment attempt</strong></p><p>Driver Name: {{ $assignedFleet['driver_name'] }} · Taxi Name: {{ $assignedFleet['taxi_name'] }} · Taxi Number: {{ $assignedFleet['taxi_number'] }}</p><img src="{{ $assignedFleet['photo_url'] }}" alt="Taxi photo" loading="lazy" referrerpolicy="no-referrer" class="mt-2 max-h-64 max-w-full rounded-lg object-contain" /></div>@endif
                <div class="my-3 flex flex-wrap gap-2">@foreach ($paymentAttempts as $attempt)<x-filament::button size="sm" color="{{ $payment && $payment->id === $attempt->id ? 'primary' : 'gray' }}" wire:click="openPost({{ $post->id }}, {{ $attempt->id }})">Attempt #{{ $attempt->id }} · vendor #{{ $attempt->payer_vendor_id }} · {{ $attempt->status }} / {{ $attempt->refund_status }}</x-filament::button>@endforeach</div>
                @if (!$payment)
                    <p class="mt-2">This post has no secure-commission ledger. No online funds are available to hold or split.</p>
                @else
                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <p><strong>Payment:</strong> #{{ $payment->id }} · {{ $payment->status }}</p><p><strong>Agreed booking fare:</strong> {{ $payment->booking_amount_paise !== null ? $money($payment->booking_amount_paise) : 'Legacy: see quote' }}</p><p><strong>Commission:</strong> {{ $money($payment->commission_paise) }}</p><p><strong>Admin hold:</strong> {{ $payment->admin_hold ? 'ON' : 'OFF' }}</p>
                        <p><strong>Payer / receiving vendor:</strong> #{{ $payment->payer_vendor_id }}</p><p><strong>Payee / posting vendor:</strong> #{{ $payment->payee_vendor_id }}</p><p><strong>Ledger payout:</strong> {{ $payment->payout_status }}</p>
                        <p><strong>Original refund:</strong> {{ $payment->refund_status }}</p><p><strong>Admin revision:</strong> {{ $payment->admin_revision }}</p><p><strong>Paid at:</strong> {{ $payment->paid_at ?: 'Not captured' }}</p><p><strong>Reservation:</strong> {{ $payment->reservation_state }} · {{ $payment->reserved_until ?: '—' }}</p>
                    </div>
                    @if ($payment->booking_snapshot)
                        @php($agreed = json_decode($payment->booking_snapshot, true) ?: [])
                        <details class="my-3"><summary>Agreed booking details saved with this payment request</summary><div class="mt-2 space-y-2">
                            <p>{{ $agreed['pickup_city'] ?? '—' }} → {{ $agreed['drop_city'] ?? '—' }} · {{ $agreed['pickup_at'] ?? '—' }} · {{ $agreed['vehicle_class'] ?? '—' }}</p>
                            <p>Return: {{ $agreed['return_at'] ?? 'Not applicable' }} · Trip: {{ $agreed['trip_type'] ?? '—' }}</p>
                            <p class="whitespace-pre-wrap">Notes: {{ $agreed['notes'] ?? 'None' }}</p>
                        </div></details>
                    @endif
                    @if ($plan)
                        <div class="mt-4 rounded-lg border border-amber-300 p-4">
                            <h3 class="font-semibold">Current decision #{{ $plan->id }} — {{ $plan->status }}</h3>
                            <p>Final receiver refund: <strong>{{ $money($plan->payer_refund_paise) }}</strong> · Final poster payout: <strong>{{ $money($plan->payee_payout_paise) }}</strong> · Platform fee: <strong>{{ $money($plan->platform_fee_paise) }}</strong></p>
                            <p>Penalty: {{ $money($plan->penalty_paise) }} against {{ $plan->penalty_against }} · Refund: {{ $plan->refund_status }} · Payout: {{ $plan->payout_status }}</p>
                            <p class="whitespace-pre-wrap">Reason: {{ $plan->reason }}</p>
                            <p>Gateway refund: {{ $plan->razorpay_refund_id ?: 'Not issued' }} · Completed bank reference: {{ $plan->payout_reference ?: 'Not recorded' }}</p>
                        </div>
                    @endif
                    @if ($canFinance)
                        <div class="my-4 rounded-lg border p-4">
                            <h3 class="font-semibold">Commission payout bank account</h3>
                            @if ($payoutBank)
                                <p>Plan bank version {{ $payoutBank['revision'] }} · {{ $payoutBank['bank_name'] }} · {{ $payoutBank['account_holder_name'] }}</p>
                                <p>Account: {{ $payoutBank['account_number'] ?? $payoutBank['masked_account_number'] }} · IFSC: {{ $payoutBank['ifsc'] }}</p>
                                @if (!$showPayoutBank)<x-filament::button size="sm" color="gray" wire:click="revealPayoutBank">Reveal account for payout</x-filament::button>@endif
                                @if ($currentBank && $currentBank['id'] !== $payoutBank['id'])<p class="mt-2 text-amber-600">Vendor has changed bank details. This existing plan keeps the account shown above; do not substitute the latest account.</p>@endif
                            @elseif ($currentBank)
                                <p>Current saved account: {{ $currentBank['bank_name'] }} · {{ $currentBank['masked_account_number'] }} · IFSC {{ $currentBank['ifsc'] }} (version {{ $currentBank['revision'] }})</p>
                                <p>A new payout plan pins this account version.</p>
                            @else
                                <p>Posting vendor must add account details under Profile → Bank Account before creating a payout plan.</p>
                            @endif
                            <p class="mt-2 text-sm text-gray-500">Account format is checked; bank ownership/validity has not been verified. Transfers remain external/manual. Razorpay refunds go to the original payment source.</p>
                        </div>
                        <div class="mt-4 space-y-4">
                            <label class="block text-sm font-medium">Reason for the next action (required)<textarea wire:model="financeReason" maxlength="1000" rows="2" class="mt-1 w-full rounded-lg border-gray-300 dark:bg-gray-800"></textarea></label>
                            <div class="flex flex-wrap gap-3">
                                <x-filament::button color="gray" wire:click="reconcileCapture" wire:loading.attr="disabled" wire:confirm="Verify the recorded late capture with Razorpay? A matching capture will remain on admin hold; no refund or payout will be sent.">Reconcile late capture (GET only)</x-filament::button>
                                <x-filament::button color="warning" wire:click="hold" wire:loading.attr="disabled" wire:confirm="Hold this commission? New checkout/settlement/refund instructions will be blocked. An already submitted gateway request can still finish.">Hold payment</x-filament::button>
                                <x-filament::button color="success" wire:click="release" wire:loading.attr="disabled" wire:confirm="Release this hold? A planned allocation will become immutable and approved. No bank transfer is performed.">Release hold / approve plan</x-filament::button>
                            </div>
                            <p class="text-sm text-gray-500">Enter the BASE split before penalty. Receiver refund + poster payout + platform fee must equal the captured commission. Penalty shifts value between the two vendors; it cannot charge extra money or exceed the penalized party's allocation.</p>
                            <div class="grid gap-4 md:grid-cols-3">
                                <label class="text-sm">Receiver refund ₹<input wire:model="refundAmount" inputmode="decimal" class="mt-1 w-full rounded-lg border-gray-300 dark:bg-gray-800" /></label>
                                <label class="text-sm">Poster payout ₹<input wire:model="payoutAmount" inputmode="decimal" class="mt-1 w-full rounded-lg border-gray-300 dark:bg-gray-800" /></label>
                                <label class="text-sm">Platform fee ₹<input wire:model="platformFee" inputmode="decimal" class="mt-1 w-full rounded-lg border-gray-300 dark:bg-gray-800" /></label>
                                <label class="text-sm">Penalty against<select wire:model="penaltyAgainst" class="mt-1 w-full rounded-lg border-gray-300 dark:bg-gray-800"><option value="none">None</option><option value="payer">Receiving vendor / payer</option><option value="payee">Posting vendor / payee</option></select></label>
                                <label class="text-sm">Penalty ₹<input wire:model="penaltyAmount" inputmode="decimal" class="mt-1 w-full rounded-lg border-gray-300 dark:bg-gray-800" /></label>
                            </div>
                            <x-filament::button wire:click="savePlan" wire:loading.attr="disabled" wire:confirm="Save this allocation and penalty? Commission will remain held until you approve/release the plan.">Save split / penalty plan</x-filament::button>
                            <hr class="border-gray-200" />
                            <p class="text-sm text-gray-500">After approving/releasing a plan, refund the receiver through Razorpay. A timeout requires GET-only reconciliation of the gateway refund ID; never issue another refund blindly.</p>
                            <x-filament::button color="danger" wire:click="issueRefund" wire:loading.attr="disabled" wire:confirm="Issue the exact receiver refund in the approved plan through Razorpay? This submits a real gateway refund request.">Issue approved receiver refund</x-filament::button>
                            <div class="flex flex-wrap items-end gap-3"><label class="text-sm">Gateway refund ID<input wire:model="gatewayRefundId" maxlength="100" placeholder="rfnd_…" class="mt-1 block rounded-lg border-gray-300 dark:bg-gray-800" /></label><x-filament::button color="gray" wire:click="reconcileRefund" wire:loading.attr="disabled">Reconcile refund (GET only)</x-filament::button></div>
                            <hr class="border-gray-200" />
                            @if ($plan && !$plan->payout_bank_account_id && (int) $plan->payee_payout_paise > 0 && $plan->payout_status !== 'paid')<x-filament::button color="warning" wire:click="pinLegacyBank" wire:loading.attr="disabled" wire:confirm="Pin the vendor’s current saved bank account to this legacy unpaid plan? Confirm that destination with the vendor first. No transfer is sent.">Pin bank for legacy unpaid plan</x-filament::button>@endif
                            <p class="text-sm text-gray-500">Poster bank transfer is completed outside this screen. This button only records its reference after the required refund has been processed. It does not send a bank transfer.</p>
                            <label class="block text-sm">Completed bank-transfer reference<input wire:model="payoutReference" maxlength="150" class="mt-1 w-full rounded-lg border-gray-300 dark:bg-gray-800" /></label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="bankPayoutCompleted" /> I have already completed the poster's bank payout shown in the approved plan.</label>
                            <x-filament::button color="success" wire:click="recordPayout" wire:loading.attr="disabled" wire:confirm="Record this already completed bank payout? Verify the exact amount and bank reference first.">Record completed bank payout</x-filament::button>
                        </div>
                    @else
                        <p class="mt-4 text-sm">Your Moderator role can review and mediate chat. Financial actions require an active Admin role.</p>
                    @endif
                @endif
            </section>
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900">
                <h2 class="text-lg font-semibold">Admin action history</h2>
                <div class="mt-3 space-y-3">@forelse ($audits as $audit)<details class="rounded-lg border border-gray-200 p-3"><summary>{{ $audit->created_at }} · {{ $audit->actor_name }} · {{ $audit->action }}</summary><p class="mt-2 whitespace-pre-wrap">{{ $audit->reason }}</p><pre class="mt-2 overflow-auto whitespace-pre-wrap text-xs">{{ json_encode(json_decode($audit->details ?: '{}', true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>@empty<p>No admin actions yet.</p>@endforelse</div>
                <h3 class="mt-5 font-semibold">Booking edit history</h3>
                <div class="mt-3 space-y-3">@forelse ($edits as $edit)<details class="rounded-lg border border-gray-200 p-3"><summary>{{ $edit->created_at }} · Vendor #{{ $edit->actor_vendor_id }} · v{{ $edit->from_revision }} → v{{ $edit->to_revision }}</summary><p class="mt-2 font-medium">Before / After / Prior quotes</p><pre class="overflow-auto whitespace-pre-wrap text-xs">{{ json_encode(['before' => json_decode($edit->before_data, true), 'after' => json_decode($edit->after_data, true), 'prior_quotes' => json_decode($edit->previous_quotes, true)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>@empty<p>No booking edits.</p>@endforelse</div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
