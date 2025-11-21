<aside class="tasks-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-title-container">
            <span class="sidebar-icon">☑️</span>
            <h2 class="sidebar-title">Session Tasks</h2>
        </div>
        <div class="tabs-container">
            <button class="tab-btn active" data-tab="my-tasks">My Tasks ({{ $myTasks->count() }})</button>
            <button class="tab-btn" data-tab="partner-tasks">Partner ({{ $partnerTasks->count() }})</button>
        </div>
    </div>

    <div class="tasks-content">
        <!-- My Tasks Tab -->
        <div class="tab-content" data-content="my-tasks">
            @php
                $myProgressValue = max(0, min(100, round($myProgress ?? 0)));
            @endphp
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-label">Progress</span>
                    <span class="progress-percentage">{{ $myProgressValue }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="--progress-width: {{ $myProgressValue }}%; width: var(--progress-width);"></div>
                </div>
            </div>
            
            <div class="task-list" id="my-tasks">
                @foreach($myTasks as $task)
                    <div class="task-item" data-task-id="{{ $task->id }}" onclick="showTaskDetails({{ $task->id }})" style="cursor: pointer;">
                        <div class="task-header">
                            <input type="checkbox" class="task-checkbox" 
                                {{ $task->completed ? 'checked' : '' }}
                                onclick="event.stopPropagation(); toggleTask({{ $task->id }})">
                            <div class="task-content">
                                <div class="task-title">{{ $task->title }}</div>
                                <div class="task-meta">
                                    @if($task->priority)
                                        <span class="task-tag tag-priority-{{ $task->priority }}">
                                            {{ ucfirst($task->priority) }} Priority
                                        </span>
                                    @endif
                                    @if($task->current_status)
                                        <span class="task-tag tag-status">
                                            {{ ucfirst(str_replace('_', ' ', $task->current_status)) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if(Auth::id() === $task->created_by)
                                <div class="task-actions" style="display:flex; gap:6px; margin-left:auto;" onclick="event.stopPropagation()">
                                    <button onclick="editTask({{ $task->id }})" title="Edit Task"
                                            style="background:#3b82f6;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Edit
                                    </button>
                                    <button onclick="deleteTask({{ $task->id }})" title="Delete Task"
                                            style="background:#ef4444;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Delete
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Partner Tasks Tab -->
        <div class="tab-content" data-content="partner-tasks" style="display: none;">
            @php
                $partnerProgressValue = max(0, min(100, round($partnerProgress ?? 0)));
            @endphp
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-label">Progress</span>
                    <span class="progress-percentage">{{ $partnerProgressValue }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="--progress-width: {{ $partnerProgressValue }}%; width: var(--progress-width);"></div>
                </div>
            </div>
            
            <div class="task-list" id="partner-tasks">
                @foreach($partnerTasks as $task)
                    <div class="task-item" data-task-id="{{ $task->id }}" onclick="showTaskDetails({{ $task->id }})" style="cursor: pointer;">
                        <div class="task-header">
                            <input type="checkbox" class="task-checkbox" 
                                {{ $task->completed ? 'checked' : '' }}
                                disabled
                                onclick="event.stopPropagation()">
                            <div class="task-content">
                                <div class="task-title">{{ $task->title }}</div>
                                <div class="task-meta">
                                    @if($task->priority)
                                        <span class="task-tag tag-priority-{{ $task->priority }}">
                                            {{ ucfirst($task->priority) }} Priority
                                        </span>
                                    @endif
                                    @if($task->current_status)
                                        <span class="task-tag tag-status">
                                            {{ ucfirst(str_replace('_', ' ', $task->current_status)) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if(Auth::id() === $task->created_by)
                                <div class="task-actions" style="display:flex; gap:6px; margin-left:auto;" onclick="event.stopPropagation()">
                                    <button onclick="editTask({{ $task->id }})" title="Edit Task"
                                            style="background:#3b82f6;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Edit
                                    </button>
                                    <button onclick="deleteTask({{ $task->id }})" title="Delete Task"
                                            style="background:#ef4444;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Delete
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="sidebar-footer">
        <button class="add-task-btn" onclick="showAddTaskModal()">
            <span class="plus-icon">+</span>
            Add Task
        </button>
    </div>

    <div id="taskDetailsModal" class="modal-overlay" style="display: none;" onclick="closeTaskDetails(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Task Details</h3>
            <button class="modal-close" onclick="closeTaskDetails()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-group">
                <label class="detail-label">Title</label>
                <div class="detail-value" id="detailTitle"></div>
            </div>
            <div class="detail-group">
                <label class="detail-label">Description</label>
                <div class="detail-value" id="detailDescription"></div>
            </div>
            <div class="detail-row">
                <div class="detail-group">
                    <label class="detail-label">Priority</label>
                    <div class="detail-value" id="detailPriority"></div>
                </div>
                <div class="detail-group">
                    <label class="detail-label">Status</label>
                    <div class="detail-value" id="detailStatus"></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-group">
                    <label class="detail-label">Required File Types</label>
                    <div class="detail-value" id="detailFileTypes"></div>
                </div>
                <div class="detail-group">
                    <label class="detail-label">Created By</label>
                    <div class="detail-value" id="detailCreatedBy"></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-group">
                    <label class="detail-label">Due Date</label>
                    <div class="detail-value" id="detailDueDate"></div>
                </div>
                <div class="detail-group">
                    <label class="detail-label">Completed</label>
                    <div class="detail-value" id="detailCompleted"></div>
                </div>
            </div>
            <div class="detail-group">
                <label class="detail-label">Created At</label>
                <div class="detail-value" id="detailCreatedAt"></div>
            </div>
            <!-- Grade and Checked Date (only show if graded) -->
            <div id="detailGradeSection" style="display: none;">
                <div class="detail-row">
                    <div class="detail-group">
                        <label class="detail-label">Grade</label>
                        <div class="detail-value" id="detailGrade"></div>
                    </div>
                    <div class="detail-group">
                        <label class="detail-label">Checked Date</label>
                        <div class="detail-value" id="detailCheckedAt"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" id="taskDetailsModalFooter">
            <button class="modal-btn btn-secondary" onclick="closeTaskDetails()">Close</button>
        </div>
    </div>
</div>

<!-- View Submission Modal -->
<div id="viewSubmissionModal" class="modal-overlay" style="display: none;" onclick="closeViewSubmissionModal(event)">
    <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">View Partner's Work</h3>
            <button class="modal-close" onclick="closeViewSubmissionModal()">&times;</button>
        </div>
        <div class="modal-body" id="viewSubmissionContent">
            <p>Loading submission...</p>
        </div>
        <div class="modal-footer" id="viewSubmissionFooter">
            <button class="modal-btn btn-secondary" onclick="closeViewSubmissionModal()">Close</button>
        </div>
    </div>
</div>

<!-- Grade Task Modal -->
<div id="gradeTaskModal" class="modal-overlay" style="display: none;" onclick="closeGradeTaskModal(event)">
    <div class="modal-content" style="max-width: 600px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Grade Task</h3>
            <button class="modal-close" onclick="closeGradeTaskModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="gradeTaskForm">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" id="gradeTaskId" name="task_id">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="gradeScore" style="display: block; margin-bottom: 8px; font-weight: 600;">Score (0-<span id="maxScoreDisplay">100</span>)</label>
                    <input type="number" id="gradeScore" name="score_percentage" min="0" max="100" required 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;"
                           onchange="updateGradeDisplay()">
                    <small style="color: #6b7280; display: block; margin-top: 4px;">Enter a score from 0 to <span id="maxScoreDisplay2">100</span></small>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Calculated Grade</label>
                    <div id="calculatedGrade" style="font-size: 24px; font-weight: bold; color: #3b82f6; padding: 10px; background: #eff6ff; border-radius: 4px; text-align: center;">
                        -
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="gradeStatus" style="display: block; margin-bottom: 8px; font-weight: 600;">Status</label>
                    <select id="gradeStatus" name="status" required 
                            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                        <option value="pass">Pass</option>
                        <option value="fail">Fail</option>
                        <option value="needs_improvement">Needs Improvement</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="gradeFeedback" style="display: block; margin-bottom: 8px; font-weight: 600;">Feedback</label>
                    <textarea id="gradeFeedback" name="feedback" rows="4" 
                              style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical;"
                              placeholder="Provide feedback on the submitted work..."></textarea>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="gradeImprovementNotes" style="display: block; margin-bottom: 8px; font-weight: 600;">Improvement Notes (Optional)</label>
                    <textarea id="gradeImprovementNotes" name="improvement_notes" rows="3" 
                              style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical;"
                              placeholder="Suggestions for improvement..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="modal-btn btn-primary" onclick="submitGrade()">
                <i class="fas fa-check" style="margin-right: 4px;"></i>Submit Grade
            </button>
            <button class="modal-btn btn-secondary" onclick="closeGradeTaskModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
// Function to show task details
function showTaskDetails(taskId) {
    // Fetch task details via AJAX
    fetch(`/tasks/${taskId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.task) {
                throw new Error(data.error || 'Failed to load task details');
            }
            
            const task = data.task;
            
            // Populate modal with task data
            document.getElementById('detailTitle').textContent = task.title || 'N/A';
            document.getElementById('detailDescription').textContent = task.description || 'No description provided';
            
            // Priority with color badge
            const priorityEl = document.getElementById('detailPriority');
            if (task.priority) {
                priorityEl.innerHTML = `<span class="task-tag tag-priority-${task.priority}">${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}</span>`;
            } else {
                priorityEl.textContent = 'Not set';
            }
            
            // Status with badge
            const statusEl = document.getElementById('detailStatus');
            if (task.current_status) {
                const statusText = task.current_status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                statusEl.innerHTML = `<span class="task-tag tag-status">${statusText}</span>`;
            } else {
                statusEl.textContent = 'Not set';
            }
            
            // File types
            const fileTypesEl = document.getElementById('detailFileTypes');
            if (task.allowed_file_types && task.allowed_file_types.length > 0) {
                fileTypesEl.textContent = task.allowed_file_types.join(', ');
            } else {
                fileTypesEl.textContent = 'Any file type';
            }
            
            // Created by (from loaded relationship or fallback)
            const createdByEl = document.getElementById('detailCreatedBy');
            if (task.creator && task.creator.firstname) {
                createdByEl.textContent = `${task.creator.firstname || ''} ${task.creator.lastname || ''}`.trim() || 'Unknown';
            } else {
                createdByEl.textContent = 'Unknown';
            }
            
            // Assigned to (if element exists)
            const assignedToEl = document.getElementById('detailAssignedTo');
            if (assignedToEl) {
                if (task.assignee && task.assignee.firstname) {
                    assignedToEl.textContent = `${task.assignee.firstname || ''} ${task.assignee.lastname || ''}`.trim() || 'Unassigned';
                } else {
                    assignedToEl.textContent = 'Unassigned';
                }
            }
            
            document.getElementById('detailDueDate').textContent = task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No due date';
            document.getElementById('detailCompleted').innerHTML = task.completed ? 
                '<span style="color: #10b981; font-weight: 600;">✓ Yes</span>' : 
                '<span style="color: #6b7280;">✗ No</span>';
            document.getElementById('detailCreatedAt').textContent = task.created_at ? new Date(task.created_at).toLocaleString() : 'Unknown';
            
            // Show grade and checked date if graded
            const gradeSection = document.getElementById('detailGradeSection');
            if (task.evaluation && task.evaluation.has_been_graded) {
                gradeSection.style.display = 'block';
                const gradeEl = document.getElementById('detailGrade');
                const grade = task.evaluation.grade || 'N/A';
                const score = task.evaluation.score_percentage || 0;
                gradeEl.innerHTML = `<span style="font-size: 20px; font-weight: bold; color: #3b82f6;">${grade}</span> <span style="color: #6b7280; margin-left: 8px;">(${score}%)</span>`;
                
                const checkedAtEl = document.getElementById('detailCheckedAt');
                if (task.evaluation.checked_at) {
                    checkedAtEl.textContent = new Date(task.evaluation.checked_at).toLocaleString();
                } else {
                    checkedAtEl.textContent = 'Not available';
                }
            } else {
                gradeSection.style.display = 'none';
            }
            
            // Update modal footer with action buttons
            const footer = document.getElementById('taskDetailsModalFooter');
            let footerHtml = '';
            
            // Check if user can grade this task (creator viewing submitted work)
            if (task.can_be_graded && task.has_submission) {
                const hasBeenGraded = task.evaluation && task.evaluation.has_been_graded;
                footerHtml = `
                    <button class="modal-btn btn-info" onclick="viewPartnerWork(${task.id})" style="margin-right: 8px;">
                        <i class="fas fa-eye" style="margin-right: 4px;"></i>View Work
                    </button>
                    ${!hasBeenGraded ? `
                    <button class="modal-btn btn-success" onclick="openGradeTaskModal(${task.id})" style="margin-right: 8px;">
                        <i class="fas fa-check-circle" style="margin-right: 4px;"></i>Grade Task
                    </button>
                    ` : ''}
                    <button class="modal-btn btn-secondary" onclick="closeTaskDetails()">Close</button>
                `;
            }
            // Check if user can submit this task
            else if (task.can_be_submitted) {
                footerHtml = `
                    <button class="modal-btn btn-success" onclick="submitTaskWork(${task.id})" style="margin-right: 8px;">
                        <i class="fas fa-upload" style="margin-right: 4px;"></i>Submit Work
                    </button>
                    <button class="modal-btn btn-secondary" onclick="closeTaskDetails()">Close</button>
                `;
            } else if (task.can_be_started) {
                footerHtml = `
                    <form action="/tasks/${task.id}/start" method="POST" style="display: inline; margin-right: 8px;">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button type="submit" class="modal-btn btn-primary">
                            <i class="fas fa-play" style="margin-right: 4px;"></i>Start Task
                        </button>
                    </form>
                    <button class="modal-btn btn-secondary" onclick="closeTaskDetails()">Close</button>
                `;
            } else {
                footerHtml = '<button class="modal-btn btn-secondary" onclick="closeTaskDetails()">Close</button>';
            }
            
            footer.innerHTML = footerHtml;
            
            // Show modal
            document.getElementById('taskDetailsModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Error fetching task details:', error);
            alert('Failed to load task details: ' + error.message);
        });
}

// Function to close modal
function closeTaskDetails(event) {
    if (!event || event.target.classList.contains('modal-overlay')) {
        document.getElementById('taskDetailsModal').style.display = 'none';
    }
}

// View Partner's Work
function viewPartnerWork(taskId) {
    fetch(`/tasks/${taskId}/submission-details`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.error || 'Failed to load submission');
        }
        
        const submission = data.submission;
        const task = data.task;
        const evaluation = data.evaluation;
        
        // Build submission content
        let content = `
            <div style="margin-bottom: 20px;">
                <h4 style="margin-bottom: 10px;">Submitted by: ${submission.submitter_name}</h4>
                <p style="color: #6b7280; font-size: 14px;">Submitted on: ${new Date(submission.created_at).toLocaleString()}</p>
            </div>
        `;
        
        if (submission.submission_notes) {
            content += `
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">Submission Notes:</label>
                    <div style="background: #f9fafb; padding: 12px; border-radius: 4px; white-space: pre-wrap;">${submission.submission_notes}</div>
                </div>
            `;
        }
        
        if (submission.file_paths && submission.file_paths.length > 0) {
            content += `
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">Submitted Files:</label>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
            `;
            
            submission.file_paths.forEach((filePath, index) => {
                const fileName = filePath.split('/').pop();
                const fileUrl = `/storage/${filePath}`;
                content += `
                    <a href="${fileUrl}" target="_blank" style="display: inline-flex; align-items: center; padding: 8px 12px; background: #eff6ff; border-radius: 4px; text-decoration: none; color: #3b82f6;">
                        <i class="fas fa-file" style="margin-right: 8px;"></i>${fileName}
                        <i class="fas fa-external-link-alt" style="margin-left: 8px; font-size: 12px;"></i>
                    </a>
                `;
            });
            
            content += `</div></div>`;
        }
        
        document.getElementById('viewSubmissionContent').innerHTML = content;
        
        // Update footer
        let footerHtml = '';
        if (!evaluation || !evaluation.has_been_viewed) {
            footerHtml = `
                <button class="modal-btn btn-primary" onclick="markSubmissionViewed(${taskId})" style="margin-right: 8px;">
                    <i class="fas fa-check" style="margin-right: 4px;"></i>Mark as Viewed
                </button>
            `;
        }
        if (data.can_grade && (!evaluation || !evaluation.has_been_graded)) {
            footerHtml += `
                <button class="modal-btn btn-success" onclick="closeViewSubmissionModal(); openGradeTaskModal(${taskId});" style="margin-right: 8px;">
                    <i class="fas fa-check-circle" style="margin-right: 4px;"></i>Grade Task
                </button>
            `;
        }
        footerHtml += `<button class="modal-btn btn-secondary" onclick="closeViewSubmissionModal()">Close</button>`;
        document.getElementById('viewSubmissionFooter').innerHTML = footerHtml;
        
        // Show modal
        document.getElementById('viewSubmissionModal').style.display = 'flex';
    })
    .catch(error => {
        console.error('Error loading submission:', error);
        alert('Failed to load submission: ' + error.message);
    });
}

function closeViewSubmissionModal(event) {
    if (!event || event.target.classList.contains('modal-overlay')) {
        document.getElementById('viewSubmissionModal').style.display = 'none';
    }
}

function markSubmissionViewed(taskId) {
    fetch(`/tasks/${taskId}/mark-viewed`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update footer to show grade button
            const footer = document.getElementById('viewSubmissionFooter');
            footer.innerHTML = `
                <button class="modal-btn btn-success" onclick="closeViewSubmissionModal(); openGradeTaskModal(${taskId});" style="margin-right: 8px;">
                    <i class="fas fa-check-circle" style="margin-right: 4px;"></i>Grade Task
                </button>
                <button class="modal-btn btn-secondary" onclick="closeViewSubmissionModal()">Close</button>
            `;
        } else {
            alert('Failed to mark as viewed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error marking as viewed:', error);
        alert('Failed to mark as viewed: ' + error.message);
    });
}

// Grade Task Modal
let currentGradingTaskId = null;
let currentGradingMaxScore = 100;
let currentGradingPassingScore = 70;
window.currentGradingPassingPercentage = 70;

function openGradeTaskModal(taskId) {
    currentGradingTaskId = taskId;
    
    // Fetch task details to get max score
    fetch(`/tasks/${taskId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success || !data.task) {
            throw new Error(data.error || 'Failed to load task details');
        }
        
        const task = data.task;
        currentGradingMaxScore = task.max_score || 100;
        currentGradingPassingScore = task.passing_score || 70;
        
        // Calculate passing percentage
        const passingPercentage = currentGradingMaxScore > 0 ? Math.round((currentGradingPassingScore / currentGradingMaxScore) * 100) : 70;
        window.currentGradingPassingPercentage = passingPercentage;
        
        // Update max score display
        document.getElementById('maxScoreDisplay').textContent = currentGradingMaxScore;
        document.getElementById('maxScoreDisplay2').textContent = currentGradingMaxScore;
        document.getElementById('gradeScore').max = currentGradingMaxScore;
        document.getElementById('gradeTaskId').value = taskId;
        
        // Reset form
        document.getElementById('gradeTaskForm').reset();
        document.getElementById('gradeTaskId').value = taskId;
        document.getElementById('calculatedGrade').textContent = '-';
        
        // Show modal
        document.getElementById('gradeTaskModal').style.display = 'flex';
    })
    .catch(error => {
        console.error('Error loading task:', error);
        alert('Failed to load task: ' + error.message);
    });
}

function closeGradeTaskModal(event) {
    if (!event || event.target.classList.contains('modal-overlay')) {
        document.getElementById('gradeTaskModal').style.display = 'none';
        currentGradingTaskId = null;
    }
}

function updateGradeDisplay() {
    const score = parseInt(document.getElementById('gradeScore').value) || 0;
    const maxScore = currentGradingMaxScore;
    const percentage = maxScore > 0 ? Math.round((score / maxScore) * 100) : 0;
    
    let grade = '-';
    if (score > 0) {
        if (percentage >= 95) grade = 'A+';
        else if (percentage >= 90) grade = 'A';
        else if (percentage >= 85) grade = 'A-';
        else if (percentage >= 80) grade = 'B+';
        else if (percentage >= 75) grade = 'B';
        else if (percentage >= 70) grade = 'B-';
        else if (percentage >= 65) grade = 'C+';
        else if (percentage >= 60) grade = 'C';
        else if (percentage >= 55) grade = 'C-';
        else if (percentage >= 50) grade = 'D';
        else grade = 'F';
    }
    
    document.getElementById('calculatedGrade').textContent = grade;
    
    // Update status based on score
    const passingPercentage = window.currentGradingPassingPercentage || 70;
    const statusSelect = document.getElementById('gradeStatus');
    if (percentage >= passingPercentage && statusSelect.value === 'fail') {
        statusSelect.value = 'pass';
    } else if (percentage < passingPercentage && statusSelect.value === 'pass') {
        statusSelect.value = 'fail';
    }
}

function submitGrade() {
    const form = document.getElementById('gradeTaskForm');
    const formData = new FormData(form);
    const score = parseInt(formData.get('score_percentage'));
    const maxScore = currentGradingMaxScore;
    const percentage = maxScore > 0 ? Math.round((score / maxScore) * 100) : 0;
    
    // Update score_percentage to be percentage
    formData.set('score_percentage', percentage);
    
    fetch(`/tasks/${currentGradingTaskId}/evaluation`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Task graded successfully!');
            closeGradeTaskModal();
            // Refresh task details if modal is open
            if (document.getElementById('taskDetailsModal').style.display === 'flex') {
                showTaskDetails(currentGradingTaskId);
            }
            // Reload page to update task list
            location.reload();
        } else {
            if (data.requires_viewing) {
                alert('You must view the submission before grading it.');
                closeGradeTaskModal();
                viewPartnerWork(currentGradingTaskId);
            } else {
                alert('Failed to grade task: ' + (data.error || 'Unknown error'));
            }
        }
    })
    .catch(error => {
        console.error('Error grading task:', error);
        alert('Failed to grade task: ' + error.message);
    });
}
</script>
</aside>
