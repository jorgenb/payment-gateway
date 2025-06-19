<?php

namespace Database\Seeders;

use Bilberry\PaymentGateway\Models\FakePayable;
use Illuminate\Database\Seeder;

class FakePayablesDatabaseSeeder extends Seeder
{
    protected $payables = [
        [
            'name' => 'Maze Bank Tower',
            'description' => 'Own the skyline with the tallest, flashiest office in Los Santos—where the elevator is faster than most sports cars.',
            'currency' => 'USD',
            'amount_minor' => 1000000,
            'location' => 'Pillbox Hill',
            'type' => 'Office',
            'feature' => 'Executive assistant, free snacks, and a vault big enough for your ego.',
        ],
        [
            'name' => 'Fort Zancudo Hangar 3499',
            'description' => 'Park your illicit air fleet right under the military’s nose. Includes enough space for more hardware than the US Air Force.',
            'currency' => 'USD',
            'amount_minor' => 250000,
            'location' => 'Fort Zancudo',
            'type' => 'Hangar',
            'feature' => 'Comes with a P-996 Lazer fighter jet (armed, ready, and with no annoying insurance calls).',
        ],
        [
            'name' => 'Del Perro Heights, Apt 7',
            'description' => 'Wake up to ocean views and the distant sounds of sirens—luxury living for the criminal with taste.',
            'currency' => 'USD',
            'amount_minor' => 250000,
            'location' => 'Del Perro',
            'type' => 'Apartment',
            'feature' => 'Heist planning room with more screens than a conspiracy theorist’s basement.',
        ],
        [
            'name' => 'La Mesa Clubhouse',
            'description' => 'Your MC’s underground HQ. Comes with a bar that never closes and an arm-wrestling table for settling business disputes.',
            'currency' => 'USD',
            'amount_minor' => 120000,
            'location' => 'La Mesa',
            'type' => 'MC Clubhouse',
            'feature' => 'Custom MC emblem workshop—make your patch as intimidating (or hilarious) as you want.',
        ],
        [
            'name' => 'Kosatka Submarine',
            'description' => 'A nuclear sub, straight from Mother Russia. Perfect for planning heists, launching drones, and hiding from your enemies.',
            'currency' => 'USD',
            'amount_minor' => 500000,
            'location' => 'Pacific Ocean',
            'type' => 'Submarine',
            'feature' => 'Sparrow helicopter with lock-on missiles—get to shore fast or rain hellfire from above.',
        ],
        [
            'name' => 'Up-n-Atomizer',
            'description' => 'A handheld, sci-fi force gun that sends anything (and anyone) flying. Perfect for parties, pranks, or creative getaways.',
            'currency' => 'USD',
            'amount_minor' => 25000,
            'location' => 'Ammu-Nation',
            'type' => 'Weapon',
            'feature' => 'Unlimited ammo. Zero subtlety.',
        ],
        [
            'name' => 'Widowmaker',
            'description' => 'A minigun from the future. Red laser beams, no cooldown, and the sound of absolute chaos.',
            'currency' => 'USD',
            'amount_minor' => 35000,
            'location' => 'Ammu-Nation',
            'type' => 'Weapon',
            'feature' => 'Pure alien energy—guaranteed to make a scene.',
        ],
        [
            'name' => 'Heavy Sniper Mk II',
            'description' => 'For when you need to reach out and touch someone… through body armor, a car door, and their last shred of hope.',
            'currency' => 'USD',
            'amount_minor' => 15000,
            'location' => 'Ammu-Nation',
            'type' => 'Weapon',
            'feature' => 'Optional explosive rounds. Scope sold separately.',
        ],
        [
            'name' => 'AP Pistol',
            'description' => 'Compact, fast, and as reliable as your mechanic. The drive-by king’s sidearm of choice.',
            'currency' => 'USD',
            'amount_minor' => 5000,
            'location' => 'Ammu-Nation',
            'type' => 'Weapon',
            'feature' => 'Full auto, concealable, and as loud as your getaway car.',
        ],
        [
            'name' => 'Homing Launcher',
            'description' => 'For those moments when “almost” hitting the target isn’t good enough.',
            'currency' => 'USD',
            'amount_minor' => 45000,
            'location' => 'Ammu-Nation',
            'type' => 'Weapon',
            'feature' => 'Lock-on capability—perfect for oppressors, choppers, and smug satisfaction.',
        ],
        [
            'name' => 'Oppressor Mk II',
            'description' => 'The flying motorcycle that ruined public lobbies forever. Comes with lock-on missiles and style points.',
            'currency' => 'USD',
            'amount_minor' => 1000000,
            'location' => 'Warstock Cache & Carry',
            'type' => 'Vehicle',
            'feature' => 'Jet-powered, anti-griefing, anti-everything.',
        ],
        [
            'name' => 'Armored Kuruma',
            'description' => 'The ultimate heist starter pack—fast, stylish, and nearly bulletproof.',
            'currency' => 'USD',
            'amount_minor' => 45000,
            'location' => 'Southern San Andreas Super Autos',
            'type' => 'Vehicle',
            'feature' => 'Seatbelts not included, but you probably won’t need them.',
        ],
        [
            'name' => 'Insurgent Pick-Up Custom',
            'description' => 'A military truck with a minigun turret. Perfect for convoy attacks or just making a statement at the car meet.',
            'currency' => 'USD',
            'amount_minor' => 95000,
            'location' => 'Warstock Cache & Carry',
            'type' => 'Vehicle',
            'feature' => 'Seats six, plus one gunner with anger issues.',
        ],
        [
            'name' => 'Grotti Vigilante',
            'description' => 'It’s the Batmobile, but more illegal. Jet boost and rockets included.',
            'currency' => 'USD',
            'amount_minor' => 85000,
            'location' => 'Warstock Cache & Carry',
            'type' => 'Vehicle',
            'feature' => 'Jump ramps, jet propulsion, and street-level domination.',
        ],
        [
            'name' => 'Deluxo',
            'description' => 'A classic sports car with the minor upgrade of being able to fly and shoot rockets.',
            'currency' => 'USD',
            'amount_minor' => 95000,
            'location' => 'Warstock Cache & Carry',
            'type' => 'Vehicle',
            'feature' => 'Hover mode and time-travel vibes. Roads? Where we’re going, we don’t need roads.',
        ],
    ];

    public function run(): void
    {
        foreach ($this->payables as $data) {
            FakePayable::factory()->create([
                'data' => $data,
            ]);
        }
    }
}
