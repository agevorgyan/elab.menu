<?php

namespace Database\Factories;

use App\Models\AiWaiterSession;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AiWaiterSession>
 */
class AiWaiterSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'session_token' => Str::random(32),
            'language' => 'hy',
            'status' => 'started',
            'preferences' => [],
            'questions_history' => [],
            'answers_history' => [],
            'recommendations' => [],
            'cart_items' => [],
        ];
    }
}
