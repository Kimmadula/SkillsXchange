<?php

namespace App\Services;

use App\Models\Trade;
use App\Models\TradeTask;
use App\Models\User;
use App\Models\UserSkill;
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

            // Process skill learning for trade owner (learner of looking_skill)
            if ($tradeOwnerCompletionRate >= 100) {
                $skillAdded = $this->addSkillToUser($tradeOwner, $trade->looking_skill_id);
                $results['trade_owner_skill_added'] = $skillAdded;
                
                if ($skillAdded) {
                    $results['messages'][] = "Added skill '{$trade->lookingSkill->name}' to {$tradeOwner->firstname} {$tradeOwner->lastname}";
                } else {
                    $results['messages'][] = "Skill '{$trade->lookingSkill->name}' already exists for {$tradeOwner->firstname} {$tradeOwner->lastname}";
                }
            } else {
                $results['messages'][] = "Trade owner completion rate ({$tradeOwnerCompletionRate}%) below 100% - no skill added";
            }

            // Process skill learning for requester (learner of offering_skill)
            if ($requesterCompletionRate >= 100) {
                $skillAdded = $this->addSkillToUser($requester, $trade->offering_skill_id);
                $results['requester_skill_added'] = $skillAdded;
                
                if ($skillAdded) {
                    $results['messages'][] = "Added skill '{$trade->offeringSkill->name}' to {$requester->firstname} {$requester->lastname}";
                } else {
                    $results['messages'][] = "Skill '{$trade->offeringSkill->name}' already exists for {$requester->firstname} {$requester->lastname}";
                }
            } else {
                $results['messages'][] = "Requester completion rate ({$requesterCompletionRate}%) below 100% - no skill added";
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
     * Add a skill to a user's profile
     * 
     * @param User $user
     * @param int $skillId
     * @return bool
     */
    private function addSkillToUser(User $user, int $skillId): bool
    {
        try {
            // Check if user already has this skill
            $existingSkill = UserSkill::where('user_id', $user->id)
                ->where('skill_id', $skillId)
                ->first();

            if ($existingSkill) {
                return false; // Skill already exists
            }

            // Add the skill
            UserSkill::create([
                'user_id' => $user->id,
                'skill_id' => $skillId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error adding skill to user: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'skill_id' => $skillId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Check if a trade session is ready for skill learning processing
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

        // Check if all tasks are either completed and verified, or not completed
        $incompleteTasks = $trade->tasks()
            ->where('completed', true)
            ->where('verified', false)
            ->exists();

        // If there are completed but unverified tasks, the session is not ready
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

        $readyForProcessing = $this->isTradeReadyForSkillLearning($trade);

        return [
            'ready_for_processing' => $readyForProcessing,
            'trade_owner' => [
                'user' => $tradeOwner,
                'completion_rate' => $tradeOwnerCompletionRate,
                'skill_to_learn' => $trade->lookingSkill,
                'will_receive_skill' => $tradeOwnerCompletionRate >= 100
            ],
            'requester' => [
                'user' => $requester,
                'completion_rate' => $requesterCompletionRate,
                'skill_to_learn' => $trade->offeringSkill,
                'will_receive_skill' => $requesterCompletionRate >= 100
            ]
        ];
    }
}
