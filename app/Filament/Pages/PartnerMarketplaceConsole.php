<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\PartnerMarketplaceAdminService as Admin;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PartnerMarketplaceConsole extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Bookings, Chat & Disputes';
    protected static ?string $navigationGroup = 'Partner Marketplace';
    protected static ?string $slug = 'partner-marketplace';
    protected static string $view = 'filament.pages.partner-marketplace-console';
    protected static ?string $title = 'Partner Marketplace Console';

    public string $search = '';
    public string $vendorSearch = '';
    public ?int $vendorFilterId = null;
    public string $statusFilter = 'all';
    public string $kindFilter = 'all';
    public string $paymentFilter = 'all';
    public string $dateBasis = 'pickup';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortBy = 'latest';
    public int $perPage = 15;
    public bool $autoRefresh = true;
    public ?int $postId = null;
    public ?int $paymentAttemptId = null;
    public ?int $peerVendorId = null;
    public int $adminRevision = 1;
    public int $messageLimit = 100;
    public string $replyText = '';
    public string $financeReason = '';
    public string $refundAmount = '0.00';
    public string $payoutAmount = '0.00';
    public string $platformFee = '0.00';
    public string $penaltyAgainst = 'none';
    public string $penaltyAmount = '0.00';
    public string $gatewayRefundId = '';
    public string $payoutReference = '';
    public bool $bankPayoutCompleted = false;
    public bool $showPayoutBank = false;

    public static function canAccess(): bool
    {
        $user = Auth::guard('admin')->user();
        return $user instanceof User && $user->canUseAdminLogin();
    }

    public function mount(): void { Admin::actor(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updated(string $property): void
    {
        if (in_array($property, ['statusFilter', 'kindFilter', 'paymentFilter', 'dateBasis', 'dateFrom', 'dateTo', 'sortBy', 'perPage'], true)) $this->resetPage();
    }
    public function filterVendor(int $id): void
    {
        Admin::actor(); abort_unless(DB::table('fleet_transporter_profiles')->where('id', $id)->exists(), 404);
        $this->vendorFilterId = $id; $this->vendorSearch = ''; $this->resetPage();
    }
    public function clearVendor(): void { Admin::actor(); $this->vendorFilterId = null; $this->vendorSearch = ''; $this->resetPage(); }
    public function setQueue(string $queue): void
    {
        Admin::actor(); abort_unless(in_array($queue, ['all', 'open', 'pending', 'hold', 'review', 'payout', 'chats'], true), 422);
        $this->paymentFilter = $queue; $this->resetPage();
    }
    public function clearFilters(): void
    {
        Admin::actor(); $this->search = ''; $this->vendorSearch = ''; $this->vendorFilterId = null;
        $this->statusFilter = 'all'; $this->kindFilter = 'all'; $this->paymentFilter = 'all';
        $this->dateBasis = 'pickup'; $this->dateFrom = ''; $this->dateTo = ''; $this->sortBy = 'latest'; $this->resetPage();
    }
    public function backToList(): void
    {
        Admin::actor(); $this->postId = null; $this->peerVendorId = null; $this->replyText = ''; $this->bankPayoutCompleted = false; $this->showPayoutBank = false;
        $this->dispatch('marketplace-view-changed');
    }

    private static function rupees(int $paise): string { return intdiv($paise, 100) . '.' . str_pad((string) ($paise % 100), 2, '0', STR_PAD_LEFT); }

    private function payment(): object
    {
        abort_unless($this->postId, 422, 'Open a marketplace booking first.');
        $row = $this->selectedPayment($this->postId);
        abort_unless($row, 422, 'This booking has no captured/pending secure commission ledger.');
        return $row;
    }

    private function selectedPayment(int $post): ?object
    {
        $listing = DB::table('partner_market_posts')->find($post);
        $query = DB::table('partner_market_secure_payments')->where('post_id', $post);
        if ($this->paymentAttemptId) return $query->where('id', $this->paymentAttemptId)->first();
        return $query->orderByRaw('CASE WHEN id = ? THEN 0 WHEN id = ? THEN 1 ELSE 2 END', [$listing->assigned_payment_id ?? 0, $listing->active_payment_id ?? 0])->orderByDesc('id')->first();
    }

    public function openPost(int $post, ?int $attempt = null): void
    {
        Admin::actor();
        abort_unless(DB::table('partner_market_posts')->where('id', $post)->exists(), 404);
        if ($this->postId !== $post) $this->dispatch('marketplace-view-changed');
        if ($attempt) abort_unless(DB::table('partner_market_secure_payments')->where('post_id', $post)->where('id', $attempt)->exists(), 404);
        $this->paymentAttemptId = $attempt;
        $this->postId = $post; $this->peerVendorId = null; $this->messageLimit = 100;
        $this->replyText = ''; $this->financeReason = ''; $this->bankPayoutCompleted = false; $this->showPayoutBank = false;
        $row = $this->selectedPayment($post);
        if (!$row) return;
        $this->adminRevision = (int) $row->admin_revision;
        $this->gatewayRefundId = (string) ($row->razorpay_refund_id ?? ''); $this->payoutReference = '';
        $refund = 0; $payout = (int) $row->payout_paise; $fee = (int) $row->service_fee_paise;
        $this->penaltyAgainst = 'none'; $this->penaltyAmount = '0.00';
        if ($row->admin_settlement_id) {
            $plan = DB::table('partner_market_admin_settlements')->find($row->admin_settlement_id);
            if ($plan) {
                $refund = (int) $plan->payer_refund_paise; $payout = (int) $plan->payee_payout_paise; $fee = (int) $plan->platform_fee_paise;
                if ($plan->penalty_against === 'payer') { $refund += (int) $plan->penalty_paise; $payout -= (int) $plan->penalty_paise; }
                if ($plan->penalty_against === 'payee') { $payout += (int) $plan->penalty_paise; $refund -= (int) $plan->penalty_paise; }
                $this->penaltyAgainst = $plan->penalty_against; $this->penaltyAmount = self::rupees((int) $plan->penalty_paise);
                $this->gatewayRefundId = (string) ($plan->razorpay_refund_id ?? '');
            }
        }
        $this->refundAmount = self::rupees($refund); $this->payoutAmount = self::rupees($payout); $this->platformFee = self::rupees($fee);
    }

    public function refreshSelected(): void
    {
        Admin::actor();
        if ($this->postId) { $peer = $this->peerVendorId; $this->openPost($this->postId, $this->paymentAttemptId); $this->peerVendorId = $peer; }
    }

    public function selectPeer(int $peer): void { Admin::actor(); $this->peerVendorId = $peer; $this->messageLimit = 100; }
    public function olderMessages(): void { Admin::actor(); $this->messageLimit = min(2000, $this->messageLimit + 100); }

    private function perform(callable $action, string $success): void
    {
        try {
            $action();
            $this->refreshSelected();
            Notification::make()->title($success)->success()->send();
        } catch (ValidationException $e) {
            Notification::make()->title('Check the request')->body(implode(' ', array_merge(...array_values($e->errors()))))->danger()->send();
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() === 403) throw $e;
            Notification::make()->title('Action not applied')->body($e->getMessage() ?: 'Refresh and check the current booking state.')->danger()->send();
        } catch (\Throwable $e) {
            Log::error('Marketplace admin operation failed', ['post_id' => $this->postId, 'exception' => get_class($e)]);
            Notification::make()->title('Could not confirm the action')->body('Refresh and check the ledger. Do not repeat a payment/refund blindly.')->danger()->send();
        }
    }

    public function hold(): void { $this->perform(fn () => Admin::hold((int) $this->payment()->id, $this->adminRevision, $this->financeReason), 'Commission placed on hold'); }
    public function release(): void { $this->perform(fn () => Admin::release((int) $this->payment()->id, $this->adminRevision, $this->financeReason), 'Hold released / settlement approved'); }
    public function revealPayoutBank(): void
    {
        Admin::actor(true);
        $row = $this->payment();
        $plan = $row->admin_settlement_id ? DB::table('partner_market_admin_settlements')->find($row->admin_settlement_id) : null;
        abort_unless($plan, 422, 'Create a payout plan first.');
        \App\Services\PartnerMarketplaceBankAccounts::revealForSettlement($plan, (int) $row->payee_vendor_id);
        $this->showPayoutBank = true;
    }
    public function pinLegacyBank(): void
    {
        $this->perform(fn () => Admin::pinLegacyBank((int) $this->payment()->id, $this->adminRevision, $this->financeReason), 'Bank account pinned to legacy unpaid plan; no money transferred');
    }
    public function savePlan(): void
    {
        $this->perform(fn () => Admin::plan((int) $this->payment()->id, $this->adminRevision, [
            'refund' => $this->refundAmount, 'payout' => $this->payoutAmount, 'fee' => $this->platformFee,
            'penalty_against' => $this->penaltyAgainst, 'penalty' => $this->penaltyAmount, 'reason' => $this->financeReason,
        ]), 'Allocation planned; payment remains on hold');
    }
    public function issueRefund(): void { $this->perform(fn () => Admin::issueRefund((int) $this->payment()->id, $this->adminRevision, $this->financeReason), 'Refund result recorded; review current gateway status'); }
    public function reconcileCapture(): void { $this->perform(fn () => Admin::reconcileLateCapture((int) $this->payment()->id, $this->adminRevision, $this->financeReason), 'Late capture verified; funds remain held for admin allocation'); }
    public function reconcileRefund(): void { $this->perform(fn () => Admin::reconcile((int) $this->payment()->id, $this->adminRevision, $this->gatewayRefundId, $this->financeReason), 'Gateway refund reconciled'); }
    public function recordPayout(): void
    {
        $this->perform(function (): void {
            Admin::actor(true);
            abort_unless($this->bankPayoutCompleted, 422, 'Confirm that the bank transfer was already completed.');
            Admin::recordPayout((int) $this->payment()->id, $this->adminRevision, $this->payoutReference, $this->financeReason);
        }, 'Completed bank payout recorded');
    }
    public function sendReply(): void
    {
        $this->perform(function (): void {
            abort_unless($this->postId && $this->peerVendorId, 422, 'Choose a conversation first.');
            Admin::reply($this->postId, $this->peerVendorId, $this->replyText);
            $this->replyText = '';
        }, 'Admin reply added for both vendors');
    }

    protected function getViewData(): array
    {
        $actor = Admin::actor();
        $query = DB::table('partner_market_posts as p')
            ->leftJoin('fleet_transporter_profiles as v', 'v.id', '=', 'p.transporter_profile_id')
            ->leftJoin('partner_market_secure_payments as l', function ($join) { $join->on('l.post_id', '=', 'p.id')->whereRaw('l.id = COALESCE(p.assigned_payment_id, p.active_payment_id, (SELECT MAX(lp.id) FROM partner_market_secure_payments lp WHERE lp.post_id = p.id))'); })
            ->leftJoin('partner_market_admin_settlements as d', 'd.id', '=', 'l.admin_settlement_id')
            ->select('p.*', 'v.company_name', 'v.mobile as vendor_mobile', 'l.status as payment_status', 'l.admin_hold as payment_hold',
                'l.refund_status as payment_refund_status', 'l.payout_status as payment_payout_status', 'd.refund_status as decision_refund_status');
        $term = mb_substr(trim($this->search), 0, 100);
        if ($term !== '') $query->where(function ($q) use ($term) {
            $q->where('p.id', 'like', '%' . $term . '%')->orWhere('p.pickup_city', 'like', '%' . $term . '%')->orWhere('p.drop_city', 'like', '%' . $term . '%')
                ->orWhere('v.company_name', 'like', '%' . $term . '%')->orWhere('v.mobile', 'like', '%' . $term . '%');
        });
        if ($this->vendorFilterId) $query->where('p.transporter_profile_id', $this->vendorFilterId);
        if (in_array($this->statusFilter, ['open', 'payment_pending', 'assigned', 'cancelled', 'completed'], true)) $query->where('p.status', $this->statusFilter);
        if (in_array($this->kindFilter, ['booking', 'free_vehicle'], true)) $query->where('p.kind', $this->kindFilter);
        $filterError = null;
        $validDate = static function (string $value): bool {
            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) return false;
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
        };
        if (($this->dateFrom !== '' && !$validDate($this->dateFrom)) || ($this->dateTo !== '' && !$validDate($this->dateTo)) || ($this->dateFrom !== '' && $this->dateTo !== '' && $this->dateFrom > $this->dateTo)) {
            $filterError = 'Enter a valid date range; From must be on or before To.';
            $query->whereRaw('1 = 0');
        } else {
            $column = $this->dateBasis === 'posted' ? 'p.created_at' : 'p.pickup_at';
            if ($this->dateFrom !== '') $query->where($column, '>=', $this->dateFrom . ' 00:00:00');
            if ($this->dateTo !== '') $query->where($column, '<', (new \DateTimeImmutable($this->dateTo))->modify('+1 day')->format('Y-m-d') . ' 00:00:00');
        }
        $withChats = static function ($q) {
            $q->whereExists(function ($m) { $m->selectRaw('1')->from('partner_market_messages as m')->whereColumn('m.post_id', 'p.id'); })
                ->orWhereExists(function ($m) { $m->selectRaw('1')->from('partner_market_admin_messages as m')->whereColumn('m.post_id', 'p.id'); });
        };
        $needsReview = static function ($q) { $q->where('l.refund_status', 'review_required')->orWhere('d.refund_status', 'review_required')->orWhereExists(function ($attempt) { $attempt->selectRaw('1')->from('partner_market_secure_payments as ap')->whereColumn('ap.post_id', 'p.id')->where('ap.refund_status', 'review_required'); }); };
        $counts = [
            'all' => (clone $query)->count(), 'open' => (clone $query)->where('p.status', 'open')->count(),
            'pending' => (clone $query)->where('l.status', 'pending')->count(),
            'hold' => (clone $query)->where('l.admin_hold', true)->count(),
            'review' => (clone $query)->where($needsReview)->count(),
            'payout' => (clone $query)->where('l.payout_status', 'eligible')->where('l.admin_hold', false)->count(),
            'chats' => (clone $query)->where($withChats)->count(),
        ];
        match ($this->paymentFilter) {
            'open' => $query->where('p.status', 'open'), 'pending' => $query->where('l.status', 'pending'),
            'hold' => $query->where('l.admin_hold', true), 'review' => $query->where($needsReview),
            'payout' => $query->where('l.payout_status', 'eligible')->where('l.admin_hold', false),
            'chats' => $query->where($withChats), default => $query,
        };
        $query->selectSub(DB::table('partner_market_quotes as q')->whereColumn('q.post_id', 'p.id')->where('q.status', 'pending')->selectRaw('COUNT(*)'), 'pending_quote_count')
            ->selectSub(DB::table('partner_market_messages as m')->whereColumn('m.post_id', 'p.id')->selectRaw('COUNT(*)'), 'vendor_message_count')
            ->selectSub(DB::table('partner_market_admin_messages as a')->whereColumn('a.post_id', 'p.id')->selectRaw('COUNT(*)'), 'admin_message_count');
        if ($this->sortBy === 'pickup') $query->orderBy('p.pickup_at')->orderByDesc('p.id');
        elseif ($this->sortBy === 'oldest') $query->orderBy('p.id');
        elseif ($this->sortBy === 'commission') $query->orderByDesc('p.vendor_commission')->orderByDesc('p.id');
        else $query->orderByDesc('p.id');
        $posts = $query->paginate(in_array($this->perPage, [15, 25, 50], true) ? $this->perPage : 15);
        $vendorChoices = collect();
        $vendorTerm = mb_substr(trim($this->vendorSearch), 0, 100);
        if ($vendorTerm !== '') {
            $vendorChoices = DB::table('fleet_transporter_profiles as v')->whereExists(function ($q) { $q->selectRaw('1')->from('partner_market_posts as p')->whereColumn('p.transporter_profile_id', 'v.id'); })
                ->where(function ($q) use ($vendorTerm) { $q->where('v.company_name', 'like', '%' . $vendorTerm . '%')->orWhere('v.mobile', 'like', '%' . $vendorTerm . '%')->orWhere('v.id', 'like', '%' . $vendorTerm . '%'); })
                ->select('v.id', 'v.company_name', 'v.mobile')->orderBy('v.company_name')->limit(12)->get();
        }
        $selectedVendor = $this->vendorFilterId ? DB::table('fleet_transporter_profiles')->where('id', $this->vendorFilterId)->select('id', 'company_name', 'mobile')->first() : null;
        $post = $this->postId ? DB::table('partner_market_posts')->find($this->postId) : null;
        $owner = $post ? DB::table('fleet_transporter_profiles')->find($post->transporter_profile_id) : null;
        $quotes = $post ? DB::table('partner_market_quotes as q')->leftJoin('fleet_transporter_profiles as v', 'v.id', '=', 'q.transporter_profile_id')->where('q.post_id', $post->id)->select('q.*', 'v.company_name')->orderBy('q.id')->get() : collect();
        $threads = collect(); $messages = collect(); $payment = null; $plan = null; $audits = collect(); $edits = collect();
        if ($post) {
            $ids = $quotes->pluck('transporter_profile_id')
                ->merge(DB::table('partner_market_messages')->where('post_id', $post->id)->pluck('sender_vendor_id'))
                ->merge(DB::table('partner_market_messages')->where('post_id', $post->id)->pluck('recipient_vendor_id'))
                ->merge(DB::table('partner_market_admin_messages')->where('post_id', $post->id)->pluck('peer_vendor_id'))
                ->filter(fn ($id) => (int) $id !== (int) $post->transporter_profile_id)->unique();
            $threads = DB::table('fleet_transporter_profiles')->whereIn('id', $ids)->select('id', 'company_name', 'contact_person', 'mobile')->get();
            if ($this->peerVendorId && $ids->contains(fn ($id) => (int) $id === $this->peerVendorId)) {
                $peer = $this->peerVendorId; $ownerId = (int) $post->transporter_profile_id;
                $regular = DB::table('partner_market_messages')->where('post_id', $post->id)->where(function ($q) use ($peer, $ownerId) {
                    $q->where(function ($q) use ($peer, $ownerId) { $q->where('sender_vendor_id', $peer)->where('recipient_vendor_id', $ownerId); })
                      ->orWhere(function ($q) use ($peer, $ownerId) { $q->where('sender_vendor_id', $ownerId)->where('recipient_vendor_id', $peer); });
                })->orderByDesc('id')->limit(min(2000, max(100, $this->messageLimit)))->get()->map(function ($m) use ($ownerId) {
                    $m->author = (int) $m->sender_vendor_id === $ownerId ? 'Posting vendor' : 'Receiving / interested vendor'; return $m;
                });
                \App\Services\PartnerMarketplaceFleet::enrich($regular);
                $admin = DB::table('partner_market_admin_messages')->where('post_id', $post->id)->where('peer_vendor_id', $peer)->orderByDesc('id')->limit(min(2000, max(100, $this->messageLimit)))->get()->map(function ($m) { $m->author = 'Admin — ' . $m->admin_name; return $m; });
                $messages = $regular->concat($admin)->sortBy('created_at')->values();
            }
            $payment = $this->selectedPayment((int) $post->id);
            $plan = $payment?->admin_settlement_id ? DB::table('partner_market_admin_settlements')->find($payment->admin_settlement_id) : null;
            $audits = DB::table('partner_market_admin_audits')->where('post_id', $post->id)->orderByDesc('id')->limit(100)->get();
            $edits = DB::table('partner_market_post_edits')->where('post_id', $post->id)->orderByDesc('id')->limit(100)->get();
        }
        $paymentAttempts = $post ? DB::table('partner_market_secure_payments')->where('post_id', $post->id)->orderByDesc('id')->get() : collect();
        $fleetId = $payment?->fleet_submission_id ?? $post?->assigned_fleet_submission_id;
        $assignedFleet = $fleetId ? DB::table('partner_market_fleet_submissions')->find($fleetId) : null;
        $assignedFleet = $assignedFleet ? \App\Services\PartnerMarketplaceFleet::summary($assignedFleet) : null;
        $currentBank = $actor->isAdmin() && $owner ? \App\Services\PartnerMarketplaceBankAccounts::current((int) $owner->id) : null;
        $currentBank = $currentBank ? \App\Services\PartnerMarketplaceBankAccounts::summary($currentBank) : null;
        $payoutBank = null;
        if ($actor->isAdmin() && $plan?->payout_bank_account_id) {
            $savedBank = DB::table('partner_market_bank_accounts')->find($plan->payout_bank_account_id);
            if ($savedBank && (int) $savedBank->vendor_id === (int) $payment->payee_vendor_id) $payoutBank = $this->showPayoutBank
                ? \App\Services\PartnerMarketplaceBankAccounts::revealForSettlement($plan, (int) $payment->payee_vendor_id)
                : \App\Services\PartnerMarketplaceBankAccounts::summary($savedBank);
        }
        return compact('currentBank', 'payoutBank', 'assignedFleet', 'paymentAttempts', 'posts', 'post', 'owner', 'quotes', 'threads', 'messages', 'payment', 'plan', 'audits', 'edits', 'counts', 'vendorChoices', 'selectedVendor', 'filterError') + ['canFinance' => $actor->isAdmin()];
    }
}
