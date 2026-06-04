// ===== СБРОС КЭША =====
const DATA_VERSION = '5.0';
const storedVersion = localStorage.getItem('dataVersion');
if (storedVersion !== DATA_VERSION) {
    localStorage.clear();
    localStorage.setItem('dataVersion', DATA_VERSION);
    console.log('🔄 Данные обновлены до версии ' + DATA_VERSION);
}

// ===== ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ =====
let teachersList = [];
let scheduleList = [];
let currentEditId = null;
let sortMode = "az";
let currentDeptFilter = "all";
let currentCatFilter = "all";
let isEditMode = false;
let editingScheduleTeacherId = null;
let tempSchedule = [];
let currentPhotoData = null;

// ===== ЗАГРУЗКА ДАННЫХ ИЗ БД =====
async function loadTeachersFromDB() {
    try {
        const response = await fetch('api/get_teachers.php');
        if (!response.ok) throw new Error('Ошибка загрузки');
        teachersList = await response.json();
        renderGrid();
    } catch(e) {
        console.error('Ошибка загрузки из БД:', e);
        alert('Не удалось загрузить список педагогов');
    }
}

async function loadScheduleFromDB(teacherId) {
    try {
        const response = await fetch(`api/get_schedule.php?teacher_id=${teacherId}`);
        if (!response.ok) throw new Error('Ошибка загрузки расписания');
        return await response.json();
    } catch(e) {
        console.error('Ошибка загрузки расписания:', e);
        return [];
    }
}

// ===== СОХРАНЕНИЕ В LOCALSTORAGE =====
function saveToStorage() {
    try {
        localStorage.setItem('dataVersion', DATA_VERSION);
    } catch(e) { console.error(e); }
}

function loadFromStorage() {}

// ===== ВАЛИДАЦИЯ =====
function setupInputValidation() {
    const nameRegex = /^[a-zA-Zа-яА-ЯёЁ\s\-]*$/;
    const MAX_CHARS = 25;
    const inputs = {
        lastname: document.getElementById("lastname"),
        firstname: document.getElementById("firstname"),
        middlename: document.getElementById("middlename"),
        position: document.getElementById("position"),
        department: document.getElementById("department")
    };
    const experienceInput = document.getElementById("experience");

    function showError(input, msg) {
        input.style.borderColor = "#ef4444";
        input.style.backgroundColor = "#fef2f2";
        let d = input.parentElement.querySelector(".input-error");
        if (!d) { d = document.createElement("div"); d.className = "input-error"; input.parentElement.appendChild(d); }
        d.textContent = msg;
    }
    function showWarning(input, msg) {
        let d = input.parentElement.querySelector(".char-limit-warning");
        if (!d) { d = document.createElement("div"); d.className = "char-limit-warning"; input.parentElement.appendChild(d); }
        d.textContent = msg; d.classList.add("show");
    }
    function hideWarning(input) {
        let d = input.parentElement.querySelector(".char-limit-warning");
        if (d) d.classList.remove("show");
    }
    function clearError(input) {
        input.style.borderColor = "#e2e8f0";
        input.style.backgroundColor = "#fafbfc";
        let d = input.parentElement.querySelector(".input-error");
        if (d) d.remove();
    }

    ['lastname','firstname','middlename','position','department'].forEach(key => {
        const input = inputs[key];
        input?.addEventListener("input", function() {
            let v = this.value;
            if (v.length >= MAX_CHARS) showWarning(this, `⚠️ Лимит ${MAX_CHARS} символов!`);
            else hideWarning(this);
            if (v && !nameRegex.test(v)) {
                showError(this, "✖️ Только буквы!");
                this.value = v.replace(/[^a-zA-Zа-яА-ЯёЁ\s\-]/g, "");
            } else {
                clearError(this);
                this.value = v.slice(0, MAX_CHARS);
            }
        });
    });

    experienceInput?.addEventListener("input", function() {
        let v = this.value.replace(/[^0-9]/g, "");
        if (v.length > 2) v = v.slice(0, 2);
        this.value = v;
        if (v) clearError(this);
    });
}

