<?php
session_start();

require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Check if user is logged in
if (!isset($_SESSION['student_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header('Location: studentlogin.php');
    exit();
}

$db = new Database();
$studentId = $_SESSION['student_id'];

// Create concerns table if it doesn't exist
$db->query("CREATE TABLE IF NOT EXISTS concerns (
    concern_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50),
    concern_title VARCHAR(200),
    status VARCHAR(20) DEFAULT 'Pending',
    ticket_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle concern deletion
if (isset($_POST['delete_concern'])) {
    $concernId = $db->escape($_POST['concern_id']);
    $db->query("DELETE FROM concerns WHERE concern_id = '$concernId' AND student_id = '$studentId'");
    header('Location: Concern.php');
    exit();
}

// Handle new concern submission
if (isset($_POST['add_concern'])) {
    $title = $db->escape($_POST['concern_title']);
    $ticketNumber = 'F424-FFD3-FR24'; // Generate a unique ticket number in practice
    
    $db->query("INSERT INTO concerns (student_id, concern_title, ticket_number) 
                VALUES ('$studentId', '$title', '$ticketNumber')");
    header('Location: Concern.php');
    exit();
}

// Get student's concerns
$sql = "SELECT * FROM concerns WHERE student_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $studentId);
$stmt->execute();
$concerns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="concerns-container">
    <div class="concerns-header">
        <h2>STUDENT CONCERNS</h2>
        <button class="add-concern-btn" onclick="showAddConcernModal()">+ Add Concern</button>
    </div>

    <div class="concerns-table">
        <table>
            <thead>
                <tr>
                    <th>Concern Title</th>
                    <th>Status</th>
                    <th>Support Ticket</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($concerns as $concern): ?>
                <tr>
                    <td><?php echo htmlspecialchars($concern['concern_title']); ?></td>
                    <td>
                        <span class="status-badge <?php echo strtolower($concern['status']); ?>">
                            <?php echo htmlspecialchars($concern['status']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($concern['ticket_number']); ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="concern_id" value="<?php echo $concern['concern_id']; ?>">
                            <button type="submit" name="delete_concern" class="delete-btn">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Concern Modal -->
<div id="addConcernModal" class="modal">
    <div class="modal-content">
        <h2>Add New Concern</h2>
        <form method="POST">
            <div class="form-group">
                <label for="concern_title">Concern Title:</label>
                <input type="text" id="concern_title" name="concern_title" required>
            </div>
            <div class="form-actions">
                <button type="button" onclick="closeAddConcernModal()" class="cancel-btn">Cancel</button>
                <button type="submit" name="add_concern" class="submit-btn">Submit</button>
            </div>
        </form>
    </div>
</div>

<style>
.concerns-container {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.concerns-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.concerns-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
}

.add-concern-btn {
    background: #4f46e5;
    color: white;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s;
}

.add-concern-btn:hover {
    background: #4338ca;
}

.concerns-table table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.concerns-table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: left;
    font-weight: 500;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
}

.concerns-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    color: #475569;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.completed {
    background: #dcfce7;
    color: #166534;
}

.delete-btn {
    background: #ef4444;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    cursor: pointer;
    font-size: 0.875rem;
    transition: background-color 0.2s;
}

.delete-btn:hover {
    background: #dc2626;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal-content {
    position: relative;
    background: white;
    margin: 10% auto;
    padding: 2rem;
    width: 90%;
    max-width: 500px;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #475569;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 1rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.cancel-btn {
    background: #e2e8f0;
    color: #475569;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    cursor: pointer;
    font-size: 0.875rem;
    transition: background-color 0.2s;
}

.submit-btn {
    background: #4f46e5;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    cursor: pointer;
    font-size: 0.875rem;
    transition: background-color 0.2s;
}

.cancel-btn:hover {
    background: #cbd5e1;
}

.submit-btn:hover {
    background: #4338ca;
}

@media (max-width: 768px) {
    .concerns-container {
        padding: 1rem;
    }

    .concerns-table {
        overflow-x: auto;
    }

    .modal-content {
        margin: 20% auto;
        padding: 1.5rem;
    }
}
</style>

<script>
function showAddConcernModal() {
    document.getElementById('addConcernModal').style.display = 'block';
}

function closeAddConcernModal() {
    document.getElementById('addConcernModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('addConcernModal')) {
        closeAddConcernModal();
    }
}
</script>
