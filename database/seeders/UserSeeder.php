<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create team leaders
        $teamLeaders = [
            [
                'name' => 'John Doe Team Leader',
                'email' => 'teamleader1@gmail.com',
                'password' => Hash::make('leader1'),
                'role' => 'team_leader',
            ],
            [
                'name' => 'Jane Smith Team Leader',
                'email' => 'jteamleader2@gmail.com',
                'password' => Hash::make('leader2'),
                'role' => 'team_leader',
            ],
        ];

        foreach ($teamLeaders as $leader) {
            User::create($leader);
        }

        $teamMembers = [
            [
                'name' => 'Mike Johnson',
                'email' => 'teammember1@gmail.com',
                'password' => Hash::make('member1'),
                'role' => 'team_member',
            ],
            [
                'name' => 'Sarah Williams',
                'email' => 'teammember2@gmail.com',
                'password' => Hash::make('member2'),
                'role' => 'team_member',
            ],
        ];

        for ($i = 3; $i <= 20; $i++) {
            $teamMembers[] = [
                'name' => 'Member ' . $i,
                'email' => 'member' . $i . '@gmail.com',
                'password' => Hash::make('member1'),
                'role' => 'team_member',
            ];
        }

        foreach ($teamMembers as $member) {
            User::create($member);
        }

        $this->command->info('Successfully seeded users:');
        $this->command->info('- 1 admin (admin@gmail.com / admin)');
        $this->command->info('- 2 team leaders (leader accounts / leader1)');
        $this->command->info('- 20 team members (member accounts / member1)');
    }
}
