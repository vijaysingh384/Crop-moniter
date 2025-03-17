/**
 * JavaScript for Crop Analysis page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get the crop analysis chart container
    const soilHealthChartEl = document.getElementById('soilHealthChart');
    const waterNeedsChartEl = document.getElementById('waterNeedsChart');
    const growthRateChartEl = document.getElementById('growthRateChart');
    
    if (soilHealthChartEl && waterNeedsChartEl && growthRateChartEl) {
        // Use Chart.js to create charts
        
        // Sample data - in a real app this would come from the server
        const dates = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const soilHealthData = [7.5, 6.8, 7.2, 8.1, 7.9, 8.5];
        const waterNeedsData = [5.5, 6.2, 7.0, 6.5, 5.8, 6.0];
        const growthRateData = [6.2, 6.5, 7.1, 7.8, 8.2, 8.7];
        
        // Soil Health Chart
        new Chart(soilHealthChartEl, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Soil Health Score',
                    data: soilHealthData,
                    backgroundColor: 'rgba(53, 108, 182, 0.2)',
                    borderColor: 'rgba(53, 108, 182, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(53, 108, 182, 1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        title: {
                            display: true,
                            text: 'Soil Health Score (0-10)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                }
            }
        });
        
        // Water Needs Chart
        new Chart(waterNeedsChartEl, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Water Needs Score',
                    data: waterNeedsData,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        title: {
                            display: true,
                            text: 'Water Needs Score (0-10)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                }
            }
        });
        
        // Growth Rate Chart
        new Chart(growthRateChartEl, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Growth Rate Score',
                    data: growthRateData,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        title: {
                            display: true,
                            text: 'Growth Rate (0-10)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                }
            }
        });
    }
    
    // Disease detection summary chart
    const diseaseChartEl = document.getElementById('diseaseChart');
    if (diseaseChartEl) {
        // Sample data
        const diseaseData = {
            'Healthy': 65,
            'Leaf Blight': 12,
            'Powdery Mildew': 8,
            'Root Rot': 10,
            'Nutrient Deficiency': 5
        };
        
        new Chart(diseaseChartEl, {
            type: 'pie',
            data: {
                labels: Object.keys(diseaseData),
                datasets: [{
                    data: Object.values(diseaseData),
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(23, 162, 184, 0.7)',
                        'rgba(108, 117, 125, 0.7)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(108, 117, 125, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    title: {
                        display: true,
                        text: 'Disease Detection Distribution'
                    }
                }
            }
        });
    }
});