function setupClearSelection() {
    document.addEventListener("click", function(e) {
        if (!e.target.closest(".teacher-card") && !e.target.closest(".form-card") &&
            !e.target.closest(".modal-content") && !e.target.closest("#filterBtn") &&
            !e.target.closest(".dropdown-content") && !e.target.closest("#sortBtn")) {
            if (currentEditId !== null) clearForm();
        }
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function validateTeacher(last, first) {
    if (!last.trim()) { alert("✖️ Введите фамилию!"); return false; }
    if (!first.trim()) { alert("✖️ Введите имя!"); return false; }
    return true;
}

function showFirework() {
    for (let i = 0; i < 20; i++) {
        setTimeout(() => {
            let d = document.createElement("div");
            d.className = "firework";
            d.textContent = "✔️";
            d.style.left = Math.random() * window.innerWidth + "px";
            d.style.top = window.innerHeight - 80 + "px";
            d.style.fontSize = Math.random() * 20 + 24 + "px";
            document.body.appendChild(d);
            setTimeout(() => d.remove(), 2000);
        }, i * 40);
    }
}

function getCategoryClass(c) {
    if (c === "Высшая") return "category-high";
    if (c === "Первая") return "category-first";
    return "category-none";
}

function renderGrid() {
    let filtered = [...teachersList];
    let search = document.getElementById("searchInput")?.value.toLowerCase() || "";

    if (search) filtered = filtered.filter(t => `${t.lastname} ${t.firstname} ${t.middlename || ""}`.toLowerCase().includes(search));
    if (currentDeptFilter !== "all") filtered = filtered.filter(t => t.specialty === currentDeptFilter);
    if (currentCatFilter !== "all") filtered = filtered.filter(t => t.category === currentCatFilter);
    if (sortMode === "az") filtered.sort((a, b) => a.lastname.localeCompare(b.lastname, "ru"));
    else if (sortMode === "za") filtered.sort((a, b) => b.lastname.localeCompare(a.lastname, "ru"));

    let container = document.getElementById("teachersGrid");
    if (filtered.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>🔎 Педагоги не найдены</p></div>';
    } else {
        container.innerHTML = filtered.map(t => {
           let photoHtml = '<div class="avatar-placeholder">👤</div>';
if (t.photoData) {
    photoHtml = `<img src="${t.photoData}" alt="фото">`;
} else if (t.photo) {
    // Добавляем обработчик ошибки: если фото не загрузилось — показываем иконку
    photoHtml = `<img src="uploads/${t.photo}" alt="фото" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'avatar-placeholder\'>👤</div>';">`;
}
            return `
                <div class="teacher-card" data-id="${t.id}" onclick="showTeacherModal(${t.id})">
                    <div class="card-header">
                        <div class="card-avatar">${photoHtml}</div>
                        <div class="card-info">
                            <h3>${escapeHtml(t.lastname)} ${escapeHtml(t.firstname)} ${escapeHtml(t.middlename || "")}</h3>
                            <p>${escapeHtml(t.position)}</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-detail"><span class="detail-label">Объединение</span><span class="detail-value">${escapeHtml(t.department)}</span></div>
                        <div class="card-detail"><span class="detail-label">Стаж</span><span class="detail-value">${t.experience}л</span></div>
                        <div class="card-detail"><span class="detail-label">Категория</span><span class="detail-value"><span class="category-badge ${getCategoryClass(t.category)}">${t.category === "Без категории" ? "—" : t.category}</span></span></div>
                    </div>
                </div>
            `;
        }).join("");
    }

    document.getElementById("totalCount").innerText = teachersList.length;
    document.getElementById("highestCount").innerText = teachersList.filter(t => t.category === "Высшая").length;
    document.getElementById("firstCount").innerText = teachersList.filter(t => t.category === "Первая").length;
}

function clearForm() {
    document.getElementById("lastname").value = "";
    document.getElementById("firstname").value = "";
    document.getElementById("middlename").value = "";
    document.getElementById("position").value = "";
    document.getElementById("department").value = "";
    document.getElementById("experience").value = "";
    document.getElementById("category").value = "Без категории";
    document.getElementById("photoPreview").innerHTML = "";
    document.getElementById("photoFile").value = "";
    document.getElementById("photoPath").value = "";
    currentPhotoData = null;
    currentEditId = null;
    document.querySelector(".form-header h3").innerHTML = "Добавление педагога";
    document.getElementById("addBtn").style.display = "inline-flex";
    document.getElementById("editBtn").style.display = "none";
    document.getElementById("cancelBtn").style.display = "none";
    document.querySelectorAll(".input-error").forEach(e => e.remove());
    document.querySelectorAll(".char-limit-warning").forEach(e => e.classList.remove("show"));
    document.querySelectorAll(".input-group input, .input-group select").forEach(e => {
        e.style.borderColor = "#e2e8f0";
        e.style.backgroundColor = "#fafbfc";
    });
}

// ===== ДОБАВЛЕНИЕ ПЕДАГОГА =====
function addTeacher() {
    let last = document.getElementById("lastname").value.trim();
    let first = document.getElementById("firstname").value.trim();
    let mid = document.getElementById("middlename").value.trim();
    let exp = document.getElementById("experience").value.trim();
    let position = document.getElementById("position").value.trim();
    let department = document.getElementById("department").value.trim();

    if (!validateTeacher(last, first)) return;

    let formData = new FormData();
    formData.append('lastname', last);
    formData.append('firstname', first);
    formData.append('middlename', mid);
    formData.append('position', position);
    formData.append('department', department);
    formData.append('experience', exp);
    formData.append('category', document.getElementById("category").value);

    fetch('api/add_teacher.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTeachersFromDB();
            clearForm();
            showFirework();
            alert(`✔️ Педагог ${last} ${first} добавлен!`);
        } else {
            alert('Ошибка: ' + (data.error || 'неизвестная ошибка'));
        }
    })
    .catch(e => alert('Ошибка: ' + e));
}

// ===== РЕДАКТИРОВАНИЕ ПЕДАГОГА =====
function editTeacher() {
    if (!currentEditId) return;
    
    let last = document.getElementById("lastname").value.trim();
    let first = document.getElementById("firstname").value.trim();
    let mid = document.getElementById("middlename").value.trim();
    let exp = document.getElementById("experience").value.trim();
    let position = document.getElementById("position").value.trim();
    let department = document.getElementById("department").value.trim();

    if (!validateTeacher(last, first)) return;

    fetch('api/edit_teacher.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            id: currentEditId,
            lastname: last, firstname: first, middlename: mid,
            position: position, department: department,
            experience: exp, category: document.getElementById("category").value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTeachersFromDB();
            clearForm();
            alert(`✔️ Педагог ${last} ${first} обновлён!`);
        } else {
            alert('Ошибка: ' + (data.error || 'неизвестная ошибка'));
        }
    })
    .catch(e => alert('Ошибка: ' + e));
}

