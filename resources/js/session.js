// Textarea auto-resize
const textarea = document.querySelector('.message-input');
if (textarea) {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}

// Tab switching
const tabButtons = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

tabButtons.forEach(button => {
    button.addEventListener('click', () => {
        const tabName = button.getAttribute('data-tab');
        
        tabButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        
        tabContents.forEach(content => {
            content.style.display = 'none';
        });
        
        const selectedContent = document.querySelector(`[data-content="${tabName}"]`);
        if (selectedContent) {
            selectedContent.style.display = 'block';
        }
    });
});

// Mobile sidebar toggle
const tasksBtn = document.querySelector('[title="Tasks"]');
const sidebar = document.querySelector('.tasks-sidebar');
const overlay = document.querySelector('.mobile-overlay');

if (tasksBtn && sidebar && overlay) {
    function toggleSidebar() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }
    
    tasksBtn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);
}

// TODO: Will integrate ChatManager, VideoCallManager, TaskManager here

