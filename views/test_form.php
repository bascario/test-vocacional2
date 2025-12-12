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
                <a href="/test-vocacional/logout" class="btn btn-sm btn-outline">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="test-header">
            <h1>Test de Orientación Vocacional</h1>
            <p>Responde cada pregunta según tus intereses, habilidades y valores personales</p>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']) ?><?php unset($_SESSION['error']); ?></div>
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
                <button type="submit" class="btn btn-success btn-lg" id="submitBtn" style="display:none">Finalizar y Ver Resultados</button>
            </div>
        </form>
    </div>

    <style>
        /* Simple 3D page flip book effect */
        .book-container { perspective: 1200px; margin: 30px auto; max-width: 800px; }
        .page { background: #fff; padding: 28px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); border-radius: 6px; min-height: 220px; transition: transform 600ms ease, opacity 300ms ease; transform-origin: left center; }
        .page.flip-out { transform: rotateY(-90deg); opacity: 0; }
        .page.flip-in { transform: rotateY(90deg); opacity: 0; }
        .page.show { transform: rotateY(0deg); opacity: 1; }
        .question-text { font-size: 1.05rem; margin-bottom: 18px; }
        .likert-scale { display:flex; gap:10px; justify-content:flex-start; flex-wrap: wrap; }
        .likert-option { display:inline-flex; align-items:center; gap:6px; font-size: 0.9rem; }
        .page-controls { display:flex; justify-content:space-between; align-items:center; margin-top:12px; max-width:800px; margin-left:auto; margin-right:auto; }
        #username-suggestions .btn { cursor:pointer; }
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
                    <div class="likert-scale">
                        <label class="likert-option">
                            <input type="radio" name="respuestas[${q.id}]" value="1" ${existingVal === '1' ? 'checked' : ''}>
                            <span>1. Totalmente en desacuerdo</span>
                        </label>
                        <label class="likert-option">
                            <input type="radio" name="respuestas[${q.id}]" value="2" ${existingVal === '2' ? 'checked' : ''}>
                            <span>2. En desacuerdo</span>
                        </label>
                        <label class="likert-option">
                            <input type="radio" name="respuestas[${q.id}]" value="3" ${existingVal === '3' ? 'checked' : ''}>
                            <span>3. Es difícil decidir</span>
                        </label>
                        <label class="likert-option">
                            <input type="radio" name="respuestas[${q.id}]" value="4" ${existingVal === '4' ? 'checked' : ''}>
                            <span>4. De acuerdo</span>
                        </label>
                        <label class="likert-option">
                            <input type="radio" name="respuestas[${q.id}]" value="5" ${existingVal === '5' ? 'checked' : ''}>
                            <span>5. Totalmente de acuerdo</span>
                        </label>
                    </div>
                </div>
            `;

            // update page counter and progress
            pageCounter.textContent = `Pregunta ${index + 1} de ${totalQuestions}`;
            updateProgressBar();
            updateControls();
        }

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, function(s) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[s]; });
        }

        function updateProgressBar() {
            // Count answers saved in localStorage (all pages) plus any currently checked inputs not yet saved
            let answered = 0;
            let saved = {};
            try {
                saved = JSON.parse(localStorage.getItem('testProgress') || '{}');
            } catch (e) {
                saved = {};
            }

            for (const key in saved) {
                if (key && key.indexOf('respuestas[') === 0) answered++;
            }

            // Include any checked inputs present in DOM that may not be in saved (edge case)
            document.querySelectorAll('input[type="radio"]:checked').forEach(input => {
                const name = input.name;
                if (name && name.indexOf('respuestas[') === 0 && !(name in saved)) {
                    answered++;
                }
            });

            // Clamp answered to totalQuestions
            answered = Math.max(0, Math.min(answered, totalQuestions));
            const pct = Math.round((answered / totalQuestions) * 100);
            document.getElementById('progressFill').style.width = pct + '%';
            document.getElementById('progressText').textContent = pct + '% completado';
        }

        function updateControls() {
            prevBtn.style.display = currentIndex === 0 ? 'none' : 'inline-block';
            nextBtn.style.display = currentIndex === totalQuestions - 1 ? 'none' : 'inline-block';
            submitBtn.style.display = currentIndex === totalQuestions - 1 ? 'inline-block' : 'none';
        }

        function saveCurrentAnswer() {
            const q = questions[currentIndex];
            const checked = document.querySelector(`input[name="respuestas[${q.id}]"]:checked`);
            const data = JSON.parse(localStorage.getItem('testProgress') || '{}');
            if (checked) {
                data[`respuestas[${q.id}]`] = checked.value;
            } else {
                delete data[`respuestas[${q.id}]`];
            }
            localStorage.setItem('testProgress', JSON.stringify(data));
        }

        function goTo(index) {
            if (index < 0 || index >= totalQuestions) return;

            // Validate current question answered before moving forward
            if (index > currentIndex) {
                const curQ = questions[currentIndex];
                const checked = document.querySelector(`input[name="respuestas[${curQ.id}]"]:checked`);
                if (!checked) {
                    alert('Por favor responde la pregunta antes de continuar.');
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

        prevBtn.addEventListener('click', function(){ goTo(currentIndex - 1); });
        nextBtn.addEventListener('click', function(){ goTo(currentIndex + 1); });

        // When user interacts with page (click an option), save and update progress
        document.getElementById('testForm').addEventListener('change', function(e){
            if (e.target && e.target.name && e.target.name.startsWith('respuestas')) {
                saveCurrentAnswer();
                updateProgressBar();
            }
        });

        // On submit, ensure all answered — count saved responses in localStorage (all pages)
        document.getElementById('testForm').addEventListener('submit', function(e){
            // Count responses stored in localStorage
            let saved = {};
            try { saved = JSON.parse(localStorage.getItem('testProgress') || '{}'); } catch (ex) { saved = {}; }

            let answered = 0;
            for (const key in saved) {
                if (key && key.indexOf('respuestas[') === 0) answered++;
            }

            // Also count any checked inputs currently in DOM that may not be saved yet
            document.querySelectorAll('input[type="radio"]:checked').forEach(input => {
                const name = input.name;
                if (name && name.indexOf('respuestas[') === 0 && !(name in saved)) {
                    answered++;
                }
            });

            if (answered < totalQuestions) {
                e.preventDefault();
                alert('Por favor responde todas las preguntas antes de enviar el test.');
                return;
            }

            // Append saved responses as hidden inputs (in case some aren't in DOM)
            for (const key in saved) {
                if (key && key.indexOf('respuestas[') === 0) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = saved[key];
                    document.getElementById('testForm').appendChild(input);
                }
            }

            // Also include any checked inputs present in DOM that might not be saved
            document.querySelectorAll('input[type="radio"]:checked').forEach(input => {
                const name = input.name;
                if (name && name.indexOf('respuestas[') === 0 && !(name in saved)) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = name;
                    hidden.value = input.value;
                    document.getElementById('testForm').appendChild(hidden);
                }
            });

            // Clear saved progress now that we're submitting
            localStorage.removeItem('testProgress');
        });

        // Initial render
        renderQuestion(0);

    </script>
</body>
</html>