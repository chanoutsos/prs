<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
session_start();

// Only admin and health workers can access this page
if (!isAuthenticated() || ($_SESSION['role_id'] != ROLE_ADMIN && $_SESSION['role_id'] != ROLE_HEALTH_WORKER)) {
    header("Location: login.php");
    exit;
}

$pageTitle = $_SESSION['role_id'] == ROLE_ADMIN ? 'All Vaccinations' : 'My Vaccinations';
$apiUrl = '/prs/api/v1/vaccinations';
if ($_SESSION['role_id'] == ROLE_HEALTH_WORKER) {
    $apiUrl .= '?user_id=' . $_SESSION['user_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/prs/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../views/header.php'; ?>

    <main class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1 class="dashboard-title"><?php echo $pageTitle; ?></h1>
                <?php if ($_SESSION['role_id'] == ROLE_HEALTH_WORKER || $_SESSION['role_id'] == ROLE_ADMIN): ?>
                    <button id="addVaccinationBtn" class="btn btn-primary">Add New Vaccination Record</button>
                <?php endif; ?>
            </div>

            <div class="card">
                <table class="table" id="vaccinationsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Vaccine Type</th>
                            <th>Dose #</th>
                            <th>Date</th>
                            <th>Next Due</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Vaccination Modal -->
    <div id="vaccinationModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add Vaccination Record</h2>
            <form id="vaccinationForm">
                <div class="form-group">
                    <label for="patientId">Patient ID</label>
                    <input type="text" id="patientId" name="user_id" required>
                </div>
                <div class="form-group">
                    <label for="vaccineType">Vaccine Type</label>
                    <select id="vaccineType" name="vaccine_type" required>
                        <option value="Pfizer-BioNTech">Pfizer-BioNTech</option>
                        <option value="Moderna">Moderna</option>
                        <option value="Johnson & Johnson">Johnson & Johnson</option>
                        <option value="AstraZeneca">AstraZeneca</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="doseNumber">Dose Number</label>
                    <input type="number" id="doseNumber" name="dose_number" min="1" required>
                </div>
                <div class="form-group">
                    <label for="vaccinationDate">Vaccination Date</label>
                    <input type="date" id="vaccinationDate" name="vaccination_date" required>
                </div>
                <div class="form-group">
                    <label for="healthcareProvider">Healthcare Provider</label>
                    <input type="text" id="healthcareProvider" name="healthcare_provider">
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location">
                </div>
                <div class="form-group">
                    <label for="batchNumber">Batch Number</label>
                    <input type="text" id="batchNumber" name="batch_number">
                </div>
                <div class="form-group">
                    <label for="nextDueDate">Next Due Date</label>
                    <input type="date" id="nextDueDate" name="next_due_date">
                </div>
                <button type="submit" class="btn btn-primary">Save Record</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const apiUrl = "<?php echo $apiUrl; ?>";

            function loadVaccinations() {
                fetch(apiUrl)
                    .then(response => response.json())
                    .then(data => {
                        const tableBody = document.querySelector('#vaccinationsTable tbody');
                        tableBody.innerHTML = '';

                        if (data.status === 200) {
                            data.data.forEach(record => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${record.record_id}</td>
                                    <td>${record.user_id}</td>
                                    <td>${record.vaccine_type}</td>
                                    <td>${record.dose_number}</td>
                                    <td>${record.vaccination_date}</td>
                                    <td>${record.next_due_date || 'N/A'}</td>
                                    <td>
                                        <button class="btn btn-primary edit-btn" data-id="${record.record_id}">Edit</button>
                                        <button class="btn btn-danger delete-btn" data-id="${record.record_id}">Delete</button>
                                    </td>
                                `;
                                tableBody.appendChild(row);
                            });
                        }
                    });
            }

            // Initial load
            loadVaccinations();

            const modal = document.getElementById('vaccinationModal');
            const btn = document.getElementById('addVaccinationBtn');
            const span = document.querySelector('.close');

            if (btn) btn.onclick = () => modal.style.display = 'block';
            if (span) span.onclick = () => modal.style.display = 'none';
            window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; }

            const form = document.getElementById('vaccinationForm');
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(form);
                const data = {};
                formData.forEach((value, key) => data[key] = value);

                fetch("/prs/api/v1/vaccinations", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 201) {
                        alert('Vaccination added!');
                        form.reset();
                        modal.style.display = 'none';
                        loadVaccinations();
                    } else {
                        alert(res.message || 'Error saving record');
                    }
                });
            });
        });
    </script>
</body>
</html>
