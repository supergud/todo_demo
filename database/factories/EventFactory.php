<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Event;
use Faker\Generator as Faker;

$factory->define(Event::class, function (Faker $faker) {
    return [
        'event'    => $faker->sentence,
        'deadline' => $faker->randomElement([0, 0, 1]) ? \Carbon\Carbon::now()->addDay()->toDateString() : null,
    ];
});
