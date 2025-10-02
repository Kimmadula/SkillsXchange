<?php

namespace App\Services;

use App\Models\User;
use App\Models\Skill;
use App\Models\TradeTask;
use App\Models\TaskEvaluation;
use App\Models\UserSkill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskSkillService
{
    /**
     * Add skills to user's profile when task is passed
     *
     * @param TaskEvaluation $evaluation
     * @return array
     */
    public function addSkillsToUser(TaskEvaluation $evaluation)
    {
        if (!$evaluation->isPassed() || $evaluation->skills_added || !$evaluation->hasSkillsToAdd()) {
            return [
                'success' => false,
                'message' => 'Skills cannot be added at this time',
                'skills_added' => []
            ];
        }

        $user = $evaluation->task->assignee;
        $skillsToAdd = $evaluation->skills_to_add;
        $addedSkills = [];
        $skippedSkills = [];

        DB::beginTransaction();
        
        try {
            foreach ($skillsToAdd as $skillId) {
                // Check if skill exists
                $skill = Skill::where('skill_id', $skillId)->first();
                if (!$skill) {
                    Log::warning("Skill not found: {$skillId}");
                    continue;
                }

                // Check if user already has this skill
                $existingUserSkill = UserSkill::where('user_id', $user->id)
                    ->where('skill_id', $skillId)
                    ->first();

                if ($existingUserSkill) {
                    $skippedSkills[] = [
                        'skill_id' => $skillId,
                        'name' => $skill->name,
                        'reason' => 'Already possessed'
                    ];
                    continue;
                }

                // Add skill to user
                UserSkill::create([
                    'user_id' => $user->id,
                    'skill_id' => $skillId
                ]);

                $addedSkills[] = [
                    'skill_id' => $skillId,
                    'name' => $skill->name
                ];

                Log::info("Skill added to user", [
                    'user_id' => $user->id,
                    'skill_id' => $skillId,
                    'skill_name' => $skill->name,
                    'task_id' => $evaluation->task_id
                ]);
            }

            // Mark skills as added in evaluation
            $evaluation->update(['skills_added' => true]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Skills successfully added to user profile',
                'skills_added' => $addedSkills,
                'skills_skipped' => $skippedSkills,
                'total_added' => count($addedSkills)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding skills to user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'evaluation_id' => $evaluation->id,
                'skills' => $skillsToAdd
            ]);

            return [
                'success' => false,
                'message' => 'Failed to add skills: ' . $e->getMessage(),
                'skills_added' => []
            ];
        }
    }

    /**
     * Get available skills for task assignment
     *
     * @param string $category Optional category filter
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableSkills($category = null)
    {
        $query = Skill::orderBy('name');
        
        if ($category) {
            $query->where('category', $category);
        }
        
        return $query->get();
    }

    /**
     * Get skill categories
     *
     * @return array
     */
    public function getSkillCategories()
    {
        return Skill::distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Validate skills exist
     *
     * @param array $skillIds
     * @return array
     */
    public function validateSkills(array $skillIds)
    {
        $existingSkills = Skill::whereIn('skill_id', $skillIds)->get();
        $existingSkillIds = $existingSkills->pluck('skill_id')->toArray();
        $invalidSkillIds = array_diff($skillIds, $existingSkillIds);

        return [
            'valid' => empty($invalidSkillIds),
            'existing_skills' => $existingSkills,
            'invalid_skill_ids' => $invalidSkillIds
        ];
    }

    /**
     * Get user's current skills
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserSkills(User $user)
    {
        return $user->skills()->orderBy('name')->get();
    }

    /**
     * Get skills that can be learned from a task
     *
     * @param TradeTask $task
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTaskSkills(TradeTask $task)
    {
        if (!$task->hasAssociatedSkills()) {
            return collect();
        }

        return Skill::whereIn('skill_id', $task->associated_skills)
            ->orderBy('name')
            ->get();
    }

    /**
     * Check if user can learn new skills from task
     *
     * @param User $user
     * @param TradeTask $task
     * @return array
     */
    public function getLearnableSkills(User $user, TradeTask $task)
    {
        if (!$task->hasAssociatedSkills()) {
            return [
                'can_learn' => false,
                'skills' => collect(),
                'already_has' => collect()
            ];
        }

        $taskSkills = $this->getTaskSkills($task);
        $userSkillIds = $user->skills()->pluck('skill_id')->toArray();
        
        $learnableSkills = $taskSkills->reject(function ($skill) use ($userSkillIds) {
            return in_array($skill->skill_id, $userSkillIds);
        });

        $alreadyHasSkills = $taskSkills->filter(function ($skill) use ($userSkillIds) {
            return in_array($skill->skill_id, $userSkillIds);
        });

        return [
            'can_learn' => $learnableSkills->isNotEmpty(),
            'skills' => $learnableSkills,
            'already_has' => $alreadyHasSkills
        ];
    }

    /**
     * Auto-assign skills when task is completed successfully
     *
     * @param TaskEvaluation $evaluation
     * @return array
     */
    public function autoAssignTaskSkills(TaskEvaluation $evaluation)
    {
        $task = $evaluation->task;
        
        if (!$task->hasAssociatedSkills()) {
            return [
                'success' => false,
                'message' => 'No skills associated with this task'
            ];
        }

        // Set skills to add from task's associated skills
        $evaluation->update([
            'skills_to_add' => $task->associated_skills
        ]);

        // Add skills to user if evaluation passed
        if ($evaluation->isPassed()) {
            return $this->addSkillsToUser($evaluation);
        }

        return [
            'success' => true,
            'message' => 'Skills prepared for addition when task is passed'
        ];
    }

    /**
     * Get skill learning statistics for a user
     *
     * @param User $user
     * @return array
     */
    public function getUserSkillStats(User $user)
    {
        $totalSkills = Skill::count();
        $userSkills = $user->skills()->count();
        $completedTasks = TradeTask::where('assigned_to', $user->id)
            ->where('current_status', 'completed')
            ->count();
        
        $passedEvaluations = TaskEvaluation::whereHas('task', function ($query) use ($user) {
            $query->where('assigned_to', $user->id);
        })->where('status', 'pass')->count();

        return [
            'total_skills_available' => $totalSkills,
            'skills_acquired' => $userSkills,
            'skill_completion_percentage' => $totalSkills > 0 ? round(($userSkills / $totalSkills) * 100, 2) : 0,
            'completed_tasks' => $completedTasks,
            'passed_evaluations' => $passedEvaluations,
            'success_rate' => $completedTasks > 0 ? round(($passedEvaluations / $completedTasks) * 100, 2) : 0
        ];
    }
}