// ===== УДАЛЕНИЕ ПЕДАГОГА =====
function deleteTeacher(id) {
    let t = teachersList.find(t => t.id === id);
    if (t && confirm(`✖️ Удалить ${t.lastname} ${t.firstname}?`)) {
        fetch(`api/delete_teacher.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadTeachersFromDB();
                    alert(`🗑️ Педагог удалён`);
                } else {
                    alert('Ошибка удаления');
                }
            })
            .catch(e => alert('Ошибка: ' + e));
    }
}

// ===== ФУНКЦИИ ДЛЯ РАСПИСАНИЯ =====
function getScheduleForTeacher(teacherId) {
    return scheduleList.filter(s => s.teacher_id == teacherId);
}

function validateTimeFormat(timeStr) { 
    return /^(\d{1,2})[.:](\d{2})[-–](\d{1,2})[.:](\d{2})$/.test(timeStr); 
}

function isTimeValid(start, end) {
    function toMinutes(time) {
        let match = time.match(/(\d{1,2})[.:](\d{2})/);
        if (!match) return 0;
        return parseInt(match[1]) * 60 + parseInt(match[2]);
    }
    return toMinutes(start) < toMinutes(end);
}

async function showTeacherModal(id) {
    let t = teachersList.find(t => t.id === id);
    if (!t) return;

    scheduleList = await loadScheduleFromDB(id);
    let teacherSchedule = getScheduleForTeacher(id);

    let catDisplay = t.category === "Без категории" ? "—" : t.category;

    function getPhotoHtml(teacher) {
        if (teacher.photoData) return `<img src="${teacher.photoData}" alt="фото">`;
        if (teacher.photo) return `<img src="uploads/${teacher.photo}" alt="фото">`;
        return '<div style="width:120px;height:120px;border-radius:50%;background:#f1f5f9;margin:0 auto;display:flex;align-items:center;justify-content:center;font-size:48px;">👤</div>';
    }

    let days = ["Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота", "Воскресенье"];
    let byDay = {};
    days.forEach(d => byDay[d] = []);
    teacherSchedule.forEach(s => { if (byDay[s.day]) byDay[s.day].push(s); });

    let scheduleHtml = `
        <div class="schedule-title">📅 РАСПИСАНИЕ ЗАНЯТИЙ</div>
        <div class="schedule-days">
            ${days.map(d => `
                <div class="schedule-day">
                    <div class="schedule-day-name">${d}</div>
                    <div class="schedule-day-lessons">
                        ${byDay[d].length ? byDay[d].map(item => `<div class="schedule-time"><strong>${escapeHtml(item.time)}</strong> — ${escapeHtml(item.lesson)}</div>`).join("") : '<div class="schedule-time" style="color:#94a3b8;">— нет занятий —</div>'}
                    </div>
                </div>
            `).join("")}
        </div>
    `;

    document.getElementById("modalContent").innerHTML = `
        <div class="modal-photo">${getPhotoHtml(t)}</div>
        <div class="modal-name">${escapeHtml(t.lastname)} ${escapeHtml(t.firstname)} ${escapeHtml(t.middlename || "")}</div>
        <div class="modal-position">${escapeHtml(t.position)}</div>
        <div class="modal-info">
            <div class="modal-info-item"><div class="modal-info-label">Объединение</div><div class="modal-info-value">${escapeHtml(t.department)}</div></div>
            <div class="modal-info-item"><div class="modal-info-label">Стаж</div><div class="modal-info-value">${t.experience}л</div></div>
            <div class="modal-info-item"><div class="modal-info-label">Категория</div><div class="modal-info-value">${escapeHtml(catDisplay)}</div></div>
        </div>
        ${teacherSchedule.length > 0 ? scheduleHtml : '<div style="text-align:center;padding:40px;background:#f8fafc;border-radius:16px;margin:20px 0;"><p style="color:#94a3b8;">📅 Расписание отсутствует</p></div>'}
        ${isEditMode ? `
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="editFromModal(${t.id}); closeModal();">✏️ Редактировать данные</button>
                <button class="btn btn-primary" onclick="openEditScheduleModal(${t.id})">📅 Редактировать расписание</button>
                <button class="btn btn-danger" onclick="deleteTeacher(${t.id}); closeModal();">🗑️ Удалить</button>
            </div>
        ` : '<div style="text-align:center;padding:16px;color:#94a3b8;font-size:12px;border-top:1px solid #e2e8f0;margin-top:24px;">💡 Для редактирования включите режим редактирования</div>'}
    `;
    document.getElementById("teacherModal").style.display = "flex";
}

function editFromModal(id) {
    let t = teachersList.find(t => t.id === id);
    if (t) {
        document.getElementById("lastname").value = t.lastname;
        document.getElementById("firstname").value = t.firstname;
        document.getElementById("middlename").value = t.middlename || "";
        document.getElementById("position").value = t.position;
        document.getElementById("department").value = t.department;
        document.getElementById("experience").value = t.experience;
        document.getElementById("category").value = t.category;
        if (t.photoData) {
            document.getElementById("photoPreview").innerHTML = `<img src="${t.photoData}" style="width:100%;height:100%;object-fit:cover;">`;
            currentPhotoData = t.photoData;
        }
        currentEditId = id;
        document.querySelector(".form-header h3").innerHTML = "✏️ Редактирование педагога";
        document.getElementById("addBtn").style.display = "none";
        document.getElementById("editBtn").style.display = "inline-flex";
        document.getElementById("cancelBtn").style.display = "inline-flex";
        if (!isEditMode) toggleEditMode();
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
}

function closeModal() { 
    document.getElementById("teacherModal").style.display = "none"; 
}

// ===== РЕДАКТИРОВАНИЕ РАСПИСАНИЯ =====
async function openEditScheduleModal(teacherId) {
    editingScheduleTeacherId = teacherId;
    let t = teachersList.find(t => t.id === teacherId);
    if (!t) return;
    
    scheduleList = await loadScheduleFromDB(teacherId);
    tempSchedule = [...scheduleList];
    renderScheduleEditModal(t);
    document.getElementById("editScheduleModal").style.display = "flex";
}

function closeScheduleEditModal() {
    document.getElementById("editScheduleModal").style.display = "none";
    editingScheduleTeacherId = null;
    tempSchedule = [];
}

function renderScheduleEditModal(teacher) {
    let days = ["Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота", "Воскресенье"];
    let byDay = {};
    days.forEach(d => byDay[d] = []);
    tempSchedule.forEach(s => { if (byDay[s.day]) byDay[s.day].push(s); });
    days.forEach(d => byDay[d].sort((a, b) => a.time.localeCompare(b.time)));

    let daysHtml = days.map((day, dayIdx) => {
        let items = byDay[day];
        let itemsHtml = items.length ? items.map((item, itemIdx) => `
            <div class="schedule-edit-item">
                <span class="item-time">${escapeHtml(item.time)}</span>
                <span class="item-lesson">${escapeHtml(item.lesson)}</span>
                <button class="btn-remove-item" onclick="removeScheduleItem(${dayIdx}, ${itemIdx})" title="Удалить">✕</button>
            </div>
        `).join("") : '<div class="schedule-edit-empty">Нет занятий</div>';

        return `
            <div class="schedule-edit-day">
                <div class="schedule-edit-day-header"><span>${day}</span><span class="day-count">${items.length} зан.</span></div>
                <div class="schedule-edit-day-body">
                    ${itemsHtml}
                    <div class="schedule-edit-add-form">
                        <input type="text" id="newTime_${dayIdx}" placeholder="Время (14.00-14.40)" oninput="validateTimeInput(this)">
                        <input type="text" id="newLesson_${dayIdx}" placeholder="Название" value="${escapeHtml(teacher.department)}">
                        <button class="btn-add-item" onclick="addScheduleItem(${dayIdx}, '${day}')">➕</button>
                    </div>
                </div>
            </div>
        `;
    }).join("");

    document.getElementById("scheduleEditContent").innerHTML = `
        <div class="schedule-edit-header"><h2>📅 Редактирование расписания</h2><p>${escapeHtml(teacher.lastname)} ${escapeHtml(teacher.firstname)} ${escapeHtml(teacher.middlename || "")}</p></div>
        <div class="schedule-edit-days">${daysHtml}</div>
        <div class="schedule-edit-actions">
            <button class="btn btn-success" onclick="saveScheduleChanges()">💾 Сохранить расписание</button>
            <button class="btn btn-secondary" onclick="closeScheduleEditModal()">✖️ Отмена</button>
            <button class="btn btn-danger" onclick="clearAllSchedule()">🗑️ Очистить всё</button>
        </div>
    `;
}

function validateTimeInput(input) {
    let time = input.value.trim();
    input.classList.toggle("time-input-error", time && !validateTimeFormat(time));
}

async function addScheduleItem(dayIdx, dayName) {
    let timeInput = document.getElementById(`newTime_${dayIdx}`);
    let lessonInput = document.getElementById(`newLesson_${dayIdx}`);
    let time = timeInput.value.trim();
    let lesson = lessonInput.value.trim();

    if (!time) { alert("✖️ Введите время!"); timeInput.focus(); return; }
    if (!validateTimeFormat(time)) { alert("✖️ Формат: 14.00-14.40"); timeInput.classList.add("time-input-error"); timeInput.focus(); setTimeout(() => timeInput.classList.remove("time-input-error"), 2000); return; }
    let [start, end] = time.split(/[-–]/);
    if (start === end) { alert("✖️ Время начала и конца не могут совпадать!"); timeInput.classList.add("time-input-error"); return; }
    if (!isTimeValid(start, end)) { alert("✖️ Начало должно быть раньше конца!"); timeInput.classList.add("time-input-error"); return; }
    if (!lesson) { alert("✖️ Введите название!"); lessonInput.focus(); return; }

    try {
        const response = await fetch('api/add_schedule_item.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                teacher_id: editingScheduleTeacherId,
                day: dayName,
                time: time,
                lesson: lesson
            })
        });
        const data = await response.json();
        if (data.success) {
            scheduleList = await loadScheduleFromDB(editingScheduleTeacherId);
            tempSchedule = [...scheduleList];
            let t = teachersList.find(t => t.id === editingScheduleTeacherId);
            renderScheduleEditModal(t);
            timeInput.value = '';
            lessonInput.value = '';
        } else {
            alert('Ошибка добавления занятия');
        }
    } catch(e) {
        alert('Ошибка: ' + e);
    }
}

async function removeScheduleItem(dayIdx, itemIdx) {
    let days = ["Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота", "Воскресенье"];
    let dayName = days[dayIdx];
    let dayItems = tempSchedule.filter(s => s.day === dayName).sort((a, b) => a.time.localeCompare(b.time));
    if (itemIdx >= 0 && itemIdx < dayItems.length) {
        let itemToRemove = dayItems[itemIdx];
        let id = itemToRemove.id;
        
        if (id) {
            try {
                const response = await fetch(`api/delete_schedule_item.php?id=${id}`);
                const data = await response.json();
                if (data.success) {
                    scheduleList = await loadScheduleFromDB(editingScheduleTeacherId);
                    tempSchedule = [...scheduleList];
                    let t = teachersList.find(t => t.id === editingScheduleTeacherId);
                    renderScheduleEditModal(t);
                } else {
                    alert('Ошибка удаления');
                }
            } catch(e) {
                alert('Ошибка: ' + e);
            }
        } else {
            let removeIdx = tempSchedule.findIndex(s => s.day === itemToRemove.day && s.time === itemToRemove.time && s.lesson === itemToRemove.lesson);
            if (removeIdx !== -1) tempSchedule.splice(removeIdx, 1);
            let t = teachersList.find(t => t.id === editingScheduleTeacherId);
            renderScheduleEditModal(t);
        }
    }
}

async function saveScheduleChanges() {
    closeScheduleEditModal();
    if (editingScheduleTeacherId) {
        scheduleList = await loadScheduleFromDB(editingScheduleTeacherId);
        showTeacherModal(editingScheduleTeacherId);
    }
    alert("✔️ Расписание сохранено!");
}

async function clearAllSchedule() {
    if (confirm("🗑️ Удалить все занятия?")) {
        try {
            const response = await fetch(`api/clear_schedule.php?teacher_id=${editingScheduleTeacherId}`);
            const data = await response.json();
            if (data.success) {
                scheduleList = await loadScheduleFromDB(editingScheduleTeacherId);
                tempSchedule = [];
                let t = teachersList.find(t => t.id === editingScheduleTeacherId);
                renderScheduleEditModal(t);
            } else {
                alert('Ошибка очистки расписания');
            }
        } catch(e) {
            alert('Ошибка: ' + e);
        }
    }
}

function setupPhotoUpload() {
    document.getElementById("photoFile")?.addEventListener("change", function(e) {
        let file = e.target.files[0];
        if (file) {
            if (!file.type.startsWith('image/')) { alert('⚠️ Выберите файл изображения!'); return; }
            let reader = new FileReader();
            reader.onload = function(ev) {
                currentPhotoData = ev.target.result;
                document.getElementById("photoPreview").innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
            };
            reader.readAsDataURL(file);
        }
    });
}

function setupFilters() {
    document.getElementById("deptFilterSelect")?.addEventListener("change", (e) => { currentDeptFilter = e.target.value; renderGrid(); });
    document.getElementById("catFilterSelect")?.addEventListener("change", (e) => { currentCatFilter = e.target.value; renderGrid(); });
}

function setupSortButton() {
    let btn = document.getElementById("sortBtn");
    let sortText = document.getElementById("sortText");
    if (btn) {
        btn.addEventListener("click", () => {
            if (sortMode === "az") { sortMode = "za"; if (sortText) sortText.textContent = "Я→А"; }
            else { sortMode = "az"; if (sortText) sortText.textContent = "А→Я"; }
            renderGrid();
        });
    }
}

function toggleEditMode() {
    isEditMode = !isEditMode;
    let form = document.getElementById("editForm");
    let btn = document.getElementById("toggleEditModeBtn");
    if (isEditMode) {
        form.style.display = "block";
        btn.textContent = "👀 Режим просмотра";
        btn.classList.add("active");
    } else {
        form.style.display = "none";
        btn.textContent = "✏️ Режим редактирования";
        btn.classList.remove("active");
        clearForm();
    }
}

// ===== ЭКСПОРТ CSV =====
function setupExportDropdown() {
    const btn = document.getElementById("exportDropdownBtn");
    const menu = document.getElementById("exportDropdownMenu");
    
    if (btn && menu) {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            menu.classList.toggle("show");
        });
        window.addEventListener("click", () => menu.classList.remove("show"));
        menu.querySelectorAll('a').forEach(link => {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                const exportType = link.getAttribute("data-export");
                if (exportType === 'teachers') {
                    window.location.href = 'index.php?export=1&type=teachers';
                } else if (exportType === 'schedule') {
                    exportScheduleCSV();
                }
                menu.classList.remove("show");
            });
        });
    }
}

async function exportScheduleCSV() {
    const scheduleByTeacher = {};
    
    for (let teacher of teachersList) {
        const teacherSchedule = await loadScheduleFromDB(teacher.id);
        scheduleByTeacher[`${teacher.lastname} ${teacher.firstname} ${teacher.middlename || ''}`] = {
            department: teacher.department,
            schedule: teacherSchedule
        };
    }
    
    const headers = ['Педагог', 'Объединение', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
    const rows = [];
    
    Object.keys(scheduleByTeacher).sort().forEach(teacherName => {
        const data = scheduleByTeacher[teacherName];
        const byDay = {
            'Понедельник': [], 'Вторник': [], 'Среда': [], 'Четверг': [], 
            'Пятница': [], 'Суббота': [], 'Воскресенье': []
        };
        data.schedule.forEach(s => {
            if (byDay[s.day]) byDay[s.day].push(`${s.time} ${s.lesson}`);
        });
        
        rows.push([
            teacherName,
            data.department,
            byDay['Понедельник'].join('; '),
            byDay['Вторник'].join('; '),
            byDay['Среда'].join('; '),
            byDay['Четверг'].join('; '),
            byDay['Пятница'].join('; '),
            byDay['Суббота'].join('; '),
            byDay['Воскресенье'].join('; ')
        ]);
    });
    
    const csvContent = [headers, ...rows]
        .map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(';'))
        .join('\n');
    
    const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'расписание_дюц.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
    
    alert(`✔️ Расписание экспортировано!`);
}

// ===== ИНИЦИАЛИЗАЦИЯ =====
document.addEventListener("DOMContentLoaded", () => {
    loadFromStorage();
    loadTeachersFromDB();
    setupPhotoUpload();
    setupFilters();
    setupSortButton();
    setupInputValidation();
    setupClearSelection();
    setupExportDropdown();

    document.getElementById("addBtn")?.addEventListener("click", addTeacher);
    document.getElementById("editBtn")?.addEventListener("click", editTeacher);
    document.getElementById("cancelBtn")?.addEventListener("click", clearForm);
    document.getElementById("searchInput")?.addEventListener("input", () => renderGrid());
    document.getElementById("closeModalBtn")?.addEventListener("click", closeModal);
    document.getElementById("toggleEditModeBtn")?.addEventListener("click", toggleEditMode);
    document.getElementById("closeScheduleModalBtn")?.addEventListener("click", closeScheduleEditModal);

    window.onclick = (e) => {
        if (e.target === document.getElementById("teacherModal")) closeModal();
        if (e.target === document.getElementById("editScheduleModal")) closeScheduleEditModal();
    };
});