            );

            $this->extra_km_amount = round(
                (float) $this->extra_km
                * (float) $this->extra_km_rate,
                2
            );
        }

        $billing = $this->finalBilling();

        /*
         * Keep final_amount as the effective GST-inclusive RENTAL total.
         * Do not replace it with a payable amount that may include security deposit.
         */
        $this->final_amount = $this->effectiveRentalAmount();

        $this->refund_amount =
            (float) ($billing['refund_amount'] ?? 0);

        $this->setOptionalAttribute(
            'gst_percent',
            $billing['gst_percent'] ?? 18
        );

        $this->setOptionalAttribute(
            'gst_amount',
            $this->includedGstAmount()
        );

        $this->setOptionalAttribute(
            'online_payment_charge',
            $billing['online_payment_charge'] ?? 0
        );

        $this->syncPayment();
    }

    private function numericAttribute(
        array $keys
    ): float {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $this->attributes)) {
                continue;
            }

            $value = $this->attributes[$key];

            if ($value === null || $value === '') {
                continue;
            }

            return max(0, (float) $value);
        }

        return 0;
    }

    private function booleanAttribute(
        array $keys
    ): bool {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $this->attributes)) {
                continue;
            }

            $value = $this->attributes[$key];

            if (is_bool($value)) {
                return $value;
            }

            if ((int) $value === 1) {
                return true;
            }

            if (
                in_array(
                    strtolower(trim((string) $value)),
                    ['true', 'yes', 'on', 'selected'],
                    true
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function setOptionalAttribute(
        string $key,
        mixed $value
    ): void {
        if (! array_key_exists($key, $this->attributes)) {
            return;
        }

        $this->setAttribute($key, $value);
    }
}