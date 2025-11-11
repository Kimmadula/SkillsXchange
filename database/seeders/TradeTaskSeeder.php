<?php

namespace Database\Seeders;

use App\Models\Trade;
use App\Models\TradeTask;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TradeTaskSeeder extends Seeder
{
    public function run()
    {
        // Always seed a few tasks for a specific demo trade/user if available
        $specificTradeId = 1;
        $specificUserEmail = 'dwight@example.com';

        $demoTrade = Trade::find($specificTradeId);
        $demoUser = User::where('email', $specificUserEmail)->first();

        if ($demoTrade && $demoUser) {
            // Ensure Dwight has at least 3 tasks assigned on trade 1
            $currentAssignedToDwight = TradeTask::where('trade_id', $demoTrade->getKey())
                ->where('assigned_to', $demoUser->getKey())
                ->count();
            $needed = max(0, 3 - $currentAssignedToDwight);

            if ($needed > 0) {
                $this->seedTasksForTrade($demoTrade, $demoUser, $needed);
                $this->command?->info("Provisioned {$needed} demo task(s) for trade ID {$specificTradeId} assigned to {$specificUserEmail}.");
            } else {
                $this->command?->warn("Trade ID {$specificTradeId} already has at least 3 tasks assigned to {$specificUserEmail}. Skipping demo seeding.");
            }

            // Ensure partner (not Dwight) has at least 3 tasks as well
            $partner = null;
            if ($demoTrade->user && $demoTrade->user->getKey() !== $demoUser->getKey()) {
                $partner = $demoTrade->user;
            }
            if (!$partner) {
                $partner = User::whereKeyNot($demoUser->getKey())->inRandomOrder()->first();
            }
            if ($partner) {
                $currentAssignedToPartner = TradeTask::where('trade_id', $demoTrade->getKey())
                    ->where('assigned_to', $partner->getKey())
                    ->count();
                $neededPartner = max(0, 3 - $currentAssignedToPartner);
                if ($neededPartner > 0) {
                    $this->seedTasksForTrade($demoTrade, $partner, $neededPartner);
                    $this->command?->info("Provisioned {$neededPartner} partner task(s) for trade ID {$specificTradeId} assigned to user ID {$partner->getKey()}.");
                } else {
                    $this->command?->warn("Trade ID {$specificTradeId} already has at least 3 tasks assigned to partner user ID {$partner->getKey()}.");
                }
            } else {
                $this->command?->warn("No partner user found to assign tasks for trade ID {$specificTradeId}.");
            }
        }

        if (!Trade::query()->exists()) {
            $this->command?->warn('No trades found. Skipping TradeTaskSeeder.');
            return;
        }

        if (!User::query()->exists()) {
            $this->command?->warn('No users found. Skipping TradeTaskSeeder.');
            return;
        }

        $faker = fake();

        // Seed tasks for up to 10 most recent trades
        Trade::query()
            ->latest('id')
            ->take(10)
            ->get()
            ->each(function (Trade $trade) use ($faker) {
                // Pick two users: creator and assignee (can be the trade owner and a random user or vice versa)
                $creator = $trade->user ?? User::inRandomOrder()->first();
                $assignee = User::whereKeyNot($creator->getKey())->inRandomOrder()->first() ?: $creator;

                // Create between 2-5 tasks per trade
                $numTasks = random_int(2, 5);
                for ($i = 0; $i < $numTasks; $i++) {
                    $requiresSubmission = (bool)random_int(0, 1);
                    $allowedTypesPool = ['image', 'video', 'pdf', 'word', 'excel'];
                    $allowedFileTypes = $requiresSubmission
                        ? Arr::random($allowedTypesPool, random_int(1, 3))
                        : [];

                    $priority = Arr::random(['low', 'medium', 'high']);

                    TradeTask::create([
                        'trade_id' => $trade->getKey(),
                        'created_by' => $creator->getKey(),
                        'assigned_to' => $assignee->getKey(),
                        'title' => Str::title($faker->unique()->words(random_int(2, 5), true)),
                        'description' => $faker->optional(0.7)->sentence(random_int(8, 18)),
                        'completed' => false,
                        'priority' => $priority,
                        'due_date' => $faker->optional(0.6)->dateTimeBetween('+1 day', '+30 days'),
                        'associated_skills' => $faker->optional(0.4)->randomElements(range(1, 8), random_int(1, 3)),
                        'requires_submission' => $requiresSubmission,
                        'submission_type' => Arr::random(['file', 'text', 'both']),
                        'submission_instructions' => $requiresSubmission ? $faker->sentence(random_int(6, 14)) : null,
                        'max_score' => 100,
                        'passing_score' => 70,
                        'current_status' => 'assigned',
                        'allowed_file_types' => $allowedFileTypes,
                        'strict_file_types' => (bool)random_int(0, 1),
                    ]);
                }
            });

        $this->command?->info('Trade tasks seeded successfully.');
    }

    private function seedTasksForTrade(Trade $trade, User $assignee, int $count = 4): void
    {
        $faker = fake();
        $creator = $trade->user ?? $assignee;
        $allowedTypesPool = ['image', 'video', 'pdf', 'word', 'excel'];

        foreach (range(1, $count) as $i) {
            $requiresSubmission = (bool)random_int(0, 1);
            $allowedFileTypes = $requiresSubmission
                ? Arr::random($allowedTypesPool, random_int(1, 3))
                : [];

            TradeTask::create([
                'trade_id' => $trade->getKey(),
                'created_by' => $creator->getKey(),
                'assigned_to' => $assignee->getKey(),
                'title' => "Demo Task {$i} for Trade {$trade->getKey()}",
                'description' => $faker->optional(0.8)->sentence(random_int(10, 20)),
                'completed' => false,
                'priority' => Arr::random(['low', 'medium', 'high']),
                'due_date' => $faker->optional(0.7)->dateTimeBetween('+1 day', '+21 days'),
                'associated_skills' => $faker->optional(0.5)->randomElements(range(1, 8), random_int(1, 3)),
                'requires_submission' => $requiresSubmission,
                'submission_type' => Arr::random(['file', 'text', 'both']),
                'submission_instructions' => $requiresSubmission ? $faker->sentence(random_int(6, 14)) : null,
                'max_score' => 100,
                'passing_score' => 70,
                'current_status' => 'assigned',
                'allowed_file_types' => $allowedFileTypes,
                'strict_file_types' => (bool)random_int(0, 1),
            ]);
        }
    }
}

