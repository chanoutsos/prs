// Main application JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if they exist on the page
    if (document.getElementById('vaccinationChart')) {
        initVaccinationChart();
    }
    
    if (document.getElementById('vaccineTypeChart')) {
        initVaccineTypeChart();
    }
    
    if (document.getElementById('trendChart')) {
        initTrendChart();
    }
    
    // Initialize mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            document.querySelector('.nav ul').classList.toggle('active');
        });
    }
});

// Initialize vaccination distribution chart
function initVaccinationChart() {
    fetch('/api/v1/vaccinations?stats=true')
        .then(response => response.json())
        .then(data => {
            if (data.status === 200) {
                const ctx = document.getElementById('vaccinationChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.data.by_type.map(item => item.vaccine_type),
                        datasets: [{
                            label: 'Vaccinations by Type',
                            data: data.data.by_type.map(item => item.count),
                            backgroundColor: [
                                'rgba(52, 152, 219, 0.7)',
                                'rgba(46, 204, 113, 0.7)',
                                'rgba(241, 196, 15, 0.7)',
                                'rgba(231, 76, 60, 0.7)'
                            ],
                            borderColor: [
                                'rgba(52, 152, 219, 1)',
                                'rgba(46, 204, 113, 1)',
                                'rgba(241, 196, 15, 1)',
                                'rgba(231, 76, 60, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading vaccination data:', error));
}

// Initialize vaccine type by region chart
function initVaccineTypeChart() {
    fetch('/api/v1/vaccinations?stats=true')
        .then(response => response.json())
        .then(data => {
            if (data.status === 200) {
                const ctx = document.getElementById('vaccineTypeChart').getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: data.data.by_type.map(item => item.vaccine_type),
                        datasets: [{
                            label: 'Vaccine Types',
                            data: data.data.by_type.map(item => item.count),
                            backgroundColor: [
                                'rgba(52, 152, 219, 0.7)',
                                'rgba(46, 204, 113, 0.7)',
                                'rgba(241, 196, 15, 0.7)',
                                'rgba(231, 76, 60, 0.7)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }
        })
        .catch(error => console.error('Error loading vaccine type data:', error));
}

// Initialize vaccination trend chart
function initTrendChart() {
    fetch('/api/v1/vaccinations?stats=true')
        .then(response => response.json())
        .then(data => {
            if (data.status === 200) {
                const ctx = document.getElementById('trendChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.data.over_time.map(item => item.date),
                        datasets: [{
                            label: 'Vaccinations Over Time',
                            data: data.data.over_time.map(item => item.count),
                            backgroundColor: 'rgba(52, 152, 219, 0.2)',
                            borderColor: 'rgba(52, 152, 219, 1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading trend data:', error));
}

// Form submission handler for AJAX forms
function handleFormSubmit(formId, successCallback) {
    const form = document.getElementById(formId);
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const action = form.getAttribute('action');
            const method = form.getAttribute('method') || 'POST';
            
            fetch(action, {
                method: method,
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 200 || data.status === 201) {
                    if (typeof successCallback === 'function') {
                        successCallback(data);
                    }
                } else {
                    alert(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request');
            });
        });
    }
}