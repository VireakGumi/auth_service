<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // user default
        $users = [
            [
                'first_name' => 'Admin',
                'last_name' => 'Account',
                'username' => 'admin',
                'email' => 'admin@gmail.com',
                'password' => '11223344Aa!@#',
            ],
            [
                'first_name' => 'User',
                'last_name' => 'Account',
                'username' => 'user',
                'email' => 'user@gmail.com',
                'password' => '11223344Aa!@#',
            ],
        ];

        foreach ($users as $data) {
            // create user
            $user = new User();
            $user->first_name = $data['first_name'];
            $user->last_name = $data['last_name'];
            $user->username = $data['username'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->save();
            // assign role
            if ($user->username === 'admin') {
                $user->roles()->attach(1); // admin role
            } else {
                $user->roles()->attach(2); // user role
            }
            $user->save();
        }
    }
}
