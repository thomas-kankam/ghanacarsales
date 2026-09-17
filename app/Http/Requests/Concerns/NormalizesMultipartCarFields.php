<?php

namespace App\Http\Requests\Concerns;

trait NormalizesMultipartCarFields
{
    /**
     * FormData / query strings send booleans as "true"/"false" and empty numbers as "".
     * Laravel's boolean rule only accepts true/false/0/1/"0"/"1", so normalize first.
     */
    protected function normalizeMultipartCarFields(): void
    {
        $merge = [];

        foreach (['swap_deals', 'aircon', 'registered'] as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $value = $this->input($field);

            if ($value === null || $value === '') {
                $merge[$field] = null;
                continue;
            }

            if (is_bool($value)) {
                $merge[$field] = $value;
                continue;
            }

            $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            // Accept common multipart / JS string forms; leave unknown values for the boolean rule.
            if ($normalized !== null || in_array($value, [0, 1, '0', '1', true, false], true)) {
                $merge[$field] = (bool) ($normalized ?? $value);
            }
        }

        foreach (['year_of_manufacture', 'mileage', 'registration_year', 'price', 'plan_price', 'min_year', 'max_year', 'min_mileage', 'max_mileage', 'min_price', 'max_price'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        if ($this->filled('plan_details') && is_string($this->input('plan_details'))) {
            $decoded = json_decode($this->input('plan_details'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $merge['plan_details'] = $decoded;
            }
        }

        // Multipart may send models as a JSON string.
        if ($this->filled('models') && is_string($this->input('models'))) {
            $decoded = json_decode($this->input('models'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $merge['models'] = $decoded;
            }
        }

        // fuel_type may be a single value or JSON array string from FormData.
        if ($this->filled('fuel_type') && is_string($this->input('fuel_type'))) {
            $raw = $this->input('fuel_type');
            if (str_starts_with(trim($raw), '[')) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $merge['fuel_type'] = $decoded;
                }
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
