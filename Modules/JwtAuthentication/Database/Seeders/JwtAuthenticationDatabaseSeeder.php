<?php

namespace Modules\JwtAuthentication\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Modules\JwtAuthentication\Entities\User;
use Faker;

class JwtAuthenticationDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        Model::unguard();

        for ($i = 1; $i < 20; $i++) {
            User::create( [
                'name' => $faker->name,
                'email' => $faker->email,
                'password' => Hash::make('123456'),
                'active' => true,
                'activation_token' => '',
            ]);
        }
    }
}
