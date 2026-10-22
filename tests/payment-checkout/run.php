<?php
// Isolated regression harness: real checkout service + in-memory Laravel/model adapters.
// This does not replace integration testing against the application's database/observers.
namespace Carbon {
    class Carbon extends \DateTimeImmutable {
        public static function parse($value) { return $value instanceof self ? $value : new self((string) $value); }
        public function lt($other) { return $this < $other; }
        public function subMinutes($n) { return $this->modify("-$n minutes"); }
        public function toDateTimeString() { return $this->format('Y-m-d H:i:s'); }
    }
}
namespace Illuminate\Support\Facades {
    class DB {
        public static array $rows = ['booking_payment_checkouts' => [], 'self_drive_bookings' => [], 'orders' => [], 'addresses' => [], 'items' => [], 'vehicles' => []];
        public static array $callbacks = [];
        public static function table($name) { return new \TestQuery($name); }
        public static function transaction($callback, $attempts = 1) {
            $before = unserialize(serialize(self::$rows)); $callbacks = self::$callbacks; self::$callbacks = [];
            try { $result = $callback(); } catch (\Throwable $e) { self::$rows = $before; self::$callbacks = $callbacks; throw $e; }
            $pending = self::$callbacks; self::$callbacks = $callbacks;
            foreach ($pending as $fn) { $fn(); }
            return $result;
        }
        public static function afterCommit($fn) { self::$callbacks[] = $fn; }
    }
    class Schema {
        public static function hasTable($name) { return isset(DB::$rows[$name]); }
        public static function hasColumn($table, $column) { return true; }
    }
    class Log { public static function warning(...$args) {} public static function critical(...$args) {} }
}
namespace Illuminate\Support { class Str { public static function upper($v) { return strtoupper($v); } public static function random($n) { return bin2hex(random_bytes($n)); } } }
namespace App\Models {
    class FakeModel {
        protected array $attributes = [];
        public function __get($key) { return $this->attributes[$key] ?? null; }
        public function __set($key, $value) { $this->attributes[$key] = $value; }
        public function __isset($key) { return isset($this->attributes[$key]); }
        public function setRawAttributes($attributes) { $this->attributes = $attributes; return $this; }
        public function forceFill($attributes) { return $this->setRawAttributes($attributes); }
        public static function query() { return new \TestModelQuery(static::class); }
        public function getTable() { return static::TABLE; }
        public function save() { $this->id = \Illuminate\Support\Facades\DB::table($this->getTable())->insertGetId($this->attributes); }
        public function loadMissing(...$args) { return $this; }
        public function items() { return new \TestModelQuery(Item::class); }
    }
    class Order extends FakeModel { const TABLE = 'orders'; }
    class SelfDriveBooking extends FakeModel { const TABLE = 'self_drive_bookings'; }
    class Vehicle extends FakeModel { const TABLE = 'vehicles'; }
    class Address extends FakeModel { const TABLE = 'addresses'; }
    class Item extends FakeModel { const TABLE = 'items'; }
}
namespace App\Services {
    class RazorpayService {
        public static array $payments = [];
        public static bool $validSignature = true;
        public static function createOrder($amount, $receipt, $notes) { return ['status' => true, 'data' => ['id' => 'order_'.$receipt]]; }
        public static function publicKey() { return 'test_key'; }
        public static function verifyPayment(...$args) { return ['status' => self::$validSignature]; }
        public static function fetchPayment($id) { return ['status' => true, 'data' => self::$payments[$id] ?? []]; }
    }
    class SelfDriveAvailabilityService { public bool $available = true; public function check($data) { return ['status' => $this->available]; } }
    class SelfDriveBookingService { public int $notifications = 0; public function notifyVerifiedCreation($booking) { $this->notifications++; } }
    class WhatsAppService { public static int $notifications = 0; public static function dispatchEvent(...$args) { self::$notifications++; } }
}
namespace {
    use Illuminate\Support\Facades\DB;
    use App\Services\BookingPaymentCheckoutService;
    use App\Services\BookingPaymentRules;
    use App\Services\RazorpayService;
    class TestQuery {
        private array $filters = [];
        public function __construct(private string $table) {}
        public function where($column, $value) { $this->filters[] = [$column, $value]; return $this; }
        public function whereNull($column) { return $this->where($column, null); }
        public function lockForUpdate() { return $this; }
        private function matches($row) { foreach ($this->filters as [$col, $val]) { if (($row[$col] ?? null) != $val) { return false; } } return true; }
        public function insertGetId($row) { $id = count(DB::$rows[$this->table]) + 1; $row['id'] = $id; DB::$rows[$this->table][$id] = $row; return $id; }
        public function find($id) { $row = DB::$rows[$this->table][$id] ?? null; return $row && $this->matches($row) ? (object) $row : null; }
        public function first() { foreach (DB::$rows[$this->table] as $row) { if ($this->matches($row)) { return (object) $row; } } return null; }
        public function exists() { return $this->first() !== null; }
        public function update($values) { foreach (DB::$rows[$this->table] as &$row) { if ($this->matches($row)) { $row = array_merge($row, $values); } } }
    }
    class TestModelQuery {
        public function __construct(private string $model) {}
        public function availableForCustomer() { return $this; }
        public function lockForUpdate() { return $this; }
        public function find($id) { $model = new $this->model; $row = DB::table($model->getTable())->find($id); return $row ? $model->setRawAttributes((array) $row) : null; }
        public function findOrFail($id) { return $this->find($id) ?? throw new \RuntimeException('Not found'); }
        public function create($attributes) { $model = new $this->model; $model->setRawAttributes($attributes); $model->save(); return $model; }
    }
    function now() { return new \Carbon\Carbon('2026-10-22 12:00:00'); }
    $services = [
        \App\Services\SelfDriveAvailabilityService::class => new \App\Services\SelfDriveAvailabilityService,
        \App\Services\SelfDriveBookingService::class => new \App\Services\SelfDriveBookingService,
    ];
    function app($class) { global $services; return $services[$class]; }
    require __DIR__.'/../../app/Services/BookingPaymentRules.php';
    require __DIR__.'/../../app/Services/BookingPaymentCheckoutService.php';
    set_error_handler(function ($severity, $message, $file, $line) { throw new \ErrorException($message, 0, $severity, $file, $line); });
    $checks = 0;
    function check($value, $label) { global $checks; if (! $value) { throw new \RuntimeException('FAIL: '.$label); } $checks++; }
    function rejects($fn, $label) { try { $fn(); } catch (\RuntimeException $e) { check(true, $label); return; } throw new \RuntimeException('FAIL (accepted): '.$label); }
    $service = new BookingPaymentCheckoutService;
    DB::$rows['vehicles'][1] = ['id' => 1];
    function draft($service, $type = 'advance', $source = 'self_drive') {
        $attributes = ['customer_id' => 7, 'user_id' => 7, 'vehicle_id' => 1, 'start_datetime' => '2026-10-31 10:00:00', 'end_datetime' => '2026-11-01 10:00:00', 'booking_status' => 'pending_payment'];
        $payload = ['attributes' => $attributes, 'address' => ['full_name' => 'Test', 'phone' => '1234567890'], 'items' => [['product_id' => 1]]];
        return $service->stage(7, $source, $payload, 10980, $type, $type === 'advance' ? 500 : 10980);
    }
    function payment($id, $order, $amount = 500) { return ['id' => $id, 'order_id' => $order, 'status' => 'captured', 'captured' => true, 'currency' => 'INR', 'amount' => (int) round($amount*100), 'amount_refunded' => 0]; }
    $draft = draft($service);
    check(count(DB::$rows['self_drive_bookings']) === 0, 'staging creates no booking');
    check(app(\App\Services\SelfDriveBookingService::class)->notifications === 0, 'no early notification');
    rejects(fn () => $service->owned(-$draft->id, 8), 'ownership');
    $gateway = $service->gatewayOrder($draft);
    check($service->gatewayOrder($draft)['id'] === $gateway['id'], 'gateway order reused on page rerender');
    check($gateway['paid_amount'] === 0 && $gateway['advance_amount'] === 0, 'unpaid checkout has zero received');
    $draft = $service->owned(-$draft->id, 7);
    $pay = payment('pay_1', $gateway['id']);
    foreach (['failed', 'authorized', 'created', 'pending'] as $status) {
        RazorpayService::$payments['pay_1'] = array_merge($pay, ['status' => $status, 'captured' => false]);
        rejects(fn () => $service->completeCaptured($draft, 'pay_1'), $status.' rejects');
        check(count(DB::$rows['self_drive_bookings']) === 0, $status.' creates no booking');
    }
    foreach ([['amount' => 100], ['currency' => 'USD'], ['order_id' => 'other'], ['id' => 'other'], ['captured' => false], ['amount_refunded' => 1]] as $bad) {
        rejects(fn () => BookingPaymentRules::received(array_merge($pay, $bad), $gateway['id'], 'pay_1', 500), 'payment mismatch');
    }
    RazorpayService::$payments['pay_1'] = $pay;
    RazorpayService::$validSignature = false;
    rejects(fn () => $service->verify($draft, $gateway['id'], 'pay_1', 'bad'), 'bad signature creates no booking');
    check(count(DB::$rows['self_drive_bookings']) === 0, 'bad signature no booking');
    RazorpayService::$validSignature = true;
    $paid = $service->verify($draft, $gateway['id'], 'pay_1', 'good');
    check($paid['paid_amount'] === 500.0 && $paid['remaining_amount'] === 10480.0, 'token amount exact');
    check($paid['advance_amount'] === 500.0 && $paid['payment_status'] === 'partial', 'token partial');
    check($paid['booking_status'] === 'pending_vendor_confirmation', 'paid booking enters vendor queue');
    $again = $service->completeCaptured($draft, 'pay_1');
    check($again['duplicate'] && count(DB::$rows['self_drive_bookings']) === 1, 'callback plus webhook creates one booking');
    check(app(\App\Services\SelfDriveBookingService::class)->notifications === 1, 'notification once');
    $full = draft($service, 'full'); $g = $service->gatewayOrder($full); $full = $service->owned(-$full->id, 7);
    RazorpayService::$payments['pay_full'] = payment('pay_full', $g['id'], 10980);
    $result = $service->completeCaptured($full, 'pay_full');
    check($result['payment_status'] === 'paid' && $result['remaining_amount'] === 0.0, 'full payment');
    $web = draft($service, 'advance', 'order'); $g = $service->gatewayOrder($web); $web = $service->owned(-$web->id, 7);
    check(count(DB::$rows['orders']) === 0, 'website no order before payment');
    RazorpayService::$payments['pay_web'] = payment('pay_web', $g['id']);
    $result = $service->completeCaptured($web, 'pay_web');
    check(count(DB::$rows['orders']) === 1 && count(DB::$rows['addresses']) === 1 && count(DB::$rows['items']) === 1, 'website creates order relations after capture');
    check($result['paid_amount'] === 500.0 && $result['remaining_amount'] === 10480.0, 'website token exact');
    $review = draft($service); $g = $service->gatewayOrder($review); $review = $service->owned(-$review->id, 7);
    RazorpayService::$payments['pay_review'] = payment('pay_review', $g['id']);
    app(\App\Services\SelfDriveAvailabilityService::class)->available = false;
    rejects(fn () => $service->completeCaptured($review, 'pay_review'), 'unavailable vehicle');
    $review = $service->owned(-$review->id, 7);
    check($review->state === 'paid_review_required' && $review->gateway_payment_id === 'pay_review', 'captured payment retained for recovery');
    check(count(DB::$rows['self_drive_bookings']) === 2, 'unavailable booking transaction rolled back');
    app(\App\Services\SelfDriveAvailabilityService::class)->available = true;
    $recovered = $service->completeCaptured($review, 'pay_review');
    check($recovered['booking_created'] && count(DB::$rows['self_drive_bookings']) === 3, 'recovery retry once');
    echo "PASS: $checks regression assertions (in-memory adapters).\n";
}
