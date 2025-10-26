<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => 'admin1',
            'email' => 'admin1@example.com',
            'password' => bcrypt('admintest')
        ];
        DB::table('admins')->insert($param);
    }
}
