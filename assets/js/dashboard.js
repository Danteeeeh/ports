// Task Management Functions
function showAddTaskModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Task</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addTaskForm">
                    <div class="form-group">
                        <label for="taskTitle">Title</label>
                        <input type="text" id="taskTitle" required>
                    </div>
                    <div class="form-group">
                        <label for="taskDescription">Description</label>
                        <textarea id="taskDescription" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="taskDueDate">Due Date</label>
                        <input type="date" id="taskDueDate" required>
                    </div>
                    <div class="form-group">
                        <label for="taskPriority">Priority</label>
                        <select id="taskPriority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Add Task</button>
                        <button type="button" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    // Close modal handlers
    const closeBtn = modal.querySelector('.close-btn');
    const cancelBtn = modal.querySelector('.btn-secondary');
    const closeModal = () => document.body.removeChild(modal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Form submission
    const form = modal.querySelector('#addTaskForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = {
            title: document.getElementById('taskTitle').value,
            description: document.getElementById('taskDescription').value,
            dueDate: document.getElementById('taskDueDate').value,
            priority: document.getElementById('taskPriority').value
        };
        addTask(formData);
        closeModal();
    });
}

function addTask(taskData) {
    fetch('add_task.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(taskData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to add task: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateTaskStatus(taskId, completed) {
    fetch('update_task_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ taskId, completed })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Failed to update task status');
        }
    })
    .catch(error => console.error('Error:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize GPA Chart
    const gpaChartCtx = document.getElementById('gpaChart');
    if (gpaChartCtx) {
        new Chart(gpaChartCtx, {
            type: 'line',
            data: {
                labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
                datasets: [{
                    label: 'GPA Trend',
                    data: [3.2, 3.4, 3.5, 3.6],
                    borderColor: '#4CAF50',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        min: 0,
                        max: 4,
                        ticks: {
                            stepSize: 0.5
                        }
                    }
                }
            }
        });
    }

    // Initialize Calendar
    const calendarEl = document.getElementById('calendar-widget');
    if (calendarEl) {
        flatpickr(calendarEl, {
            inline: true,
            mode: 'multiple',
            dateFormat: 'Y-m-d',
            defaultDate: 'today',
            disable: ['2025-05-11', '2025-05-12'],  // Example disabled dates (weekends)
            onChange: function(selectedDates, dateStr) {
                // Handle date selection
                console.log('Selected date:', dateStr);
            }
        });
    }

    // Get all nav buttons and content sections
    const navButtons = document.querySelectorAll('nav button[data-section]');
    const sections = document.querySelectorAll('section[id]');

    // Add click event to each nav button
    navButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetSection = button.getAttribute('data-section');

            // Remove active class from all buttons and sections
            navButtons.forEach(btn => btn.classList.remove('active'));
            sections.forEach(section => section.classList.remove('active'));

            // Add active class to clicked button and corresponding section
            button.classList.add('active');
            document.getElementById(targetSection).classList.add('active');
        });
    });

    // Initialize notifications dropdown
    const notificationsIcon = document.querySelector('.notifications');
    if (notificationsIcon) {
        notificationsIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('show-dropdown');
        });
    }

    // Close notifications dropdown when clicking outside
    document.addEventListener('click', function() {
        if (notificationsIcon) {
            notificationsIcon.classList.remove('show-dropdown');
        }
    });

    // Initialize Performance Chart
    const performanceChartCtx = document.getElementById('performanceChart');
    if (performanceChartCtx) {
        new Chart(performanceChartCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Academic Performance',
                    data: [85, 88, 92, 87, 90, 93],
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Initialize task checkboxes
    document.querySelectorAll('.task-status input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskItem = this.closest('.task-item');
            if (this.checked) {
                taskItem.style.opacity = '0.6';
            } else {
                taskItem.style.opacity = '1';
            }
        });
    });

    // Add hover effect to resource items
    document.querySelectorAll('.resource-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.querySelector('.download-btn').style.transform = 'scale(1.1)';
        });
        item.addEventListener('mouseleave', function() {
            this.querySelector('.download-btn').style.transform = 'scale(1)';
        });
    });

    // Add animation to progress bars
    const progressBars = document.querySelectorAll('.progress-bar .progress');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 300);
    });

    // Initialize tooltips for notification items
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.classList.add('highlight');
        });
        item.addEventListener('mouseleave', function() {
            this.classList.remove('highlight');
        });
    });

    // Add smooth scrolling to sections
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});
