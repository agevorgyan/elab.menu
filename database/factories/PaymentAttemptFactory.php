<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\PaymentAttempt;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentAttempt>
 */
class PaymentAttemptFactory extends Factory
{
    protected $model = PaymentAttempt::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'vendor_id' => Vendor::factory(),
            'gateway' => 'stripe',
            'merchant_reference' => 'PAY-'.Str::upper(Str::random(12)),
            'provider_transaction_id' => null,
            'amount' => fake()->randomFloat(2, 500, 20000),
            'currency' => 'AMD',
            'status' => PaymentStatus::Created,
            'request_payload' => null,
            'response_payload' => null,
            'idempotency_key' => (string) Str::uuid(),
            'verified_at' => null,
            'expires_at' => now()->addMinutes(30),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'provider_transaction_id' => 'ch_'.Str::random(16),
            'verified_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Expired,
            'expires_at' => now()->subMinutes(10),
        ]);
    }
}
