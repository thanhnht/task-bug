<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Tạo Admin mặc định ───────────────────────────────────────────────
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email'          => 'admin@company.com',
                'full_name'      => 'System Administrator',
                'password'       => Hash::make('Admin@123456'),
                'role'           => User::ROLE_ADMIN,
                'is_first_login' => false,  // Admin không cần đổi pass lần đầu
                'is_active'      => true,
                'login_attempts' => 0,
            ]
        );

        // ─── Tạo nhân viên mẫu ───────────────────────────────────────────────
        User::firstOrCreate(
            ['username' => 'employee01'],
            [
                'email'          => 'employee01@company.com',
                'full_name'      => 'Nguyễn Văn A',
                'password'       => Hash::make('Temp@1234'),
                'role'           => User::ROLE_EMPLOYEE,
                'is_first_login' => true,   // Phải đổi pass lần đầu
                'is_active'      => true,
                'login_attempts' => 0,
            ]
        );

        $this->command->info('✅ Seeder hoàn tất!');
        $this->command->info('   Admin:    username=admin       | password=Admin@123456');
        $this->command->info('   Employee: username=employee01  | password=Temp@1234  (cần đổi pass)');
    }
}
