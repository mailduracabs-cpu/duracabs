<?php

namespace Tests\Unit;

use App\Services\CustomerPhoneNumber;
use App\Services\WhatsAppService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CustomerPhoneNumberTest extends TestCase
{
    public function test_indian_formats_share_one_legacy_account_and_otp_key(): void
    {
        foreach (['9876543210', '+919876543210', '919876543210', '00919876543210'] as $input) {
            self::assertSame('9876543210', CustomerPhoneNumber::normalize($input));
        }
        self::assertSame('0000000001', CustomerPhoneNumber::normalize('+910000000001', '91'));
    }

    public function test_international_numbers_retain_country_identity_and_significant_zero(): void
    {
        self::assertSame('+447911123456', CustomerPhoneNumber::normalize('07911123456', '44'));
        self::assertSame('+390669821234', CustomerPhoneNumber::normalize('0669821234', '39'));
        self::assertSame('+4512345678', CustomerPhoneNumber::normalize('+4512345678'));
        self::assertFalse(CustomerPhoneNumber::isIndian('+4512345678'));
        self::assertNotContains('4512345678', CustomerPhoneNumber::aliases('+4512345678'));
        self::assertNotContains('7911123456', CustomerPhoneNumber::aliases('+447911123456'));
    }

    public function test_malformed_or_mismatched_numbers_are_rejected(): void
    {
        foreach ([['abc123', null], ['+447911123456', '91'], ['123', '44'],
            ['1234567890', '000'], ['+123456789', '1'], ['+1234567890123456', null]] as [$number, $code]) {
            try {
                CustomerPhoneNumber::normalize($number, $code);
                self::fail('Invalid phone number accepted: ' . $number);
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function test_explicit_foreign_whatsapp_numbers_are_not_prefixed_with_india(): void
    {
        self::assertSame('4512345678', WhatsAppService::cleanNumber('+4512345678'));
        self::assertSame('447911123456', WhatsAppService::cleanNumber('00447911123456'));
    }
}
