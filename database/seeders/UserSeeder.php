<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Firm;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create user
        $user = User::create([
            'name' => 'Jackie Gonz',
            'email' => 'jackie@gonzfirm.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create firm
        $firm = Firm::create([
            'name' => 'Gonz Law Firm',
            'slug' => 'gonz-law-firm',
            'primary_email' => 'jackie@gonzfirm.com',
            'primary_phone' => '555-123-4567',
            'website_url' => 'https://gonzfirm.com',
            'timezone' => 'America/Chicago',
        ]);

        // Attach user to firm
        $user->firms()->attach($firm->id);
    }
}
