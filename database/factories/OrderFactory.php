<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{


    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id'      => \App\Models\User::inRandomOrder()->first()->id ?? 1,
            'total_amount' => $this->faker->randomFloat(2, 50, 2000),
            'status'       => $this->faker->randomElement(['pending', 'completed', 'cancelled']),
            'created_at'   => now()->subDay()->setTime(rand(0, 23), rand(0, 59)),
            'updated_at'   => now(),
        ];
    }


}