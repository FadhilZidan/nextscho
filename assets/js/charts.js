document.addEventListener('DOMContentLoaded', function () {
    // Attendance pie chart
    var attCtx = document.getElementById('chartAttendance');
    if (attCtx && typeof Chart !== 'undefined') {
        var data = JSON.parse(attCtx.dataset.values || '[]');
        new Chart(attCtx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Izin', 'Sakit', 'Alpha'],
                datasets: [{
                    data: data,
                    backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 12 } } }
                },
                cutout: '65%',
            }
        });
    }

    // Grade bar chart
    var gradeCtx = document.getElementById('chartGrades');
    if (gradeCtx && typeof Chart !== 'undefined') {
        var labels = JSON.parse(gradeCtx.dataset.labels || '[]');
        var values = JSON.parse(gradeCtx.dataset.values || '[]');
        new Chart(gradeCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Rata-rata Nilai',
                    data: values,
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    borderColor: '#6366f1',
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { font: { size: 11 } },
                        grid: { color: '#f3f4f6' }
                    },
                    x: { ticks: { font: { size: 11 } }, grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});
