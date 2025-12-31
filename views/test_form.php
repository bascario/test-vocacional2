<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Test Vocacional</title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                Test Vocacional
            </div>
            <div class="nav-menu">
                <span>Bienvenido, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="/test-vocacional/auth/changePassword" class="btn btn-sm btn-outline">Cambiar Contraseña</a>
                <a href="/test-vocacional/logout" class="btn btn-sm btn-outline">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="test-header">
            <h1>Test de Orientación Vocacional</h1>
            <p>Responde cada pregunta según tus intereses, habilidades y valores personales</p>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['error']) ?>     <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <span class="progress-text" id="progressText">0% completado</span>
        </div>

        <form method="POST" action="/test-vocacional/test/submit" id="testForm">
            <?php
            // $questions is a flattened list provided by the controller
            $totalQuestions = count($questions);
            ?>

            <div class="book-container" id="bookContainer">
                <div class="page" id="pageFront">
                    <!-- Question content injected by JS / initial rendered below -->
                </div>
            </div>

            <input type="hidden" id="currentIndex" name="currentIndex" value="0">
            <div class="page-controls" id="pageControls">
                <button type="button" class="btn btn-secondary" id="prevBtn">← Anterior</button>
                <span id="pageCounter">Pregunta 1 de <?= $totalQuestions ?></span>
                <button type="button" class="btn btn-primary" id="nextBtn">Siguiente →</button>
            </div>

            <div style="margin-top:18px; text-align:center;">
                <button type="submit" class="btn btn-success btn-lg" id="submitBtn" style="display:none">Finalizar y Ver
                    Resultados</button>
            </div>
        </form>
    </div>

    <style>
        /* Simple 3D page flip book effect */
        .book-container {
            perspective: 1200px;
            margin: 30px auto;
            max-width: 800px;
        }

        .page {
            background: #fff;
            padding: 28px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            border-radius: 6px;
            min-height: 220px;
            transition: transform 600ms ease, opacity 300ms ease;
            transform-origin: left center;
        }

        .page.flip-out {
            transform: rotateY(-90deg);
            opacity: 0;
        }

        .page.flip-in {
            transform: rotateY(90deg);
            opacity: 0;
        }

        .page.show {
            transform: rotateY(0deg);
            opacity: 1;
        }

        .page.show {
            transform: rotateY(0deg);
            opacity: 1;
        }

        .question-text {
            font-size: 1.25rem;
            margin-bottom: 25px;
            font-weight: 600;
            color: #2c3e50;
            text-align: center;
        }

        .likert-scale-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .likert-options {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .likert-btn {
            flex: 1;
            padding: 15px 5px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            font-weight: 600;
            color: #555;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .likert-btn:hover {
            border-color: #3498db;
            background: #f7fbff;
        }

        .likert-btn.selected {
            border-color: #3498db;
            background: #3498db;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }

        .likert-btn .val-num {
            font-size: 1.4rem;
            margin-bottom: 5px;
            display: block;
        }

        .likert-btn .val-text {
            font-size: 0.8rem;
            font-weight: normal;
        }

        .likert-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #7f8c8d;
            padding: 0 5px;
        }

        .page-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        #username-suggestions .btn {
            cursor: pointer;
        }
    </style>

    <script>
        const questions = <?= json_encode($questions) ?>;
        const totalQuestions = questions.length;
        let currentIndex = 0;

        const pageEl = document.getElementById('pageFront');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        const pageCounter = document.getElementById('pageCounter');

        function renderQuestion(index) {
            const q = questions[index];
            const existingVal = (localStorage.getItem('testProgress') ? JSON.parse(localStorage.getItem('testProgress')) : {})[`respuestas[${q.id}]`];

            pageEl.innerHTML = `
                <div class="question-card">
                    <p class="question-text">${escapeHtml(q.pregunta)}</p>
                    
                    <div class="likert-scale-container">
                        <div class="likert-labels">
                            <span>Totalmente en desacuerdo</span>
                            <span>Totalmente de acuerdo</span>
                        </div>
                        
                        <div class="likert-options">
                            ${[1, 2, 3, 4, 5].map(val => {
                const isSelected = existingVal == val;
                const labels = {
                    1: 'Totalmente en desacuerdo',
                    2: 'En desacuerdo',
                    3: 'Difícil decidir',
                    4: 'De acuerdo',
                    5: 'Totalmente de acuerdo'
                };
                return `
                                <div class="likert-btn ${isSelected ? 'selected' : ''}" onclick="selectOption(${q.id}, ${val})">
                                    <span class="val-num">${val}</span>
                                    <span class="val-text">${labels[val]}</span>
                                    <input type="radio" name="respuestas[${q.id}]" value="${val}" ${isSelected ? 'checked' : ''} style="display:none">
                                </div>
                                `;
            }).join('')}
                        </div>
                    </div>
                </div>
            `;

            // update page counter and progress
            pageCounter.textContent = `Pregunta ${index + 1} de ${totalQuestions}`;
            updateProgressBar();
            updateControls();
        }

        // Expose function to global scope for onclick handler
        window.selectOption = function (qId, val) {
            // Unselect others within current likert-options container
            const curBtns = document.querySelectorAll('.likert-options .likert-btn');
            curBtns.forEach(el => el.classList.remove('selected'));

            // Find the matching button/input for this value and mark it
            const btns = document.querySelectorAll('.likert-btn');
            let matchedInput = null;
            btns.forEach(btn => {
                const input = btn.querySelector('input');
                if (input) {
                    if (parseInt(input.value, 10) === val) {
                        btn.classList.add('selected');
                        input.checked = true;
                        matchedInput = input;
                    } else {
                        input.checked = false;
                    }
                }
            });

            // Persist immediately to localStorage
            const data = JSON.parse(localStorage.getItem('testProgress') || '{}');
            data[`respuestas[${qId}]`] = String(val);
            localStorage.setItem('testProgress', JSON.stringify(data));

            // Update progress bar immediately
            updateProgressBar();
        };

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, function (s) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": "&#39;" })[s]; });
        }

        function updateProgressBar() {
            // Count answers saved in localStorage ONLY (single source of truth)
            let answered = 0;
            let saved = {};
            try {
                saved = JSON.parse(localStorage.getItem('testProgress') || '{}');
            } catch (e) {
                saved = {};
            }

            // Count all respuestas keys in saved
            for (const key in saved) {
                if (key && key.indexOf('respuestas[') === 0) {
                    answered++;
                }
            }

            // Clamp answered to totalQuestions
            answered = Math.max(0, Math.min(answered, totalQuestions));
            const pct = Math.round((answered / totalQuestions) * 100);
            document.getElementById('progressFill').style.width = pct + '%';
            document.getElementById('progressText').textContent = pct + '% completado';
        }

        function focusOnPage() {
            try {
                pageEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // briefly highlight the page to draw attention
                pageEl.classList.add('highlight-focus');
                setTimeout(() => pageEl.classList.remove('highlight-focus'), 900);
            } catch (e) { /* ignore */ }
        }

        function findFirstUnansweredIndex() {
            let saved = {};
            try { saved = JSON.parse(localStorage.getItem('testProgress') || '{}'); } catch (ex) { saved = {}; }

            for (let i = 0; i < questions.length; i++) {
                const q = questions[i];
                const key = `respuestas[${q.id}]`;
                // check saved progress first
                if (Object.prototype.hasOwnProperty.call(saved, key)) continue;
                // check DOM in case it's on the current page but not yet saved
                const checked = document.querySelector(`input[name="${key}"]:checked`);
                if (!checked) return i;
            }
            return -1;
        }

        function updateControls() {
            prevBtn.style.display = currentIndex === 0 ? 'none' : 'inline-block';
            nextBtn.style.display = currentIndex === totalQuestions - 1 ? 'none' : 'inline-block';
            submitBtn.style.display = currentIndex === totalQuestions - 1 ? 'inline-block' : 'none';
        }

        function saveCurrentAnswer() {
            // Only save if there's a checked input in the current page
            const q = questions[currentIndex];
            const checked = document.querySelector(`input[name="respuestas[${q.id}]"]:checked`);
            const data = JSON.parse(localStorage.getItem('testProgress') || '{}');

            if (checked) {
                data[`respuestas[${q.id}]`] = checked.value;
                localStorage.setItem('testProgress', JSON.stringify(data));
            }
        }

        function goTo(index) {
            if (index < 0 || index >= totalQuestions) return;

            // Validate current question answered before moving forward
            if (index > currentIndex) {
                const curQ = questions[currentIndex];
                const checked = document.querySelector(`input[name="respuestas[${curQ.id}]"]:checked`);
                if (!checked) {
                    if (window.TestVocacional && typeof window.TestVocacional.showAlert === 'function') {
                        window.TestVocacional.showAlert('Por favor responde la pregunta antes de continuar.', 'error');
                    } else {
                        alert('Por favor responde la pregunta antes de continuar.');
                    }
                    // bring the current question into view and highlight
                    focusOnPage();
                    return;
                }
            }

            // start flip out
            pageEl.classList.add('flip-out');
            pageEl.addEventListener('transitionend', function handler() {
                pageEl.removeEventListener('transitionend', handler);
                // Save current answers
                saveCurrentAnswer();
                currentIndex = index;
                renderQuestion(currentIndex);
                // flip in
                pageEl.classList.remove('flip-out');
                pageEl.classList.add('flip-in');
                requestAnimationFrame(() => {
                    // allow frame to apply class
                    setTimeout(() => pageEl.classList.remove('flip-in'), 600);
                });
            });
        }

        prevBtn.addEventListener('click', function () { goTo(currentIndex - 1); });
        nextBtn.addEventListener('click', function () { goTo(currentIndex + 1); });

        // Ensure last selection is saved when clicking the final submit button
        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                try { saveCurrentAnswer(); updateProgressBar(); } catch (e) { /* ignore */ }
            });
        }

        // When user interacts with page (click an option), update progress immediately
        document.getElementById('testForm').addEventListener('change', function (e) {
            if (e.target && e.target.name && e.target.name.startsWith('respuestas')) {
                // Change event already saved it, just update UI
                updateProgressBar();
            }
        });

        // On submit, ensure all answered — count saved responses in localStorage (single source of truth)
        document.getElementById('testForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // Save the current page answer before validating
            saveCurrentAnswer();

            // Count all responses in localStorage
            let saved = {};
            try {
                saved = JSON.parse(localStorage.getItem('testProgress') || '{}');
            } catch (ex) {
                saved = {};
            }

            let answered = 0;
            for (const key in saved) {
                if (key && key.indexOf('respuestas[') === 0) {
                    answered++;
                }
            }

            // If not all answered, show error and jump to first unanswered
            if (answered < totalQuestions) {
                if (window.TestVocacional && typeof window.TestVocacional.showAlert === 'function') {
                    window.TestVocacional.showAlert('Por favor responde todas las preguntas antes de enviar el test.', 'error');
                } else {
                    alert('Por favor responde todas las preguntas antes de enviar el test.');
                }

                // Jump to first unanswered question
                const first = findFirstUnansweredIndex();
                if (first >= 0 && first !== currentIndex) {
                    currentIndex = first;
                    renderQuestion(currentIndex);
                    setTimeout(() => focusOnPage(), 100);
                }
                return;
            }

            // All answered - prepare hidden inputs for all responses
            for (const key in saved) {
                if (key && key.indexOf('respuestas[') === 0) {
                    // Check if input already exists
                    let existingInput = document.querySelector(`input[name="${key}"][type="hidden"]`);
                    if (!existingInput) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = saved[key];
                        document.getElementById('testForm').appendChild(input);
                    }
                }
            }

            // Clear localStorage and submit form
            localStorage.removeItem('testProgress');
            document.getElementById('testForm').removeEventListener('submit', arguments.callee);
            document.getElementById('testForm').submit();
        });

        // Initial render
        renderQuestion(0);

    </script>
    <?php require 'views/layout/footer.php'; ?>
</body>

</html>