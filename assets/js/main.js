// Main JavaScript functionality
document.addEventListener('DOMContentLoaded', function () {
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
    // Add fade-in animation to general elements
    const elements = document.querySelectorAll('.card, .result-card, .stat-card, .login-card');
    elements.forEach((el, index) => {
        el.style.animationDelay = `${index * 0.08}s`;
        el.classList.add('fade-in');
    });

    // Special login animations: show logo, slide card and pulse primary button
    const loginCard = document.querySelector('.login-card');
    if (loginCard) {
        // add class that triggers card slide-in
        loginCard.classList.add('animate');

        const logo = loginCard.querySelector('.login-logo');
        if (logo) {
            setTimeout(() => logo.classList.add('show'), 120);
        }

        const primaryBtn = loginCard.querySelector('.btn-primary');
        if (primaryBtn) {
            setTimeout(() => primaryBtn.classList.add('pulse'), 700);
        }
    }
}

function initializeFormValidation() {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
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
        input.addEventListener('change', function (e) {
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

        form.addEventListener('change', function () {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                const formData = new FormData(form);
                localStorage.setItem('testProgress', JSON.stringify(Object.fromEntries(formData)));
                showAlert('Progreso guardado automáticamente', 'success');
            }, 3000);
        });
    });
}

// Username availability checker (used on registration page)
function initializeUsernameChecker() {
    const usernameInput = document.getElementById('username');
    const help = document.getElementById('username-help');
    if (!usernameInput || !help) return;

    let timer;

    const performCheck = async (val) => {
        val = val.trim();
        if (!val || val.length < 3) {
            help.innerHTML = '<span style="color:#666;">Escribe al menos 3 caracteres para verificar disponibilidad</span>';
            return;
        }

        help.innerHTML = '<div class="status-check status-loading">⏳ Verificando disponibilidad...</div>';

        try {
            const nombre = document.getElementById('nombre') ? document.getElementById('nombre').value.trim() : '';
            const apellido = document.getElementById('apellido') ? document.getElementById('apellido').value.trim() : '';
            const params = new URLSearchParams({ username: val });
            if (nombre) params.set('nombre', nombre);
            if (apellido) params.set('apellido', apellido);

            const url = `/test-vocacional/auth/check-username?${params.toString()}`;
            const res = await fetch(url);
            if (!res.ok) throw new Error('Error en la verificación');
            const data = await res.json();

            if (data.available) {
                help.innerHTML = '<div class="status-check status-available">✅ Usuario disponible</div>';
            } else {
                let html = '<div class="status-check status-taken">❌ Usuario no disponible</div>';
                if (Array.isArray(data.suggestions) && data.suggestions.length) {
                    html += '<div class="suggestions-container">';
                    html += '<span class="suggestions-title">Sugerencias disponibles:</span>';
                    html += data.suggestions.map(s => `<button type="button" class="suggest-btn" data-val="${s}">${s}</button>`).join('');
                    html += '</div>';
                } else {
                    html += '<div style="font-size:0.85rem;color:#666;margin-top:5px;">Intenta con otra variación o agrega números.</div>';
                }
                help.innerHTML = html;

                // Attach click handler for suggestions
                help.querySelectorAll('.suggest-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        usernameInput.value = this.getAttribute('data-val');
                        performCheck(usernameInput.value);
                        usernameInput.focus();
                    });
                });
            }
        } catch (err) {
            help.innerHTML = '<span style="color:#666;">No fue posible verificar disponibilidad en este momento</span>';
        }
    };

    // Check on input (with debounce)
    usernameInput.addEventListener('input', function () {
        clearTimeout(timer);
        const val = usernameInput.value.trim();
        if (!val) {
            help.innerHTML = '';
            return;
        }
        timer = setTimeout(() => performCheck(val), 500);
    });

    // Immediate check on blur if enough characters
    usernameInput.addEventListener('blur', function () {
        const val = usernameInput.value.trim();
        if (val.length >= 3) {
            clearTimeout(timer);
            performCheck(val);
        }
    });
}

// Call username checker when DOM is ready
if (document.getElementById('username')) {
    initializeUsernameChecker();
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