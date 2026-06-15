<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfessionalUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Usuario Profesional',
            'email' => 'testprofesional@materiagis.local',
            'password' => bcrypt('secret123'),
        ]);

        $role = DB::table('roles')->where('slug', 'professional')->first();

        if ($role) {
            DB::table('user_roles')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
