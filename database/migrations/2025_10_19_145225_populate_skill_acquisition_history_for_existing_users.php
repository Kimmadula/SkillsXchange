<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all users who have skills but no acquisition history
        $usersWithSkills = DB::table('user_skills')
            ->join('users', 'user_skills.user_id', '=', 'users.id')
            ->leftJoin('skill_acquisition_history', function($join) {
                $join->on('user_skills.user_id', '=', 'skill_acquisition_history.user_id')
                     ->on('user_skills.skill_id', '=', 'skill_acquisition_history.skill_id');
            })
            ->whereNull('skill_acquisition_history.id')
            ->select('user_skills.user_id', 'user_skills.skill_id', 'users.created_at')
            ->get();

        // Create acquisition history entries for existing skills
        foreach ($usersWithSkills as $userSkill) {
            DB::table('skill_acquisition_history')->insert([
                'user_id' => $userSkill->user_id,
                'skill_id' => $userSkill->skill_id,
                'trade_id' => null,
                'acquisition_method' => 'manual_add',
                'score_achieved' => 100,
                'notes' => 'Registered skill (migrated from existing data)',
                'acquired_at' => $userSkill->created_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove acquisition history entries that were created by this migration
        DB::table('skill_acquisition_history')
            ->where('notes', 'Registered skill (migrated from existing data)')
            ->delete();
    }
};