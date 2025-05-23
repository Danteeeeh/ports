    </main>
  </div>

  <script>
    function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Show the selected section
        document.getElementById(sectionId).classList.add('active');
    }

    // Notification handling
    document.querySelector('.notif').addEventListener('click', function() {
        // TODO: Implement notification panel
        alert('Notifications feature coming soon!');
    });

    // Search functionality
    document.querySelector('.top-bar input').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            // TODO: Implement search functionality
            alert('Search feature coming soon!');
        }
    });
  </script>
</body>
</html>
