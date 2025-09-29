// Main JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips and other UI components
    initializeUI();
    
    // Form validation
    initializeFormValidation();
    
    // File upload handling
    initializeFileUpload();
    
    // Charts initialization
    initializeCharts();
});

function initializeUI() {
    // Add fade-in animation to elements
    const elements = document.querySelectorAll('.card, .result-card, .stat-card');
    elements.forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
        el.classList.add('fade-in');
    });
}

function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAlert('Por favor completa todos los campos requeridos.', 'error');
            }
        });
    });
}

function initializeFileUpload() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    showAlert('El archivo excede el tamaño máximo permitido (10MB).', 'error');
                    e.target.value = '';
                    return;
                }
                
                const allowedTypes = [
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                
                if (!allowedTypes.includes(file.type)) {
                    showAlert('Tipo de archivo no permitido. Use Excel o Word.', 'error');
                    e.target.value = '';
                    return;
                }
                
                showAlert(`Archivo seleccionado: ${file.name}`, 'success');
            }
        });
    });
}

function initializeCharts() {
    // Chart.js global configuration
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = 'Arial, sans-serif';
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#333';
    }
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function updateProgress() {
    const forms = document.querySelectorAll('#testForm');
    forms.forEach(form => {
        const totalQuestions = form.querySelectorAll('.question-card').length;
        const answeredQuestions = form.querySelectorAll('input[type="radio"]:checked').length;
        const progress = (answeredQuestions / totalQuestions) * 100;
        
        const progressFill = form.querySelector('#progressFill');
        const progressText = form.querySelector('#progressText');
        
        if (progressFill && progressText) {
            progressFill.style.width = progress + '%';
            progressText.textContent = Math.round(progress) + '% completado';
        }
    });
}

// Auto-save functionality for test forms
function initializeAutoSave() {
    const testForms = document.querySelectorAll('#testForm');
    
    testForms.forEach(form => {
        let autoSaveTimer;
        
        form.addEventListener('change', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                const formData = new FormData(form);
                localStorage.setItem('testProgress', JSON.stringify(Object.fromEntries(formData)));
                showAlert('Progreso guardado automáticamente', 'success');
            }, 3000);
        });
    });
}

// Restore saved progress
function restoreProgress() {
    const savedProgress = localStorage.getItem('testProgress');
    if (savedProgress) {
        const progressData = JSON.parse(savedProgress);
        
        Object.keys(progressData).forEach(key => {
            const input = document.querySelector(`[name="${key}"][value="${progressData[key]}"]`);
            if (input) {
                input.checked = true;
            }
        });
        
        updateProgress();
    }
}

// Confirmation dialogs
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Smooth scrolling
function smoothScrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Export functions for global use
window.TestVocacional = {
    showAlert,
    updateProgress,
    confirmAction,
    smoothScrollTo,
    restoreProgress
};

// Initialize auto-save if test form exists
if (document.getElementById('testForm')) {
    initializeAutoSave();
    restoreProgress();
}