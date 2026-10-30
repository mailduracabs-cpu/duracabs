<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\FleetManagement\TransporterProfile;
use App\Services\PartnerMarketplaceBankAccounts as Banks;
use Illuminate\Http\Request;

class PartnerMarketplaceBankController extends PartnerController
{
    private function vendor(Request $request): TransporterProfile
    {
        [$vendor, $role] = $this->context($request);
        abort_unless($role === 'vendor' && $vendor->isActive(), 403, 'Active verified vendor required.');
        return $vendor;
    }
    public function show(Request $request)
    {
        $vendor = $this->vendor($request); $current = Banks::current((int) $vendor->id);
        return response()->json(['status' => true, 'data' => ['account' => $current ? Banks::summary($current) : null,
            'refund_destination' => 'original_payment_source', 'automatic_payout_enabled' => false]])->header('Cache-Control', 'no-store, private');
    }
    public function save(Request $request)
    {
        $vendor = $this->vendor($request);
        $input = $request->validate(['expected_revision' => ['required', 'integer', 'min:0'],
            'account_holder_name' => ['required', 'string', 'max:150'], 'bank_name' => ['required', 'string', 'max:150'],
            'ifsc' => ['required', 'string', 'max:11'], 'replace_account_number' => ['required', 'boolean'],
            'account_number' => ['nullable', 'string', 'max:20'], 'account_number_confirmation' => ['nullable', 'string', 'max:20']]);
        $row = Banks::save($vendor, (int) $input['expected_revision'], $input);
        return response()->json(['status' => true, 'data' => ['account' => Banks::summary($row)],
            'message' => 'Bank details saved. New settlement plans use this version. Existing plans keep their saved account.'])->header('Cache-Control', 'no-store, private');
    }
}
