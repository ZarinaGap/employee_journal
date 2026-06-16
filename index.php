<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'config.php'; 

// ===== ЭКСПОРТ ПЕДАГОГОВ В CSV =====
if (isset($_GET['export']) && $_GET['export'] == 1 && isset($_GET['type']) && $_GET['type'] == 'teachers') {
    require_once 'config.php';
    
    $stmt = $pdo->query("SELECT * FROM teachers ORDER BY lastname");
    $teachers = $stmt->fetchAll();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="преподаватели_дюц.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Фамилия', 'Имя', 'Отчество', 'Должность', 'Объединение', 'Стаж (лет)', 'Категория'], ';');
    foreach($teachers as $t) {
        fputcsv($output, [
            $t['id'], $t['lastname'], $t['firstname'], $t['middlename'], 
            $t['position'], $t['department'], $t['experience'] . 'л', $t['category']
        ], ';');
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ДЮЦ - Педагогический состав</title>
    <link rel="icon" type="image/png" href="images/Russia.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="logo">
            <div class="logo-icon">
                <img src="images/logo.png" alt="Логотип" class="logo-img" tooltip="Герб Дворца детского творчества">
            </div>
            <div class="logo-text">
                <h1>ДДЮТ г. Белоярский</h1>
                <p>Ханты-Мансийский автономный округ - Югра, г. Белоярский, ул. Лысюка, д.4</p>
            </div>
        </div>
        <div class="header-buttons" style="display:flex; gap:12px; align-items:center;">
            <div class="dropdown-export">
                <button class="btn btn-outline" id="exportDropdownBtn" tooltip="Экспорт данных в CSV">
                    📎 Экспорт ▼
                </button>
                <div class="dropdown-export-menu" id="exportDropdownMenu">
                    <a href="#" data-export="teachers" tooltip="Скачать список всех педагогов">📋 Экспорт педагогов CSV</a>
                    <a href="#" data-export="schedule" tooltip="Скачать расписание всех педагогов">📅 Экспорт расписания CSV</a>
                </div>
            </div>
            <a href="logout.php" class="btn btn-logout" onclick="return confirm('Выйти из системы?');" tooltip="Завершить сессию">
                <span>Выйти (<?= htmlspecialchars($_SESSION['login'] ?? 'user') ?>)</span>
            </a>
        </div>
    </div>
</header>

<main class="main">
    <div class="page-header">
        <h1>Педагогический состав</h1>
        <p class="subtitle">Преподаватели и сотрудники Дворца детского творчества</p>
    </div>
    
    <div class="filters-section">
        <div class="filter-row">
            <div class="filter-group">
                <label>🔎 ПОИСК</label>
                <input type="text" id="searchInput" placeholder="Поиск по имени..." tooltip="Введите фамилию, имя или отчество">
            </div>
            <div class="filter-group">
                <label>🗃️ СПЕЦИАЛЬНОСТЬ</label>
                <select id="deptFilterSelect" tooltip="Фильтр по направлению деятельности">
                    <option value="all">Все специальности</option>
                    <option value="Художественное">Художественное</option>
                    <option value="Социально-педагогическое">Социально-педагогическое</option>
                    <option value="Техническое">Техническое</option>
                    <option value="Туристско-краеведческое">Туристско-краеведческое</option>
                    <option value="Естественнонаучное">Естественнонаучное</option>
                </select>
            </div>
            <div class="filter-group">
                <label>🏆 КАТЕГОРИЯ</label>
                <select id="catFilterSelect" tooltip="Фильтр по квалификационной категории">
                    <option value="all">Все категории</option>
                    <option value="Высшая">Высшая</option>
                    <option value="Первая">Первая</option>
                    <option value="Без категории">Без категории</option>
                </select>
            </div>
            <div class="filter-group">
                <label>&nbsp;</label>
                <button id="sortBtn" class="btn btn-secondary" style="width:100%;" tooltip="Сортировка по фамилии">
                    ↕: <span id="sortText">А→Я</span>
                </button>
            </div>
            <div class="filter-group filter-edit-btn">
                <label>&nbsp;</label>
                <button class="btn-edit-mode" id="toggleEditModeBtn" tooltip="Включить/выключить режим редактирования">
                    ✏️ Режим редактирования
                </button>
            </div>
        </div>
    </div>

    <div id="editForm" class="form-card" style="display: none;">
        <div class="form-header"><span>➕</span><h3 id="formTitle">Добавление педагога</h3></div>
        <div class="form-grid">
            <div class="input-group">
                <label>Фамилия *</label>
                <input type="text" id="lastname" maxlength="25" tooltip="Только буквы, пробелы и дефисы">
            </div>
            <div class="input-group">
                <label>Имя *</label>
                <input type="text" id="firstname" maxlength="25" tooltip="Только буквы, пробелы и дефисы">
            </div>
            <div class="input-group">
                <label>Отчество</label>
                <input type="text" id="middlename" maxlength="25" tooltip="Только буквы, пробелы и дефисы">
            </div>
            <div class="input-group">
                <label>Должность</label>
                <input type="text" id="position" maxlength="25" tooltip="Только буквы, пробелы и дефисы">
            </div>
            <div class="input-group">
                <label>Объединение</label>
                <input type="text" id="department" maxlength="25" tooltip="Только буквы, пробелы и дефисы">
            </div>
            <div class="input-group">
                <label>Стаж (лет)</label>
                <input type="text" id="experience" maxlength="2" inputmode="numeric" tooltip="Только цифры, от 1 до 99">
            </div>
            <div class="input-group">
                <label>Категория</label>
                <select id="category" tooltip="Выберите квалификационную категорию">
                    <option>Без категории</option>
                    <option>Первая</option>
                    <option>Высшая</option>
                </select>
            </div>
            <div class="photo-upload">
                <div class="photo-label">📸 Фото</div>
                <input type="file" id="photoFile" accept="image/*" tooltip="Загрузите фотографию педагога">
                <div class="photo-preview" id="photoPreview"></div>
                <input type="hidden" id="photoPath">
            </div>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" id="addBtn" tooltip="Добавить нового педагога">➕ Добавить</button>
            <button class="btn btn-secondary" id="editBtn" style="display:none;" tooltip="Сохранить изменения">💾 Сохранить</button>
            <button class="btn btn-secondary" id="cancelBtn" style="display:none;" tooltip="Отменить редактирование">❌ Отмена</button>
        </div>
    </div>

    <div id="teachersGrid" class="teachers-grid"></div>

    <div class="stats">
        <div class="stat stat-total">👥 Всего: <span id="totalCount">0</span></div>
        <div class="stat stat-high">🏆 Высшая: <span id="highestCount">0</span></div>
        <div class="stat stat-first">⭐ Первая: <span id="firstCount">0</span></div>
    </div>
</main>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-col">
            <p>© 2026 Дворец детского (юношеского) творчества г. Белоярский</p>
            <p>628163, ХМАО-Югра, г. Белоярский, ул. Лысюка, д.4</p>
            <p>📞 8 (34670) 5-15-47 | ✉️ <a href="mailto:info@ddutbel86.ru" class="footer-email-link" tooltip="Написать письмо на почту">info@ddutbel86.ru</a></p>
        </div>
        <div class="footer-col">
            <h4>Социальные сети</h4>
            <div class="social-links-row">
                <a href="https://ok.ru/ddyutg.bel" target="_blank" class="social-link-icon" tooltip="Одноклассники">
                    <img src="images/ok.png" alt="Одноклассники" class="social-icon">
                </a>
                <a href="https://vk.com/ddutbel_86" target="_blank" class="social-link-icon" tooltip="ВКонтакте">
                    <img src="images/vk.png" alt="ВКонтакте" class="social-icon">
                </a>
                <a href="https://max.ru/id8611005670_gos" target="_blank" class="social-link-icon" tooltip="Max">
                    <img src="images/max.png" alt="Max" class="social-icon">
                </a>
            </div>
        </div>
    </div>
</footer>

<div id="teacherModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" id="closeModalBtn" tooltip="Закрыть">✕</button>
        <div id="modalContent"></div>
    </div>
</div>

<div id="editScheduleModal" class="modal">
    <div class="modal-content modal-schedule-edit">
        <button class="modal-close" id="closeScheduleModalBtn" tooltip="Закрыть">✕</button>
        <div id="scheduleEditContent"></div>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>