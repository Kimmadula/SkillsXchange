<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userModel = \App\Models\User::class;
        $tradeModel = \App\Models\Trade::class;
        $skillModel = class_exists('App\\Models\\Skill') ? \App\Models\Skill::class : null;

        $user = $userModel::first();
        if (!$user) {
            Log::warning('NotificationDemoSeeder: No users found, skipping notifications seeding.');
            return;
        }

        // Find or create a simple ongoing trade for the user
        $trade = $tradeModel::where('user_id', $user->id)->where('status', 'ongoing')->first();
        if (!$trade) {
            $now = Carbon::now();
            // Ensure skill IDs are available (fallback to creating simple skills if model exists)
            $offeringSkillId = null;
            $lookingSkillId = null;
            if ($skillModel) {
                $offering = $skillModel::first() ?: $skillModel::create(['name' => 'Demo Offering Skill']);
                $looking = $skillModel::where('id', '!=', $offering->id)->first() ?: $skillModel::create(['name' => 'Demo Looking Skill']);
                $offeringSkillId = $offering->id;
                $lookingSkillId = $looking->id;
            } else {
                // If there is no Skill model, try to use existing IDs 1 and 2 (best-effort)
                $offeringSkillId = 1;
                $lookingSkillId = 2;
            }
            $trade = $tradeModel::create([
                'user_id' => $user->id,
                'offering_skill_id' => $offeringSkillId,
                'looking_skill_id' => $lookingSkillId,
                'start_date' => $now->toDateString(),
                'end_date' => $now->toDateString(),
                'available_from' => $now->copy()->addMinutes(60)->format('H:i:s'), // starts in 60m
                'available_to' => $now->copy()->addHours(3)->format('H:i:s'),
                'preferred_days' => null,
                'gender_pref' => null,
                'location' => null,
                'session_type' => 'online',
                'use_username' => false,
                'status' => 'ongoing',
            ]);
        }

        // Insert notifications into user_notifications table
        try {
            // Pre-session reminders
            $reminderMinutes = [60, 30, 15];
            foreach ($reminderMinutes as $minutes) {
                DB::table('user_notifications')->insert([
                    'user_id' => $user->id,
                    'type' => 'pre_session_reminder',
                    'data' => json_encode([
                        'trade_id' => $trade->id,
                        'minutes_before' => $minutes,
                        'message' => "Your session starts in {$minutes} minutes.",
                        'start_date' => $trade->start_date,
                        'available_from' => $trade->available_from,
                        'offering_skill' => optional($trade->offeringSkill)->name ?? 'Unknown',
                        'looking_skill' => optional($trade->lookingSkill)->name ?? 'Unknown',
                    ]),
                    'read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Expiration warnings
            $warningHours = [24, 12, 1];
            foreach ($warningHours as $hours) {
                DB::table('user_notifications')->insert([
                    'user_id' => $user->id,
                    'type' => 'session_expiration_warning',
                    'data' => json_encode([
                        'trade_id' => $trade->id,
                        'hours_before' => $hours,
                        'message' => "Your session will expire in {$hours} hours.",
                        'end_date' => $trade->end_date,
                        'available_to' => $trade->available_to,
                        'offering_skill' => optional($trade->offeringSkill)->name ?? 'Unknown',
                        'looking_skill' => optional($trade->lookingSkill)->name ?? 'Unknown',
                    ]),
                    'read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('NotificationDemoSeeder: Failed to send notifications', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}


