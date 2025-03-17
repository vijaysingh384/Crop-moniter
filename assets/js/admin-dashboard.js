/**
 * JavaScript for Admin Dashboard page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Flagged cases chart
    const flaggedCasesChartEl = document.getElementById('flaggedCasesChart');
    if (flaggedCasesChartEl) {
        // Sample data - in a real app, this would come from the server
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const flaggedData = [12, 19, 15, 8, 22, 17];
        const resolvedData = [8, 15, 12, 7, 18, 12];
        
        new Chart(flaggedCasesChartEl, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Flagged Cases',
                    data: flaggedData,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }, {
                    label: 'Resolved Cases',
                    data: resolvedData,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Cases'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Flagged vs Resolved Cases',
                        font: {
                            size: 16
                        }
                    }
                }
            }
        });
    }
    
    // Pension scheme distribution chart
    const schemeDistributionChartEl = document.getElementById('schemeDistributionChart');
    if (schemeDistributionChartEl) {
        // Sample data
        const schemeData = {
            'NPS': 450,
            'NSAP': 320,
            'PMSYM': 280,
            'State Pension': 180,
            'Other': 90
        };
        
        new Chart(schemeDistributionChartEl, {
            type: 'doughnut',
            data: {
                labels: Object.keys(schemeData),
                datasets: [{
                    data: Object.values(schemeData),
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
