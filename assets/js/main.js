/**
 * Main JavaScript file for Government Services Portal
 */

document.addEventListener('DOMContentLoaded', function() {
    // File Upload Preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const previewContainer = document.querySelector('.upload-preview');
            if (previewContainer) {
                previewContainer.innerHTML = '';
                
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        previewContainer.appendChild(img);
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            }
        });
    });
    
    // Alerts Dismissal
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const closeBtn = document.createElement('span');
        closeBtn.innerHTML = '&times;';
        closeBtn.className = 'close-alert';
        closeBtn.style.float = 'right';
        closeBtn.style.cursor = 'pointer';
        closeBtn.style.fontWeight = 'bold';
        
        closeBtn.addEventListener('click', function() {
            alert.style.display = 'none';
        });
        
        alert.insertBefore(closeBtn, alert.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    });
    
    // Form Validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    
                    // Create or update error message
                    let errorMsg = field.nextElementSibling;
                    if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'error-message';
                        errorMsg.style.color = '#dc3545';
                        errorMsg.style.fontSize = '0.85rem';
                        errorMsg.style.marginTop = '5px';
                        field.parentNode.insertBefore(errorMsg, field.nextSibling);
                    }
                    
                    errorMsg.textContent = `${field.getAttribute('placeholder') || 'This field'} is required`;
                } else {
                    field.classList.remove('is-invalid');
                    const errorMsg = field.nextElementSibling;
                    if (errorMsg && errorMsg.classList.contains('error-message')) {
                        errorMsg.textContent = '';
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    // Input validation on blur
    const formInputs = document.querySelectorAll('input, select, textarea');
    formInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('is-invalid');
                
                // Create or update error message
                let errorMsg = this.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    errorMsg.style.color = '#dc3545';
                    errorMsg.style.fontSize = '0.85rem';
                    errorMsg.style.marginTop = '5px';
                    this.parentNode.insertBefore(errorMsg, this.nextSibling);
                }
                
                errorMsg.textContent = `${this.getAttribute('placeholder') || 'This field'} is required`;
            } else {
                this.classList.remove('is-invalid');
                const errorMsg = this.nextElementSibling;
                if (errorMsg && errorMsg.classList.contains('error-message')) {
                    errorMsg.textContent = '';
                }
            }
        });
        
        // Clear error on input
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const errorMsg = this.nextElementSibling;
            if (errorMsg && errorMsg.classList.contains('error-message')) {
                errorMsg.textContent = '';
            }
        });
    });
    
    // Mobile Navigation Toggle
    const createMobileNav = () => {
        const header = document.querySelector('header');
        const nav = document.querySelector('nav');
        
        if (header && nav && !document.querySelector('.mobile-nav-toggle')) {
            const mobileToggle = document.createElement('button');
            mobileToggle.className = 'mobile-nav-toggle';
            mobileToggle.innerHTML = '☰';
            mobileToggle.style.display = 'none';
            mobileToggle.style.background = 'none';
            mobileToggle.style.border = 'none';
            mobileToggle.style.fontSize = '1.5rem';
            mobileToggle.style.cursor = 'pointer';
            mobileToggle.style.color = '#356cb6';
            
            header.querySelector('.container').insertBefore(mobileToggle, nav);
            
            mobileToggle.addEventListener('click', function() {
                nav.style.display = nav.style.display === 'block' ? 'none' : 'block';
                this.innerHTML = nav.style.display === 'block' ? '✕' : '☰';
            });
            
            const handleResize = () => {
                if (window.innerWidth <= 768) {
                    mobileToggle.style.display = 'block';
                    nav.style.display = 'none';
                } else {
                    mobileToggle.style.display = 'none';
                    nav.style.display = 'block';
                }
            };
            
            window.addEventListener('resize', handleResize);
            handleResize();
        }
    };
    
    createMobileNav();
    
    // Initialize any meters (progress bars)
    const meters = document.querySelectorAll('.meter');
    meters.forEach(meter => {
        const value = meter.getAttribute('data-value');
        const maxValue = meter.getAttribute('data-max') || 10;
        const percentage = (value / maxValue) * 100;
        
        const meterValue = meter.querySelector('.meter-value');
        if (meterValue) {
            setTimeout(() => {
                meterValue.style.width = `${percentage}%`;
            }, 100);
        }
    });
});
