<?php
/**
 * Конфигурация приложения
 * Система оценки успеваемости студентов инженерного факультета ПолесГУ
 */

// Настройки базы данных SQLite
define('DB_PATH', __DIR__ . '/database.sqlite');

// Настройки приложения
define('APP_NAME', 'Система оценки успеваемости ПолесГУ');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/');

// Настройки сессии
ini_set('session.cookie_httponly', 1);
session_start();

// Функция подключения к базе данных
function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $db = new PDO('sqlite:' . DB_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Создаем таблицы и заполняем данными при первом запуске
            initializeDatabase($db);
            
        } catch (PDOException $e) {
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }
    
    return $db;
}

// Инициализация базы данных
function initializeDatabase($db) {
    // Проверка существования таблиц
    $tablesExist = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
    
    if (!$tablesExist) {
        // Создание таблиц
        $db->exec("
            -- Таблица пользователей
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                full_name TEXT NOT NULL,
                role TEXT NOT NULL CHECK(role IN ('admin', 'teacher')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            -- Таблица специальностей
            CREATE TABLE IF NOT EXISTS specialties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                faculty TEXT NOT NULL
            );
            
            -- Таблица кафедр
            CREATE TABLE IF NOT EXISTS departments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                code TEXT UNIQUE NOT NULL
            );
            
            -- Таблица групп
            CREATE TABLE IF NOT EXISTS groups (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                course INTEGER NOT NULL CHECK(course BETWEEN 1 AND 5),
                specialty_id INTEGER NOT NULL,
                FOREIGN KEY (specialty_id) REFERENCES specialties(id)
            );
            
            -- Таблица студентов
            CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                last_name TEXT NOT NULL,
                first_name TEXT NOT NULL,
                middle_name TEXT,
                group_id INTEGER NOT NULL,
                enrollment_year INTEGER NOT NULL,
                FOREIGN KEY (group_id) REFERENCES groups(id)
            );
            
            -- Таблица дисциплин
            CREATE TABLE IF NOT EXISTS disciplines (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                department_id INTEGER NOT NULL,
                teacher_id INTEGER,
                semester INTEGER NOT NULL CHECK(semester BETWEEN 1 AND 10),
                hours INTEGER NOT NULL,
                FOREIGN KEY (department_id) REFERENCES departments(id),
                FOREIGN KEY (teacher_id) REFERENCES users(id)
            );
            
            -- Таблица оценок
            CREATE TABLE IF NOT EXISTS grades (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                discipline_id INTEGER NOT NULL,
                grade INTEGER NOT NULL CHECK(grade BETWEEN 1 AND 10),
                date_recorded DATE NOT NULL,
                semester INTEGER NOT NULL,
                academic_year TEXT NOT NULL,
                FOREIGN KEY (student_id) REFERENCES students(id),
                FOREIGN KEY (discipline_id) REFERENCES disciplines(id)
            );
            
            -- Таблица учебных периодов
            CREATE TABLE IF NOT EXISTS academic_periods (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                is_active INTEGER DEFAULT 0
            );
        ");
        
        // Заполнение данными
        fillDatabaseWithData($db);
    }
}

