<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();

        foreach ($users as $user) {
            foreach (range(1, rand(5, 10)) as $event) {
                $event = factory(Event::class)->create([
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
