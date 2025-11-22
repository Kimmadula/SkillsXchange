<?php

namespace App\Services;

use App\Models\Trade;
use App\Models\TradeTask;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\SkillAcquisitionHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SkillLearningService
{
    /**
     * Process skill learning for a completed trade session
     * 
     * @param Trade $trade
     * @return array
     */
    public function processSkillLearning(Trade $trade): array
    {
        $results = [
            'trade_owner_skill_added' => false,
            'requester_skill_added' => false,
            'trade_owner_completion_rate' => 0,
            'requester_completion_rate' => 0,
            'messages' => []
        ];

        try {
            // Get the accepted request for this trade
            $acceptedRequest = $trade->requests()->where('status', 'accepted')->first();
            
            if (!$acceptedRequest) {
                $results['messages'][] = 'No accepted request found for this trade';
                return $results;
            }

            $tradeOwner = $trade->user;
            $requester = $acceptedRequest->requester;

            // Calculate completion rates for both users
            $tradeOwnerCompletionRate = $this->calculateCompletionRate($trade, $tradeOwner->id);
            $requesterCompletionRate = $this->calculateCompletionRate($trade, $requester->id);

            $results['trade_owner_completion_rate'] = $tradeOwnerCompletionRate;
            $results['requester_completion_rate'] = $requesterCompletionRate;

            // Check if all tasks passed for trade owner
            $tradeOwnerAllPassed = $this->allTasksPassed($trade, $tradeOwner->id);
            
            // Process skill learning for trade owner (learner of looking_skill)
            // Skills can only be acquired if ALL tasks have 'pass' status
            if ($tradeOwnerAllPassed) {
                $skillAdded = $this->addSkillToUser($tradeOwner, $trade->looking_skill_id, $trade, $tradeOwnerCompletionRate);
                $results['trade_owner_skill_added'] = $skillAdded;
                
                if ($skillAdded) {
                    $results['messages'][] = "Added skill '{$trade->lookingSkill->name}' to {$tradeOwner->firstname} {$tradeOwner->lastname}";
                } else {
                    $results['messages'][] = "Skill '{$trade->lookingSkill->name}' already exists for {$tradeOwner->firstname} {$tradeOwner->lastname}";
                }
            } else {
                $results['messages'][] = "Trade owner did not pass all tasks - no skill added. All tasks must have 'pass' status to acquire skills.";
            }

            // Check if all tasks passed for requester
            $requesterAllPassed = $this->allTasksPassed($trade, $requester->id);
            
            // Process skill learning for requester (learner of offering_skill)
            // Skills can only be acquired if ALL tasks have 'pass' status
            if ($requesterAllPassed) {
                $skillAdded = $this->addSkillToUser($requester, $trade->offering_skill_id, $trade, $requesterCompletionRate);
                $results['requester_skill_added'] = $skillAdded;
                
                if ($skillAdded) {
                    $results['messages'][] = "Added skill '{$trade->offeringSkill->name}' to {$requester->firstname} {$requester->lastname}";
                } else {
                    $results['messages'][] = "Skill '{$trade->offeringSkill->name}' already exists for {$requester->firstname} {$requester->lastname}";
                }
            } else {
                $results['messages'][] = "Requester did not pass all tasks - no skill added. All tasks must have 'pass' status to acquire skills.";
            }

            Log::info('Skill learning processed', [
                'trade_id' => $trade->id,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing skill learning: ' . $e->getMessage(), [
                'trade_id' => $trade->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $results['messages'][] = 'Error processing skill learning: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Calculate completion rate for a user in a trade
     * 
     * @param Trade $trade
     * @param int $userId
     * @return float
     */
    private function calculateCompletionRate(Trade $trade, int $userId): float
    {
        // Get all tasks assigned to this user in this trade
        $userTasks = $trade->tasks()->where('assigned_to', $userId)->get();
        
        if ($userTasks->isEmpty()) {
            return 0.0;
        }

        // Count completed and verified tasks
        $completedTasks = $userTasks->where('completed', true)->where('verified', true);
        
        $completionRate = ($completedTasks->count() / $userTasks->count()) * 100;
        
        return round($completionRate, 2);
    }

    /**
     * Check if all tasks assigned to a user have passed status
     * 
     * @param Trade $trade
     * @param int $userId
     * @return bool
     */
    private function allTasksPassed(Trade $trade, int $userId): bool
    {
        // Get all tasks assigned to this user in this trade
        $userTasks = $trade->tasks()->where('assigned_to', $userId)->get();
        
        if ($userTasks->isEmpty()) {
            return false; // No tasks means can't pass
        }

        // Check if all tasks have evaluations with 'pass' status
        foreach ($userTasks as $task) {
            $evaluation = $task->latestEvaluation;
            
            // If task has no evaluation, it hasn't passed
            if (!$evaluation) {
                return false;
            }
            
            // If evaluation status is not 'pass', return false
            if ($evaluation->status !== 'pass') {
                return false;
            }
        }
        
        return true; // All tasks have passed
    }

    /**
     * Add a skill to a user's profile
     * 
     * @param User $user
     * @param int $skillId
     * @param Trade $trade
     * @param int $completionRate
     * @return bool
     */
    private function addSkillToUser(User $user, int $skillId, Trade $trade = null, int $completionRate = 100): bool
    {
        try {
            // Check if user already has this skill
            $existingSkill = UserSkill::where('user_id', $user->id)
                ->where('skill_id', $skillId)
                ->first();

            if ($existingSkill) {
                return false; // Skill already exists
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                // Add the skill to user_skills table
                UserSkill::create([
                    'user_id' => $user->id,
                    'skill_id' => $skillId
                ]);

                // Record skill acquisition in history
                SkillAcquisitionHistory::create([
                    'user_id' => $user->id,
                    'skill_id' => $skillId,
                    'trade_id' => $trade ? $trade->id : null,
                    'acquisition_method' => 'trade_completion',
                    'score_achieved' => $completionRate,
                    'notes' => $trade ? "Acquired through trade completion (ID: {$trade->id})" : 'Acquired through trade completion',
                    'acquired_at' => now()
                ]);

                DB::commit();
                return true;

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error adding skill to user: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'skill_id' => $skillId,
                'trade_id' => $trade ? $trade->id : null,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Check if a trade session is ready for skill learning processing
     * Note: Session can be ended even if tasks don't pass, but skills won't be awarded
     * 
     * @param Trade $trade
     * @return bool
     */
    public function isTradeReadyForSkillLearning(Trade $trade): bool
    {
        // Check if trade has an accepted request
        $acceptedRequest = $trade->requests()->where('status', 'accepted')->first();
        if (!$acceptedRequest) {
            return false;
        }

        // Check if trade has tasks
        $hasTasks = $trade->tasks()->exists();
        if (!$hasTasks) {
            return false;
        }

        // Check if all tasks are completed (evaluated)
        // Allow session to end even if tasks don't pass
        $incompleteTasks = $trade->tasks()
            ->whereNotIn('current_status', ['evaluated', 'completed'])
            ->exists();

        // If there are incomplete tasks, the session is not ready
        return !$incompleteTasks;
    }

    /**
     * Get skill learning summary for a trade
     * 
     * @param Trade $trade
     * @return array
     */
    public function getSkillLearningSummary(Trade $trade): array
    {
        $acceptedRequest = $trade->requests()->where('status', 'accepted')->first();
        
        if (!$acceptedRequest) {
            return [
                'ready_for_processing' => false,
                'message' => 'No accepted request found'
            ];
        }

        $tradeOwner = $trade->user;
        $requester = $acceptedRequest->requester;

        $tradeOwnerCompletionRate = $this->calculateCompletionRate($trade, $tradeOwner->id);
        $requesterCompletionRate = $this->calculateCompletionRate($trade, $requester->id);
        
        // Check if all tasks passed for each user
        $tradeOwnerAllPassed = $this->allTasksPassed($trade, $tradeOwner->id);
        $requesterAllPassed = $this->allTasksPassed($trade, $requester->id);

        $readyForProcessing = $this->isTradeReadyForSkillLearning($trade);

        return [
            'ready_for_processing' => $readyForProcessing,
            'trade_owner' => [
                'user' => $tradeOwner,
                'completion_rate' => $tradeOwnerCompletionRate,
                'skill_to_learn' => $trade->lookingSkill,
                'will_receive_skill' => $tradeOwnerAllPassed, // Only if all tasks passed
                'all_tasks_passed' => $tradeOwnerAllPassed
            ],
            'requester' => [
                'user' => $requester,
                'completion_rate' => $requesterCompletionRate,
                'skill_to_learn' => $trade->offeringSkill,
                'will_receive_skill' => $requesterAllPassed, // Only if all tasks passed
                'all_tasks_passed' => $requesterAllPassed
            ]
        ];
    }
}
