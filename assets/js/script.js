document.addEventListener('DOMContentLoaded', function() {
    // Navigation handling
    const navButtons = document.querySelectorAll('nav button[data-section]');
    const sections = document.querySelectorAll('.content-section');

    navButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetSection = button.getAttribute('data-section');
            
            // Update active states
            navButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            sections.forEach(section => {
                section.classList.remove('active');
                if (section.id === targetSection) {
                    section.classList.add('active');
                }
            });
        });
    });

    // Search functionality
    const searchInput = document.querySelector('.search-bar input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            // Add search functionality based on your needs
        });
    }

    // Notification handling
    const notificationBell = document.querySelector('.user-info i.fa-bell');
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            // Add notification functionality
            alert('Notifications coming soon!');
        });
    }

    // Initialize attendance calendar if on attendance page
    const attendanceCalendar = document.querySelector('.attendance-calendar');
    if (attendanceCalendar) {
        initializeCalendar(attendanceCalendar);
    }
});

function initializeCalendar(container) {
    const now = new Date();
    const month = now.getMonth();
    const year = now.getFullYear();
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'];
    
    let calendarHTML = `
        <div class="calendar-header">
            <h3>${monthNames[month]} ${year}</h3>
        </div>
        <div class="calendar-grid">
            <div class="weekday">Sun</div>
            <div class="weekday">Mon</div>
            <div class="weekday">Tue</div>
            <div class="weekday">Wed</div>
            <div class="weekday">Thu</div>
            <div class="weekday">Fri</div>
            <div class="weekday">Sat</div>
    `;
    
    // Add empty cells for days before the first of the month
    for (let i = 0; i < firstDay; i++) {
        calendarHTML += '<div class="calendar-day empty"></div>';
    }
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const isToday = day === now.getDate();
        calendarHTML += `
            <div class="calendar-day${isToday ? ' today' : ''}">
                <span class="day-number">${day}</span>
                <div class="attendance-status"></div>
            </div>
        `;
    }
    
    calendarHTML += '</div>';
    container.innerHTML = calendarHTML;

    // Add calendar styles if not already in CSS
    if (!document.querySelector('#calendar-styles')) {
        const styles = document.createElement('style');
        styles.id = 'calendar-styles';
        styles.textContent = `
            .calendar-header {
                text-align: center;
                margin-bottom: 1rem;
            }
            .calendar-grid {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 0.5rem;
                background: white;
                padding: 1rem;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
            }
            .weekday {
                text-align: center;
                font-weight: 500;
                color: var(--text-light);
                padding: 0.5rem;
            }
            .calendar-day {
                aspect-ratio: 1;
                padding: 0.5rem;
                border: 1px solid #e1e1e1;
                border-radius: 4px;
                display: flex;
                flex-direction: column;
                align-items: center;
                cursor: pointer;
                transition: var(--transition);
            }
            .calendar-day:hover {
                background: #f8f9fa;
            }
            .calendar-day.today {
                background: var(--primary-color);
                color: white;
            }
            .calendar-day.empty {
                background: #f8f9fa;
                border: none;
            }
            .day-number {
                font-weight: 500;
                margin-bottom: 0.25rem;
            }
            .attendance-status {
                width: 8px;
                height: 8px;
                border-radius: 50%;
            }
        `;
        document.head.appendChild(styles);
    }
}
