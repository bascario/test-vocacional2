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
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <span class="progress-text" id="progressText">0% completado</span>
        </div>

        <!-- Category Navigation Tabs -->
        <div class="category-tabs">
            <?php 
            $categories = array_keys($questions);
            foreach ($categories as $index => $category): 
            ?>
                <button type="button" class="category-tab <?= $index === 0 ? 'active' : '' ?>" 
                        data-category="<?= $category ?>"
                        onclick="switchCategory('<?= $category ?>')">
                    <span class="tab-title"><?= ucfirst($category) ?></span>
                    <span class="tab-progress" id="progress-<?= $category ?>">0/0</span>
                </button>
            <?php endforeach; ?>
        </div>

        <form method="POST" action="/test-vocacional/test/submit" id="testForm">
            <?php 
            $totalQuestions = 0;
            
            // Contar total de preguntas
            foreach ($questions as $categoryQuestions) {
                foreach ($categoryQuestions as $typeQuestions) {
                    $totalQuestions += count($typeQuestions);
                }
            }
            ?>
            
            <?php foreach ($questions as $categoryIndex => $categoryQuestions): ?>
                <div class="category-form" id="form-<?= $categoryIndex ?>" 
                     style="display: <?= $categoryIndex === array_key_first($questions) ? 'block' : 'none' ?>;">
                    
                    <div class="category-header">
                        <h2><?= ucfirst($categoryIndex) ?></h2>
                        <p class="category-description">Responde las preguntas sobre <?= $categoryIndex ?></p>
                    </div>
                    
                    <?php foreach ($categoryQuestions as $type => $typeQuestions): ?>
                        <div class="type-subsection">
                            <h3><?= ucfirst($type) ?></h3>
                            
                            <?php foreach ($typeQuestions as $question): ?>
                                <div class="question-card">
                                    <p class="question-text">
                                        <?= htmlspecialchars($question['pregunta']) ?>
                                    </p>
                                    
                                    <div class="likert-scale">
                                        <label class="likert-option">
                                            <input type="radio" 
                                                   name="respuestas[<?= $question['id'] ?>]" 
                                                   value="1" 
                                                   required
                                                   onchange="updateProgress()">
                                            <span class="likert-label">Sí</span>
                                        </label>
                                        <label class="likert-option">
                                            <input type="radio" 
                                                   name="respuestas[<?= $question['id'] ?>]" 
                                                   value="0" 
                                                   required
                                                   onchange="updateProgress()">
                                            <span class="likert-label">No</span>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="category-navigation">
                        <?php 
                        $categoryKeys = array_keys($questions);
                        $currentIndex = array_search($categoryIndex, $categoryKeys);
                        ?>
                        
                        <?php if ($currentIndex > 0): ?>
                            <button type="button" class="btn btn-secondary" 
                                    onclick="switchCategory('<?= $categoryKeys[$currentIndex - 1] ?>')">
                                ← Anterior
                            </button>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>
                        
                        <?php if ($currentIndex < count($categoryKeys) - 1): ?>
                            <button type="button" class="btn btn-primary" 
                                    onclick="switchCategory('<?= $categoryKeys[$currentIndex + 1] ?>')">
                                Siguiente →
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-success btn-lg">
                                Finalizar y Ver Resultados
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>
    </div>

    <script>
        const categories = <?= json_encode(array_keys($questions)) ?>;
        const totalQuestions = <?= $totalQuestions ?>;
        let currentCategory = categories[0];
        
        function switchCategory(categoryName) {
            // Validate current category before switching
            const currentForm = document.getElementById('form-' + currentCategory);
            const requiredInputs = currentForm.querySelectorAll('input[required]');
            let allAnswered = true;
            
            requiredInputs.forEach(input => {
                const name = input.name;
                const isAnswered = document.querySelector(`input[name="${name}"]:checked`);
                if (!isAnswered) {
                    allAnswered = false;
                }
            });
            
            if (!allAnswered) {
                alert('Por favor responde todas las preguntas de esta categoría antes de continuar.');
                return;
            }
            
            // Hide current category
            document.getElementById('form-' + currentCategory).style.display = 'none';
            document.querySelector(`.category-tab[data-category="${currentCategory}"]`).classList.remove('active');
            
            // Show new category
            currentCategory = categoryName;
            document.getElementById('form-' + currentCategory).style.display = 'block';
            document.querySelector(`.category-tab[data-category="${currentCategory}"]`).classList.add('active');
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Save progress
            saveProgress();
        }
        
        function updateProgress() {
            const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;
            const progress = (answeredQuestions / totalQuestions) * 100;
            
            document.getElementById('progressFill').style.width = progress + '%';
            document.getElementById('progressText').textContent = Math.round(progress) + '% completado';
            
            // Update individual category progress
            categories.forEach(category => {
                const categoryForm = document.getElementById('form-' + category);
                const categoryInputs = categoryForm.querySelectorAll('input[required]');
                const categoryAnswered = categoryForm.querySelectorAll('input[type="radio"]:checked').length;
                document.getElementById('progress-' + category).textContent = categoryAnswered + '/' + categoryInputs.length;
            });
        }
        
        // Form validation
        document.getElementById('testForm').addEventListener('submit', function(e) {
            const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;
            
            if (answeredQuestions < totalQuestions) {
                e.preventDefault();
                alert('Por favor responde todas las preguntas antes de continuar.');
                return false;
            }
        });

        // Auto-save functionality
        function saveProgress() {
            const formData = new FormData(document.getElementById('testForm'));
            const responses = {};
            
            for (let [key, value] of formData.entries()) {
                responses[key] = value;
            }
            
            localStorage.setItem('testProgress', JSON.stringify(responses));
        }

        function restoreProgress() {
            const saved = localStorage.getItem('testProgress');
            if (saved) {
                const responses = JSON.parse(saved);
                
                for (let key in responses) {
                    const input = document.querySelector(`input[name="${key}"][value="${responses[key]}"]`);
                    if (input) {
                        input.checked = true;
                    }
                }
            }
            updateProgress();
        }

        // Save progress on change
        document.getElementById('testForm').addEventListener('change', function() {
            saveProgress();
        });

        // Restore progress on page load
        restoreProgress();

        // Clear saved progress on successful submission
        document.getElementById('testForm').addEventListener('submit', function() {
            localStorage.removeItem('testProgress');
        });
    </script>
</body>
</html>