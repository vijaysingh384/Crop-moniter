/**
 * JavaScript for Admin Dashboard page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Flagged cases chart
    const flaggedCasesChartEl = document.getElementById('flaggedCasesChart');
    if (flaggedCasesChartEl) {
        fetch('api/admin/get-flagged-stats.php')
            .then(response => response.json())
            .then(data => {
                new Chart(flaggedCasesChartEl, {
                    type: 'bar',
                    data: {
                        labels: data.months,
                        datasets: [{
                            label: 'Flagged Cases',
                            data: data.flagged,
                            backgroundColor: 'rgba(220, 53, 69, 0.7)',
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Resolved Cases',
                            data: data.resolved,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error loading flagged cases data:', error);
                flaggedCasesChartEl.parentElement.innerHTML = '<div class="alert alert-danger">Error loading chart data</div>';
            });
    }
    
    // Pension scheme distribution chart
    const schemeDistributionChartEl = document.getElementById('schemeDistributionChart');
    if (schemeDistributionChartEl) {
        fetch('api/admin/get-scheme-stats.php')
            .then(response => response.json())
            .then(data => {
                new Chart(schemeDistributionChartEl, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(data.schemes),
                        datasets: [{
                            data: Object.values(data.schemes),
                            backgroundColor: [
                                'rgba(53, 108, 182, 0.7)',
                                'rgba(40, 167, 69, 0.7)',
                                'rgba(255, 193, 7, 0.7)',
                                'rgba(23, 162, 184, 0.7)',
                                'rgba(108, 117, 125, 0.7)'
                            ],
                            borderColor: [
                                'rgba(53, 108, 182, 1)',
                                'rgba(40, 167, 69, 1)',
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
                                text: 'Pension Scheme Distribution'
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error loading scheme distribution data:', error);
                schemeDistributionChartEl.parentElement.innerHTML = '<div class="alert alert-danger">Error loading chart data</div>';
            });
    }
    
    // Table search functionality
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.querySelector('table');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    }
    
    // Bulk action functionality
    const bulkActionSelect = document.getElementById('bulkAction');
    const applyBulkBtn = document.getElementById('applyBulk');
    
    if (bulkActionSelect && applyBulkBtn) {
        applyBulkBtn.addEventListener('click', function() {
            const selectedAction = bulkActionSelect.value;
            if (!selectedAction) return;
            
            const selectedCheckboxes = document.querySelectorAll('input[name="selected[]"]:checked');
            if (selectedCheckboxes.length === 0) {
                alert('Please select at least one item');
                return;
            }
            
            const selectedIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
            
            // In a real app, you would send this to the server
            console.log('Action:', selectedAction, 'IDs:', selectedIds);
            
            // Confirmation dialog
            if (confirm(`Apply ${selectedAction} to ${selectedIds.length} selected items?`)) {
                // Simulate action
                alert(`${selectedAction} applied to ${selectedIds.length} items.`);
                
                // In a real app, you would submit this to the server
                // Here we just reload for demonstration
                location.reload();
            }
        });
    }
    
    // Select all checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
});