// Заполнение базы данных реальными примерами
function fillDatabaseWithData($db) {
    // Пользователи
    $users = [
        ['admin', 'admin123', 'Администратор Системы', 'admin'],
        ['petrov', 'petrov123', 'Петров Иван Сергеевич', 'teacher'],
        ['sidorova', 'sidorova123', 'Сидорова Анна Владимировна', 'teacher'],
        ['kozlov', 'kozlov123', 'Козлов Дмитрий Александрович', 'teacher'],
        ['novik', 'novik123', 'Новик Елена Петровна', 'teacher']
    ];
    
    foreach ($users as $user) {
        $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute($user);
    }
    
    // Специальности
    $specialties = [
        ['1-36 05 01', 'Машины и аппараты пищевых производств', 'Инженерный факультет'],
        ['1-36 05 02', 'Полиграфическое производство', 'Инженерный факультет'],
        ['1-36 06 01', 'Техническая эксплуатация оборудования', 'Инженерный факультет'],
        ['1-36 07 01', 'Робототехнические системы', 'Инженерный факультет'],
        ['1-36 08 01', 'Метрология и техническая диагностика', 'Инженерный факультет']
    ];
    
    foreach ($specialties as $spec) {
        $stmt = $db->prepare("INSERT INTO specialties (code, name, faculty) VALUES (?, ?, ?)");
        $stmt->execute($spec);
    }
    
    // Кафедры
    $departments = [
        ['Кафедра механики и машиноведения', 'КММ'],
        ['Кафедра автоматизации и робототехники', 'КАиР'],
        ['Кафедра информационных технологий', 'КИТ'],
        ['Кафедра высшей математики и физики', 'КВМиФ'],
        ['Кафедра гуманитарных дисциплин', 'КГД']
    ];
    
    foreach ($departments as $dept) {
        $stmt = $db->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
        $stmt->execute($dept);
    }
    
    // Группы
    $groups = [
        ['МП-11', 1, 1], ['МП-21', 2, 1], ['МП-31', 3, 1],
        ['ПР-11', 1, 2], ['ПР-21', 2, 2],
        ['СТ-11', 1, 3], ['СТ-21', 2, 3], ['СТ-31', 3, 3],
        ['РТ-11', 1, 4], ['МТ-11', 1, 5]
    ];
    
    foreach ($groups as $group) {
        $stmt = $db->prepare("INSERT INTO groups (name, course, specialty_id) VALUES (?, ?, ?)");
        $stmt->execute($group);
    }
    
    // Студенты (реальные белорусские фамилии)
    $students = [
        // Группа МП-11
        ['Иванов', 'Александр', 'Петрович', 1, 2024],
        ['Козлов', 'Дмитрий', 'Сергеевич', 1, 2024],
        ['Новик', 'Анна', 'Владимировна', 1, 2024],
        ['Борисевич', 'Максим', 'Александрович', 1, 2024],
        ['Григорьев', 'Илья', 'Дмитриевич', 1, 2024],
        ['Демидов', 'Артем', 'Андреевич', 1, 2024],
        ['Ермаков', 'Никита', 'Сергеевич', 1, 2024],
        ['Жук', 'Павел', 'Викторович', 1, 2024],
        ['Зайцев', 'Кирилл', 'Алексеевич', 1, 2024],
        ['Кравченко', 'Владислав', 'Олегович', 1, 2024],
        
        // Группа МП-21
        ['Леонов', 'Егор', 'Михайлович', 2, 2023],
        ['Мельник', 'Андрей', 'Игоревич', 2, 2023],
        ['Науменко', 'Станислав', 'Петрович', 2, 2023],
        ['Олейник', 'Роман', 'Владимирович', 2, 2023],
        ['Павлов', 'Денис', 'Александрович', 2, 2023],
        ['Романов', 'Виктор', 'Сергеевич', 2, 2023],
        ['Савченко', 'Алексей', 'Дмитриевич', 2, 2023],
        ['Тимофеев', 'Максим', 'Андреевич', 2, 2023],
        ['Усов', 'Иван', 'Петрович', 2, 2023],
        ['Федоров', 'Сергей', 'Владимирович', 2, 2023],
        
        // Группа МП-31
        ['Харитонов', 'Артём', 'Сергеевич', 3, 2022],
        ['Цыганков', 'Николай', 'Александрович', 3, 2022],
        ['Шевченко', 'Дмитрий', 'Игоревич', 3, 2022],
        ['Якубович', 'Андрей', 'Владимирович', 3, 2022],
        ['Абрамов', 'Павел', 'Сергеевич', 3, 2022],
        ['Белоус', 'Максим', 'Дмитриевич', 3, 2022],
        ['Васильев', 'Илья', 'Александрович', 3, 2022],
        ['Голуб', 'Артём', 'Викторович', 3, 2022],
        ['Дорошенко', 'Никита', 'Андреевич', 3, 2022],
        ['Елисеев', 'Денис', 'Сергеевич', 3, 2022],
        
        // Группа ПР-11
        ['Жданов', 'Владислав', 'Петрович', 4, 2024],
        ['Зубков', 'Егор', 'Алексеевич', 4, 2024],
        ['Игнатов', 'Станислав', 'Владимирович', 4, 2024],
        ['Карпов', 'Роман', 'Дмитриевич', 4, 2024],
        ['Лебедев', 'Алексей', 'Сергеевич', 4, 2024],
        ['Морозов', 'Виктор', 'Андреевич', 4, 2024],
        ['Нестеров', 'Максим', 'Игоревич', 4, 2024],
        ['Орлов', 'Иван', 'Петрович', 4, 2024],
        ['Прохоров', 'Сергей', 'Владимирович', 4, 2024],
        ['Рыбаков', 'Дмитрий', 'Александрович', 4, 2024],
        
        // Группа ПР-21
        ['Соколов', 'Артём', 'Сергеевич', 5, 2023],
        ['Тарасов', 'Николай', 'Викторович', 5, 2023],
        ['Ушаков', 'Андрей', 'Дмитриевич', 5, 2023],
        ['Фролов', 'Павел', 'Александрович', 5, 2023],
        ['Цветков', 'Максим', 'Игоревич', 5, 2023],
        ['Чернов', 'Илья', 'Сергеевич', 5, 2023],
        ['Щербаков', 'Денис', 'Владимирович', 5, 2023],
        ['Юдин', 'Виктор', 'Андреевич', 5, 2023],
        ['Яковлев', 'Алексей', 'Петрович', 5, 2023],
        ['Аксёнов', 'Станислав', 'Дмитриевич', 5, 2023],
        
        // Группа СТ-11
        ['Беляев', 'Роман', 'Сергеевич', 6, 2024],
        ['Виноградов', 'Максим', 'Владимирович', 6, 2024],
        ['Гусев', 'Иван', 'Александрович', 6, 2024],
        ['Давыдов', 'Дмитрий', 'Игоревич', 6, 2024],
        ['Егоров', 'Андрей', 'Петрович', 6, 2024],
        ['Журавлёв', 'Павел', 'Сергеевич', 6, 2024],
        ['Захаров', 'Максим', 'Викторович', 6, 2024],
        ['Ильин', 'Илья', 'Андреевич', 6, 2024],
        ['Киселёв', 'Денис', 'Дмитриевич', 6, 2024],
        ['Кузнецов', 'Виктор', 'Сергеевич', 6, 2024],
        
        // Группа СТ-21
        ['Лазарев', 'Алексей', 'Владимирович', 7, 2023],
        ['Медведев', 'Станислав', 'Александрович', 7, 2023],
        ['Никитин', 'Роман', 'Игоревич', 7, 2023],
        ['Осипов', 'Максим', 'Петрович', 7, 2023],
        ['Попова', 'Анна', 'Сергеевна', 7, 2023],
        ['Рогов', 'Иван', 'Дмитриевич', 7, 2023],
        ['Семёнов', 'Андрей', 'Владимирович', 7, 2023],
        ['Титов', 'Павел', 'Александрович', 7, 2023],
        ['Фомин', 'Дмитрий', 'Сергеевич', 7, 2023],
        ['Хомяков', 'Максим', 'Игоревич', 7, 2023],
        
        // Группа СТ-31
        ['Цветаев', 'Илья', 'Викторович', 8, 2022],
        ['Чижов', 'Денис', 'Андреевич', 8, 2022],
        ['Шаповалов', 'Виктор', 'Дмитриевич', 8, 2022],
        ['Шишкин', 'Алексей', 'Сергеевич', 8, 2022],
        ['Щукин', 'Станислав', 'Владимирович', 8, 2022],
        ['Эрнст', 'Роман', 'Александрович', 8, 2022],
        ['Юрьев', 'Максим', 'Игоревич', 8, 2022],
        ['Ярославцев', 'Иван', 'Петрович', 8, 2022],
        ['Агафонов', 'Андрей', 'Дмитриевич', 8, 2022],
        ['Блохин', 'Павел', 'Сергеевич', 8, 2022],
        
        // Группа РТ-11
        ['Власов', 'Дмитрий', 'Владимирович', 9, 2024],
        ['Громов', 'Максим', 'Александрович', 9, 2024],
        ['Долгов', 'Илья', 'Игоревич', 9, 2024],
        ['Ермаков', 'Денис', 'Петрович', 9, 2024],
        ['Зимин', 'Виктор', 'Сергеевич', 9, 2024],
        ['Исаев', 'Алексей', 'Андреевич', 9, 2024],
        ['Крылов', 'Станислав', 'Дмитриевич', 9, 2024],
        ['Ларионов', 'Роман', 'Владимирович', 9, 2024],
        ['Марков', 'Максим', 'Игоревич', 9, 2024],
        ['Наумов', 'Иван', 'Петрович', 9, 2024],
        
        // Группа МТ-11
        ['Орехов', 'Андрей', 'Сергеевич', 10, 2024],
        ['Платонов', 'Павел', 'Владимирович', 10, 2024],
        ['Родионов', 'Дмитрий', 'Александрович', 10, 2024],
        ['Соловьёв', 'Максим', 'Игоревич', 10, 2024],
        ['Терентьев', 'Илья', 'Дмитриевич', 10, 2024],
        ['Уваров', 'Денис', 'Сергеевич', 10, 2024],
        ['Филиппов', 'Виктор', 'Андреевич', 10, 2024],
        ['Харламов', 'Алексей', 'Петрович', 10, 2024],
        ['Царёв', 'Станислав', 'Владимирович', 10, 2024],
        ['Шестаков', 'Роман', 'Александрович', 10, 2024]
    ];
    
    foreach ($students as $student) {
        $stmt = $db->prepare("INSERT INTO students (last_name, first_name, middle_name, group_id, enrollment_year) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($student);
    }
    
    // Дисциплины
    $disciplines = [
        ['Высшая математика', 4, 2, 1, 144],
        ['Физика', 4, 2, 1, 108],
        ['Сопротивление материалов', 1, 3, 3, 72],
        ['Теоретическая механика', 1, 2, 2, 72],
        ['Детали машин', 1, 4, 5, 90],
        ['Основы программирования', 3, 1, 1, 108],
        ['Базы данных', 3, 2, 3, 72],
        ['Инженерная графика', 1, 1, 1, 90],
        ['Материаловедение', 1, 2, 2, 54],
        ['Электротехника', 2, 3, 4, 72],
        ['Гидравлика', 1, 3, 4, 54],
        ['Теплотехника', 1, 4, 5, 72],
        ['Автоматизация производственных процессов', 2, 5, 7, 90],
        ['Робототехника', 2, 5, 8, 72],
        ['Метрология и технические измерения', 5, 4, 6, 54]
    ];
    
    foreach ($disciplines as $disc) {
        $stmt = $db->prepare("INSERT INTO disciplines (name, department_id, semester, teacher_id, hours) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($disc);
    }
    
    // Генерация оценок
    generateGrades($db);
    
    // Учебные периоды
    $periods = [
        ['Осенний семестр 2024/2025', '2024-09-01', '2025-01-31', 1],
        ['Весенний семестр 2024/2025', '2025-02-15', '2025-06-30', 0],
        ['Осенний семестр 2023/2024', '2023-09-01', '2024-01-31', 0]
    ];
    
    foreach ($periods as $period) {
        $stmt = $db->prepare("INSERT INTO academic_periods (name, start_date, end_date, is_active) VALUES (?, ?, ?, ?)");
        $stmt->execute($period);
    }
}

// Генерация реалистичных оценок
function generateGrades($db) {
    $students = $db->query("SELECT id FROM students")->fetchAll(PDO::FETCH_COLUMN);
    $disciplines = $db->query("SELECT id FROM disciplines")->fetchAll(PDO::FETCH_COLUMN);
    
    $grades = [];
    $academicYears = ['2023/2024', '2024/2025'];
    
    foreach ($students as $studentId) {
        // Каждый студент имеет оценки по 5-8 предметам
        $numDisciplines = rand(5, 8);
        $selectedDisciplines = array_rand(array_flip($disciplines), min($numDisciplines, count($disciplines)));
        
        if (!is_array($selectedDisciplines)) {
            $selectedDisciplines = [$selectedDisciplines];
        }
        
        foreach ($selectedDisciplines as $disciplineId) {
            // Генерируем 1-3 оценки по каждому предмету
            $numGrades = rand(1, 3);
            
            for ($i = 0; $i < $numGrades; $i++) {
                // Распределение оценок: больше хороших, меньше плохих
                $rand = rand(1, 100);
                if ($rand <= 15) {
                    $grade = rand(1, 4); // Низкие оценки (15%)
                } elseif ($rand <= 40) {
                    $grade = rand(5, 6); // Средние оценки (25%)
                } elseif ($rand <= 75) {
                    $grade = rand(7, 8); // Хорошие оценки (35%)
                } else {
                    $grade = rand(9, 10); // Отличные оценки (25%)
                }
                
                $semester = rand(1, 8);
                $year = $academicYears[array_rand($academicYears)];
                $day = rand(1, 28);
                $month = rand(9, 12);
                $date = sprintf('%d-%02d-%02d', 2024, $month, $day);
                
                $grades[] = [$studentId, $disciplineId, $grade, $date, $semester, $year];
            }
        }
    }
    
    // Вставка оценок
    $stmt = $db->prepare("INSERT INTO grades (student_id, discipline_id, grade, date_recorded, semester, academic_year) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($grades as $grade) {
        $stmt->execute($grade);
    }
}

// Вспомогательные функции
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /modules/auth/login.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
}

// CSRF защита
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
