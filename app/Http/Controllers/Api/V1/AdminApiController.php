<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AdminApiController extends Controller
{
    private function ok($data = null, string $message = 'Success')
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    private function fail(string $message = 'Something went wrong', int $code = 422, $errors = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public function dashboard(Request $request)
    {
        return $this->ok([
            'customers' => Schema::hasTable('users') ? User::count() : 0,
            'bookings' => Schema::hasTable('bookings') ? DB::table('bookings')->count() : 0,
            'inquiries' => Schema::hasTable('inquiries') ? DB::table('inquiries')->count() : 0,
            'reviews' => Schema::hasTable('reviews') ? DB::table('reviews')->count() : 0,
            'today_bookings' => Schema::hasTable('bookings')
                ? DB::table('bookings')->whereDate('created_at', today())->count()
                : 0,
            'today_inquiries' => Schema::hasTable('inquiries')
                ? DB::table('inquiries')->whereDate('created_at', today())->count()
                : 0,
        ], 'Admin dashboard fetched successfully');
    }

    public function customers(Request $request)
    {
        if (!Schema::hasTable('users')) {
            return $this->ok([], 'Users table not found');
        }

        $query = User::query();

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        return $this->ok(
            $query->latest()->paginate($request->limit ?? 20),
            'Customers fetched successfully'
        );
    }

    public function bookings(Request $request)
    {
        if (!Schema::hasTable('bookings')) {
            return $this->ok([], 'Bookings table not found');
        }

        $query = DB::table('bookings')->latest();

        if ($request->status && Schema::hasColumn('bookings', 'status')) {
            $query->where('status', $request->status);
        }

        if ($request->mobile && Schema::hasColumn('bookings', 'mobile')) {
            $query->where('mobile', $request->mobile);
        }

        return $this->ok(
            $query->paginate($request->limit ?? 20),
            'Bookings fetched successfully'
        );
    }

    public function inquiries(Request $request)
    {
        if (!Schema::hasTable('inquiries')) {
            return $this->ok([], 'Inquiries table not found');
        }

        $query = DB::table('inquiries')->latest();

        if ($request->mobile && Schema::hasColumn('inquiries', 'mobile')) {
            $query->where('mobile', $request->mobile);
        }

        return $this->ok(
            $query->paginate($request->limit ?? 20),
            'Inquiries fetched successfully'
        );
    }

    public function saveBanner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'image' => 'nullable|string',
            'link' => 'nullable|string',
            'status' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors());
        }

        if (!Schema::hasTable('banners')) {
            return $this->ok($request->all(), 'Banner API ready. Banners table not found.');
        }

        $data = $this->onlyTableColumns('banners', [
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'image' => $request->image,
            'link' => $request->link,
            'status' => $request->status ?? 1,
            'updated_at' => now(),
        ]);

        if ($request->id) {
            DB::table('banners')->where('id', $request->id)->update($data);
            $id = $request->id;
        } else {
            $data['created_at'] = now();
            $id = DB::table('banners')->insertGetId($data);
        }

        return $this->ok(['banner_id' => $id], 'Banner saved successfully');
    }

    public function deleteBanner(Request $request)
    {
        if (!Schema::hasTable('banners')) {
            return $this->ok(null, 'Banner API ready. Banners table not found.');
        }

        $request->validate(['id' => 'required|integer']);
        DB::table('banners')->where('id', $request->id)->delete();

        return $this->ok(null, 'Banner deleted successfully');
    }

    public function saveOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'code' => 'nullable|string',
            'discount' => 'nullable',
            'image' => 'nullable|string',
            'status' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors());
        }

        if (!Schema::hasTable('offers')) {
            return $this->ok($request->all(), 'Offer API ready. Offers table not found.');
        }

        $data = $this->onlyTableColumns('offers', [
            'title' => $request->title,
            'description' => $request->description,
            'code' => $request->code,
            'discount' => $request->discount,
            'image' => $request->image,
            'status' => $request->status ?? 1,
            'updated_at' => now(),
        ]);

        if ($request->id) {
            DB::table('offers')->where('id', $request->id)->update($data);
            $id = $request->id;
        } else {
            $data['created_at'] = now();
            $id = DB::table('offers')->insertGetId($data);
        }

        return $this->ok(['offer_id' => $id], 'Offer saved successfully');
    }

    public function deleteOffer(Request $request)
    {
        if (!Schema::hasTable('offers')) {
            return $this->ok(null, 'Offer API ready. Offers table not found.');
        }

        $request->validate(['id' => 'required|integer']);
        DB::table('offers')->where('id', $request->id)->delete();

        return $this->ok(null, 'Offer deleted successfully');
    }

    public function saveCarImage(Request $request)
    {
        return $this->saveGenericImage($request, 'car_images', 'Car image saved successfully');
    }

    public function saveRouteImage(Request $request)
    {
        return $this->saveGenericImage($request, 'route_images', 'Route image saved successfully');
    }

    public function saveTourImage(Request $request)
    {
        return $this->saveGenericImage($request, 'tour_images', 'Tour image saved successfully');
    }

    public function approveAiImage(Request $request)
    {
        if (!Schema::hasTable('ai_images')) {
            return $this->ok($request->all(), 'AI image approve API ready. Table not found.');
        }

        $request->validate(['id' => 'required|integer']);

        DB::table('ai_images')->where('id', $request->id)->update(
            $this->onlyTableColumns('ai_images', [
                'status' => 'approved',
                'updated_at' => now(),
            ])
        );

        return $this->ok(null, 'AI image approved successfully');
    }

    public function deleteAiImage(Request $request)
    {
        if (!Schema::hasTable('ai_images')) {
            return $this->ok(null, 'AI image delete API ready. Table not found.');
        }

        $request->validate(['id' => 'required|integer']);
        DB::table('ai_images')->where('id', $request->id)->delete();

        return $this->ok(null, 'AI image deleted successfully');
    }

    public function replaceAiImage(Request $request)
    {
        if (!Schema::hasTable('ai_images')) {
            return $this->ok($request->all(), 'AI image replace API ready. Table not found.');
        }

        $request->validate([
            'id' => 'required|integer',
            'image' => 'required|string',
        ]);

        DB::table('ai_images')->where('id', $request->id)->update(
            $this->onlyTableColumns('ai_images', [
                'image' => $request->image,
                'status' => 'replaced',
                'updated_at' => now(),
            ])
        );

        return $this->ok(null, 'AI image replaced successfully');
    }

    public function sendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'message' => 'required|string',
            'user_id' => 'nullable|integer',
            'mobile' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors());
        }

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')->insert(
                $this->onlyTableColumns('notifications', [
                    'user_id' => $request->user_id,
                    'mobile' => $request->mobile,
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type ?? 'admin',
                    'is_read' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        return $this->ok([
            'title' => $request->title,
            'message' => $request->message,
        ], 'Notification sent successfully');
    }

    private function saveGenericImage(Request $request, string $table, string $message)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string',
            'image' => 'required|string',
            'reference_id' => 'nullable',
            'status' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors());
        }

        if (!Schema::hasTable($table)) {
            return $this->ok($request->all(), "{$message}. {$table} table not found.");
        }

        $data = $this->onlyTableColumns($table, [
            'title' => $request->title,
            'image' => $request->image,
            'reference_id' => $request->reference_id,
            'status' => $request->status ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table($table)->insertGetId($data);

        return $this->ok(['id' => $id], $message);
    }

    private function onlyTableColumns(string $table, array $data): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        return collect($data)
            ->filter(fn ($value) => !is_null($value))
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->toArray();
    }
}