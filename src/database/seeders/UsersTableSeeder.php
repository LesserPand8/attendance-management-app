<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => 'user1',
            'email' => 'user1@example.com',
            'password' => bcrypt('testtest')
        ];
        DB::table('users')->insert($param);

        $param = [
            'name' => 'user2',
            'email' => 'user2@example.com',
            'password' => bcrypt('testtest')
        ];
        DB::table('users')->insert($param);
    }
}
