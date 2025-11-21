<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\TradeTask;
use App\Models\TaskSubmission;
use App\Models\TaskEvaluation;
use App\Models\Skill;
use App\Models\User;
use App\Services\TaskSkillService;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Events\TaskDeleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    protected $taskSkillService;

    public function __construct(TaskSkillService $taskSkillService)
    {   
        $this->middleware('auth');
        $this->taskSkillService = $taskSkillService;
    }

    /**
     * Display a listing of all tasks for the authenticated user
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get all tasks where user is either creator or assignee
        $myTasks = TradeTask::where('assigned_to', $user->id)
            ->orWhere('created_by', $user->id)
            ->with(['trade', 'creator', 'assignee', 'verifier', 'latestSubmission', 'latestEvaluation'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get enhanced task statistics
        $stats = [
            'total' => $myTasks->total(),
            'assigned' => TradeTask::where('assigned_to', $user->id)->byStatus('assigned')->count(),
            'in_progress' => TradeTask::where('assigned_to', $user->id)->byStatus('in_progress')->count(),
            'submitted' => TradeTask::where('assigned_to', $user->id)->byStatus('submitted')->count(),
            'completed' => TradeTask::where('assigned_to', $user->id)->byStatus('completed')->count(),
            'overdue' => TradeTask::where('assigned_to', $user->id)->overdue()->count(),
        ];

        // Get skill learning statistics
        $skillStats = $this->taskSkillService->getUserSkillStats($user);

        return view('tasks.index', compact('myTasks', 'stats', 'skillStats'));
    }

    /**
     * Show the form for creating a new task
     */
    public function create()
    {
        $user = Auth::user();
        
        // Get user's active trades
        $activeTrades = Trade::where('user_id', $user->id)
            ->orWhereHas('requests', function($query) use ($user) {
                $query->where('requester_id', $user->id)->where('status', 'accepted');
            })
            ->with(['user', 'requests.requester'])
            ->get();

        // Get available skills for task assignment
        $skills = $this->taskSkillService->getAvailableSkills();
        $skillCategories = $this->taskSkillService->getSkillCategories();

        return view('tasks.create', compact('activeTrades', 'skills', 'skillCategories'));
    }

    /**
     * Store a newly created task
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'trade_id' => 'required|exists:trades,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'assigned_to' => 'required|exists:users,id',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date|after:today',
            'associated_skills' => 'nullable|array',
            'associated_skills.*' => 'exists:skills,skill_id',
            'requires_submission' => 'boolean',
            'submission_type' => 'nullable|in:file,text,both',
            'submission_instructions' => 'nullable|string|max:1000',
            'max_score' => 'nullable|integer|min:1|max:1000',
            'passing_score' => 'nullable|integer|min:1|max:1000',
            'allowed_file_types' => 'nullable|array',
            'allowed_file_types.*' => 'in:image,video,pdf,word,excel',
            'strict_file_types' => 'boolean'
        ]);

        try {
            $trade = Trade::findOrFail($request->trade_id);
            
            // Check if user is part of this trade
            if ($trade->user_id !== $user->id && 
                !$trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()) {
                return redirect()->back()->with('error', 'You are not authorized to create tasks for this trade.');
            }

            // Validate skills if provided
            if ($request->associated_skills) {
                $skillValidation = $this->taskSkillService->validateSkills($request->associated_skills);
                if (!$skillValidation['valid']) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Some selected skills are invalid.');
                }
            }

            $task = $trade->tasks()->create([
                'created_by' => $user->id,
                'assigned_to' => $request->assigned_to,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'medium',
                'due_date' => $request->due_date,
                'associated_skills' => $request->associated_skills,
                'requires_submission' => $request->boolean('requires_submission'),
                'submission_type' => $request->submission_type ?? 'both',
                'submission_instructions' => $request->submission_instructions,
                'max_score' => $request->max_score ?? 100,
                'passing_score' => $request->passing_score ?? 70,
                'allowed_file_types' => $request->allowed_file_types,
                'strict_file_types' => $request->boolean('strict_file_types'),
            ]);

            $task->load(['creator', 'assignee']);

            // Broadcast task created event
            broadcast(new TaskCreated($task, $trade->id));

            return redirect()->route('tasks.show', $task)->with('success', 'Task created successfully!');
            
        } catch (\Exception $e) {
            Log::error('Task creation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create task: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified task
     */
    public function show(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is part of this task's trade
        if ($task->trade->user_id !== $user->id && 
            !$task->trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()) {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'You are not authorized to view this task.'], 403);
            }
            return redirect()->back()->with('error', 'You are not authorized to view this task.');
        }

        $task->load(['trade', 'creator', 'assignee', 'verifier', 'latestEvaluation']);
        
        // If this is an AJAX request, return JSON
        if (request()->wantsJson()) {
            $evaluation = $task->latestEvaluation;
            return response()->json([
                'success' => true,
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date,
                    'requires_submission' => $task->requires_submission,
                    'allowed_file_types' => $task->allowed_file_types,
                    'submission_instructions' => $task->submission_instructions,
                    'current_status' => $task->current_status,
                    'max_score' => $task->max_score,
                    'passing_score' => $task->passing_score,
                    'completed' => $task->completed,
                    'created_at' => $task->created_at,
                    'created_by' => $task->created_by,
                    'assigned_to' => $task->assigned_to,
                    'can_be_submitted' => $task->canBeSubmitted() && $task->assigned_to === $user->id,
                    'can_be_started' => $task->canBeStarted() && $task->assigned_to === $user->id,
                    'can_be_graded' => $task->canBeEvaluated() && $task->created_by === $user->id,
                    'has_submission' => $task->latestSubmission !== null,
                    'creator' => $task->creator ? [
                        'firstname' => $task->creator->firstname,
                        'lastname' => $task->creator->lastname
                    ] : null,
                    'assignee' => $task->assignee ? [
                        'firstname' => $task->assignee->firstname,
                        'lastname' => $task->assignee->lastname
                    ] : null,
                    'evaluation' => $evaluation ? [
                        'score_percentage' => $evaluation->score_percentage,
                        'grade' => $evaluation->grade_letter,
                        'status' => $evaluation->status,
                        'feedback' => $evaluation->feedback,
                        'checked_at' => $evaluation->checked_at ? $evaluation->checked_at->toISOString() : null,
                        'has_been_graded' => $evaluation->hasBeenGraded()
                    ] : null
                ]
            ]);
        }
        
        return view('tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the specified task
     */
    public function edit(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is the creator of this task
        if ($task->created_by !== $user->id) {
            return redirect()->back()->with('error', 'You can only edit tasks you created.');
        }

        $task->load(['trade', 'assignee']);
        
        // Get available skills for task association
        $skills = $this->taskSkillService->getAvailableSkills();
        
        return view('tasks.edit', compact('task', 'skills'));
    }

    /**
     * Update the specified task
     */
    public function update(Request $request, TradeTask $task)
    {
        $user = Auth::user();
        
        // Debug logging
        Log::info('Task update request received', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'request_data' => $request->all(),
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson()
        ]);
        
        // Check if user is the creator of this task
        if ($task->created_by !== $user->id) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'You can only edit tasks you created.'], 403);
            }
            return redirect()->back()->with('error', 'You can only edit tasks you created.');
        }

        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'priority' => 'nullable|in:low,medium,high',
                'due_date' => 'nullable|date|after_or_equal:today|sometimes',
                'associated_skills' => 'nullable|array',
                'associated_skills.*' => 'exists:skills,skill_id',
                'requires_submission' => 'boolean',
                'submission_instructions' => 'nullable|string|max:1000',
                'max_score' => 'nullable|integer|min:1|max:1000|sometimes',
                'passing_score' => 'nullable|integer|min:1|max:1000|sometimes',
                'allowed_file_types' => 'nullable|array',
                'allowed_file_types.*' => 'in:image,video,pdf,word,excel',
                'strict_file_types' => 'boolean'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Task update validation failed', [
                'task_id' => $task->id,
                'user_id' => $user->id,
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }

        try {
            // Validate skills if provided
            if ($request->associated_skills) {
                $skillValidation = $this->taskSkillService->validateSkills($request->associated_skills);
                if (!$skillValidation['valid']) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Some selected skills are invalid.');
                }
            }

            $task->update([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'medium',
                'due_date' => $request->due_date,
                'associated_skills' => $request->associated_skills,
                'requires_submission' => $request->boolean('requires_submission'),
                'submission_instructions' => $request->submission_instructions,
                'max_score' => $request->max_score ?? 100,
                'passing_score' => $request->passing_score ?? 70,
                'allowed_file_types' => $request->allowed_file_types,
                'strict_file_types' => $request->boolean('strict_file_types'),
            ]);

            // Broadcast task updated event
            broadcast(new TaskUpdated($task, $task->trade_id));

            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Task updated successfully!',
                    'task' => $task->fresh()
                ]);
            }
            
            return redirect()->route('tasks.show', $task)->with('success', 'Task updated successfully!');
            
        } catch (\Exception $e) {
            Log::error('Task update error: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update task: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to update task: ' . $e->getMessage());
        }
    }

    /**
     * Toggle task completion status
     */
    public function toggle(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is assigned to this task
        if ($task->assigned_to !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $task->update([
                'completed' => !$task->completed,
                'completed_at' => !$task->completed ? now() : null,
                'verified' => $task->completed ? false : $task->verified,
                'verified_at' => $task->completed ? null : $task->verified_at,
                'verified_by' => $task->completed ? null : $task->verified_by
            ]);

            // Broadcast task updated event
            broadcast(new TaskUpdated($task, $task->trade_id));

            return response()->json([
                'success' => true,
                'task' => $task->load(['creator', 'assignee', 'verifier'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Task toggle error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update task'], 500);
        }
    }

    /**
     * Remove the specified task
     */
    public function destroy(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is the creator of this task
        if ($task->created_by !== $user->id) {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'You can only delete tasks you created.'], 403);
            }
            return redirect()->back()->with('error', 'You can only delete tasks you created.');
        }

        try {
            // Store task info before deletion for broadcasting
            $taskId = $task->id;
            $tradeId = $task->trade_id;
            $creatorName = $task->creator->firstname . ' ' . $task->creator->lastname;
            $assigneeName = $task->assignee->firstname . ' ' . $task->assignee->lastname;
            
            // Delete related submissions and evaluations first
            $task->submissions()->delete();
            $task->evaluations()->delete();
            
            // Delete the task
            $task->delete();
            
            // Broadcast task deleted event
            broadcast(new TaskDeleted($taskId, $tradeId, $creatorName, $assigneeName));
            
            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Task deleted successfully!']);
            }
            
            return redirect()->route('tasks.index')->with('success', 'Task deleted successfully!');
            
        } catch (\Exception $e) {
            Log::error('Task deletion error: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            if (request()->wantsJson()) {
                return response()->json(['error' => 'Failed to delete task: ' . $e->getMessage()], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to delete task: ' . $e->getMessage());
        }
    }

    /**
     * Start working on a task
     */
    public function startTask(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is assigned to this task
        if ($task->assigned_to !== $user->id) {
            return redirect()->back()->with('error', 'You can only start tasks assigned to you.');
        }

        if (!$task->canBeStarted()) {
            return redirect()->back()->with('error', 'This task cannot be started at this time.');
        }

        try {
            $task->updateStatus('in_progress');
            
            // Broadcast task updated event
            broadcast(new TaskUpdated($task, $task->trade_id));
            
            return redirect()->back()->with('success', 'Task started successfully!');
            
        } catch (\Exception $e) {
            Log::error('Task start error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to start task: ' . $e->getMessage());
        }
    }

    /**
     * Submit task work
     */
    public function submitTask(Request $request, TradeTask $task)
    {
        $user = Auth::user();
        
        // Debug logging
        Log::info('Task submission request', [
            'user_id' => $user ? $user->id : 'not authenticated',
            'task_id' => $task->id,
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'headers' => $request->headers->all(),
            'method' => $request->method()
        ]);
        
        // Check if user is assigned to this task
        if ($task->assigned_to !== $user->id) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'You can only submit tasks assigned to you.'
                ], 403);
            }
            return redirect()->back()->with('error', 'You can only submit tasks assigned to you.');
        }

        if (!$task->canBeSubmitted()) {
            $debugInfo = [
                'current_status' => $task->current_status,
                'requires_submission' => $task->requires_submission,
                'can_be_submitted' => $task->canBeSubmitted(),
                'task_id' => $task->id
            ];
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'This task cannot be submitted at this time.',
                    'debug' => $debugInfo
                ], 400);
            }
            return redirect()->back()->with('error', 'This task cannot be submitted at this time.');
        }

        // Dynamic validation based on task requirements
        $validationRules = [
            'submission_notes' => 'nullable|string|max:2000',
            'files' => 'nullable|array|max:10'
        ];

        if ($task->hasAllowedFileTypes()) {
            $validationRules['files.*'] = $task->getFileTypeValidationRules();
        } else {
            $validationRules['files.*'] = 'file|max:50000|mimes:jpg,jpeg,png,gif,pdf,doc,docx,mp4,mov,avi,xls,xlsx';
        }

        try {
            $request->validate($validationRules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }

        try {
            $filePaths = [];
            $fileTypes = [];

            // Handle file uploads
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('task_submissions/' . $task->id, 'public');
                    $filePaths[] = $path;
                    
                    // Determine file type
                    $mimeType = $file->getMimeType();
                    if (str_starts_with($mimeType, 'image/')) {
                        $fileTypes[] = 'image';
                    } elseif (str_starts_with($mimeType, 'video/')) {
                        $fileTypes[] = 'video';
                    } else {
                        $fileTypes[] = 'document';
                    }
                }
            }

            // Create submission
            $submission = TaskSubmission::create([
                'task_id' => $task->id,
                'submitted_by' => $user->id,
                'submission_notes' => $request->submission_notes,
                'file_paths' => $filePaths,
                'file_types' => count(array_unique($fileTypes)) > 1 ? 'mixed' : ($fileTypes[0] ?? 'mixed')
            ]);

            // Update task status
            $task->updateStatus('submitted');

            // Broadcast task updated event
            broadcast(new TaskUpdated($task, $task->trade_id));

            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Task submitted successfully!',
                    'task' => $task->fresh()
                ]);
            }
            
            return redirect()->route('tasks.show', $task)->with('success', 'Task submitted successfully!');
            
        } catch (\Exception $e) {
            Log::error('Task submission error: ' . $e->getMessage());
            
            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to submit task: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to submit task: ' . $e->getMessage());
        }
    }

    /**
     * Show evaluation form for a task
     */
    public function showEvaluationForm(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is the creator of this task
        if ($task->created_by !== $user->id) {
            return redirect()->back()->with('error', 'You can only evaluate tasks you created.');
        }

        if (!$task->canBeEvaluated()) {
            return redirect()->back()->with('error', 'This task is not ready for evaluation.');
        }

        $task->load(['latestSubmission.submitter', 'assignee']);
        $learnableSkills = $this->taskSkillService->getLearnableSkills($task->assignee, $task);

        return view('tasks.evaluate', compact('task', 'learnableSkills'));
    }

    /**
     * Store task evaluation
     */
    public function storeEvaluation(Request $request, TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is the creator of this task
        if ($task->created_by !== $user->id) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'You can only evaluate tasks you created.'], 403);
            }
            return redirect()->back()->with('error', 'You can only evaluate tasks you created.');
        }

        if (!$task->canBeEvaluated()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'This task is not ready for evaluation.'], 400);
            }
            return redirect()->back()->with('error', 'This task is not ready for evaluation.');
        }

        try {
            $request->validate([
                'score_percentage' => 'required|integer|min:0|max:100', // Percentage is always 0-100
                'status' => 'required|in:pass,fail,needs_improvement',
                'feedback' => 'nullable|string|max:2000',
                'improvement_notes' => 'nullable|string|max:2000'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }

        try {
            $latestSubmission = $task->latestSubmission;
            
            // Check if submission has been viewed first
            $existingEvaluation = $task->latestEvaluation;
            if (!$existingEvaluation || !$existingEvaluation->hasBeenViewed()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'You must view the submission before grading it.',
                        'requires_viewing' => true
                    ], 400);
                }
                return redirect()->back()->with('error', 'You must view the submission before grading it.');
            }
            
            // Determine pass/fail based on score
            // Convert passing_score to percentage for comparison
            $passingPercentage = $task->max_score > 0 ? round(($task->passing_score / $task->max_score) * 100) : 70;
            $status = $request->score_percentage >= $passingPercentage ? 'pass' : 'fail';
            if ($request->status === 'needs_improvement') {
                $status = 'needs_improvement';
            }

            // Calculate grade from score
            $grade = $this->calculateGradeFromScore($request->score_percentage);

            // Update existing evaluation or create new one
            if ($existingEvaluation && $existingEvaluation->status === 'pending') {
                $evaluation = $existingEvaluation;
                $evaluation->update([
                    'score_percentage' => $request->score_percentage,
                    'grade' => $grade,
                    'status' => $status,
                    'feedback' => $request->feedback,
                    'improvement_notes' => $request->improvement_notes,
                    'evaluated_at' => now() // Set evaluated_at when grading
                ]);
            } else {
                // Create new evaluation
                $evaluation = TaskEvaluation::create([
                    'task_id' => $task->id,
                    'submission_id' => $latestSubmission?->id,
                    'evaluated_by' => $user->id,
                    'score_percentage' => $request->score_percentage,
                    'grade' => $grade,
                    'status' => $status,
                    'feedback' => $request->feedback,
                    'improvement_notes' => $request->improvement_notes,
                    'viewed_at' => $existingEvaluation?->viewed_at ?? now(),
                    'evaluated_at' => now()
                ]);
            }

            // Auto-assign skills if task has associated skills
            if ($task->hasAssociatedSkills()) {
                $this->taskSkillService->autoAssignTaskSkills($evaluation);
            }

            // Update task status
            $newTaskStatus = $status === 'pass' ? 'completed' : 'evaluated';
            $task->updateStatus($newTaskStatus);

            // Auto-verify task if it passed evaluation (for skill learning)
            if ($status === 'pass') {
                $task->update([
                    'verified' => true,
                    'verified_at' => now(),
                    'verified_by' => $user->id
                ]);
            }

            // Broadcast task updated event
            broadcast(new TaskUpdated($task, $task->trade_id));

            $message = $status === 'pass' ? 'Task evaluated, marked as passed, and verified for skill learning!' : 'Task evaluation completed.';
            
            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'task' => $task->fresh()
                ]);
            }
            
            return redirect()->route('tasks.show', $task)->with('success', $message);
            
        } catch (\Exception $e) {
            Log::error('Task evaluation error: ' . $e->getMessage());
            
            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to evaluate task: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to evaluate task: ' . $e->getMessage());
        }
    }

    /**
     * Download submission file
     */
    public function downloadSubmissionFile(TaskSubmission $submission, $fileIndex)
    {
        $user = Auth::user();
        $task = $submission->task;
        
        // Check if user is part of this task
        if ($task->assigned_to !== $user->id && $task->created_by !== $user->id) {
            abort(403, 'Unauthorized access to submission file.');
        }

        $filePaths = $submission->file_paths;
        if (!isset($filePaths[$fileIndex])) {
            abort(404, 'File not found.');
        }

        $filePath = $filePaths[$fileIndex];
        if (!Storage::exists($filePath)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::download($filePath);
    }

    /**
     * Get submission details for review (AJAX)
     */
    public function getSubmissionDetails(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is the task creator
        if ($task->created_by !== $user->id) {
            return response()->json(['error' => 'You can only review submissions for tasks you created.'], 403);
        }

        try {
            // Get the latest submission
            $submission = $task->latestSubmission()->with('submitter')->first();
            
            if (!$submission) {
                return response()->json(['error' => 'No submission found for this task.'], 404);
            }

            // Get existing evaluation if any
            $evaluation = $task->latestEvaluation;
            $hasBeenViewed = $evaluation && $evaluation->hasBeenViewed();
            $hasBeenGraded = $evaluation && $evaluation->hasBeenGraded();

            return response()->json([
                'success' => true,
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'submission_instructions' => $task->submission_instructions,
                    'allowed_file_types' => $task->allowed_file_types,
                    'max_score' => $task->max_score,
                    'passing_score' => $task->passing_score
                ],
                'submission' => [
                    'id' => $submission->id,
                    'submitter_name' => $submission->submitter->firstname . ' ' . $submission->submitter->lastname,
                    'submission_notes' => $submission->submission_notes,
                    'file_paths' => $submission->file_paths,
                    'created_at' => $submission->created_at->toISOString()
                ],
                'evaluation' => $evaluation ? [
                    'id' => $evaluation->id,
                    'score_percentage' => $evaluation->score_percentage,
                    'grade' => $evaluation->grade_letter,
                    'status' => $evaluation->status,
                    'feedback' => $evaluation->feedback,
                    'improvement_notes' => $evaluation->improvement_notes,
                    'checked_at' => $evaluation->checked_at ? $evaluation->checked_at->toISOString() : null,
                    'has_been_viewed' => $hasBeenViewed,
                    'has_been_graded' => $hasBeenGraded
                ] : null,
                'can_grade' => $hasBeenViewed || !$evaluation, // Can grade if viewed or no evaluation exists
                'has_been_viewed' => $hasBeenViewed,
                'has_been_graded' => $hasBeenGraded
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get submission details error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load submission details.'], 500);
        }
    }
    
    /**
     * Calculate letter grade from score percentage (0-100)
     */
    private function calculateGradeFromScore($percentage)
    {
        return match(true) {
            $percentage >= 95 => 'A+',
            $percentage >= 90 => 'A',
            $percentage >= 85 => 'A-',
            $percentage >= 80 => 'B+',
            $percentage >= 75 => 'B',
            $percentage >= 70 => 'B-',
            $percentage >= 65 => 'C+',
            $percentage >= 60 => 'C',
            $percentage >= 55 => 'C-',
            $percentage >= 50 => 'D',
            default => 'F'
        };
    }
    
    /**
     * Mark submission as viewed by evaluator (AJAX)
     */
    public function markSubmissionViewed(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is the task creator
        if ($task->created_by !== $user->id) {
            return response()->json(['error' => 'You can only view submissions for tasks you created.'], 403);
        }

        try {
            // Get the latest submission
            $submission = $task->latestSubmission;
            
            if (!$submission) {
                return response()->json(['error' => 'No submission found for this task.'], 404);
            }

            // Get or create evaluation
            $evaluation = $task->latestEvaluation;
            
            if (!$evaluation) {
                // Create a pending evaluation record to track viewing
                $evaluation = TaskEvaluation::create([
                    'task_id' => $task->id,
                    'submission_id' => $submission->id,
                    'evaluated_by' => $user->id,
                    'status' => 'pending',
                    'viewed_at' => now()
                ]);
            } else {
                // Update existing evaluation with viewed_at
                if (!$evaluation->viewed_at) {
                    $evaluation->update(['viewed_at' => now()]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Submission marked as viewed. You can now grade this task.',
                'viewed_at' => $evaluation->viewed_at->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mark submission viewed error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to mark submission as viewed.'], 500);
        }
    }

    /**
     * Get task progress data for AJAX
     */
    public function getTaskProgress(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is part of this task
        if ($task->assigned_to !== $user->id && $task->created_by !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $task->load(['latestSubmission', 'latestEvaluation']);

        return response()->json([
            'success' => true,
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'current_status' => $task->current_status,
                'status_color' => $task->status_color,
                'status_icon' => $task->status_icon,
                'can_be_started' => $task->canBeStarted(),
                    'can_be_submitted' => $task->canBeSubmitted(),
                    'can_be_evaluated' => $task->canBeEvaluated(),
                    'is_completed' => $task->isCompleted(),
                    'latest_submission' => $task->latestSubmission,
                    'latest_evaluation' => $task->latestEvaluation ? [
                        'id' => $task->latestEvaluation->id,
                        'score_percentage' => $task->latestEvaluation->score_percentage,
                        'grade' => $task->latestEvaluation->grade_letter,
                        'status' => $task->latestEvaluation->status,
                        'feedback' => $task->latestEvaluation->feedback,
                        'checked_at' => $task->latestEvaluation->checked_at ? $task->latestEvaluation->checked_at->toISOString() : null,
                        'has_been_viewed' => $task->latestEvaluation->hasBeenViewed(),
                        'has_been_graded' => $task->latestEvaluation->hasBeenGraded()
                    ] : null,
                    'days_until_due' => $task->days_until_due,
                    'is_overdue' => $task->is_overdue
                ]
            ]);
        }
    }
