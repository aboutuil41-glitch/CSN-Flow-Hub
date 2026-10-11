<?php

namespace Database\Seeders;

use App\Models\File;
use App\Models\Feedback;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $client = User::create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
            'role' => 'client'
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        $team1 = User::create([
            'name' => 'Team Member 1',
            'email' => 'team1@example.com',
            'password' => Hash::make('password'),
            'role' => 'team'
        ]);

        $team2 = User::create([
            'name' => 'Team Member 2',
            'email' => 'team2@example.com',
            'password' => Hash::make('password'),
            'role' => 'team'
        ]);

        $project = Project::create([
            'name' => 'AUI Portal',
            'description' => 'Unified university portal',
            'statue' => 'in_progress',
            'created_by' => $client->id
        ]);

        $project->users()->attach($client->id, [
            'role' => 'client'
        ]);

        $project->users()->attach($team1->id, [
            'role' => 'team'
        ]);

        $project->users()->attach($team2->id, [
            'role' => 'team'
        ]);

        $task1 = Task::create([
            'project_id' => $project->id,
            'assigned_to' => $team1->id,
            'title' => 'Setup backend',
            'description' => 'Initialize Laravel API',
            'status' => 'completed',
            'deadline' => now()->addDays(5)
        ]);

        $task2 = Task::create([
            'project_id' => $project->id,
            'assigned_to' => $team2->id,
            'title' => 'Build frontend',
            'description' => 'React dashboard UI',
            'status' => 'in_progress',
            'deadline' => now()->addDays(10)
        ]);

        TaskComment::create([
            'task_id' => $task1->id,
            'user_id' => $team1->id,
            'content' => 'Backend setup completed'
        ]);

        $file = File::create([
            'project_id' => $project->id,
            'uploaded_by' => $team1->id,
            'name' => 'api-docs.pdf',
            'path' => 'project_files/api-docs.pdf',
            'version' => 'v1'
        ]);

        Feedback::create([
            'user_id' => $client->id,
            'file_id' => $file->id,
            'message' => 'Looks good so far',
            'type' => 'approval'
        ]);
    }
}