        </div>  
    </main>
</div>

<script>
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show the selected section
    const selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.classList.add('active');
    }
}

// Handle notifications
document.querySelector('.notif').addEventListener('click', function() {
    alert('Notifications will be implemented soon!');
});

// Handle search
document.querySelector('.search-input').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        alert('Search will be implemented soon!');
    }
});

// Show dashboard section by default
document.addEventListener('DOMContentLoaded', function() {
    showSection('dashboard');
});
</script>
</body>
</html>
