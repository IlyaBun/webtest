-- База данных: polessgu_rating
-- Система оценки успеваемости студентов инженерного факультета ПолесГУ

CREATE DATABASE IF NOT EXISTS polessgu_rating CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE polessgu_rating;

-- Таблица пользователей (преподаватели и администраторы)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'teacher') NOT NULL DEFAULT 'teacher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица специальностей
CREATE TABLE specialties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    faculty VARCHAR(100) DEFAULT 'Инженерный факультет'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица групп
CREATE TABLE groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) UNIQUE NOT NULL,
    specialty_id INT,
    course INT NOT NULL,
    FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица студентов
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    group_id INT NOT NULL,
    date_of_birth DATE,
    enrollment_year INT NOT NULL,
    status ENUM('active', 'expelled', 'graduated', 'academic_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE RESTRICT,
    INDEX idx_group (group_id),
    INDEX idx_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица кафедр
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица дисциплин
CREATE TABLE disciplines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    department_id INT,
    teacher_id INT,
    semester INT,
    hours_total INT,
    hours_lecture INT,
    hours_practice INT,
    control_type ENUM('exam', 'credit', 'exam_and_credit') DEFAULT 'exam',
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица оценок
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    discipline_id INT NOT NULL,
    grade INT NOT NULL CHECK (grade BETWEEN 1 AND 10),
    grade_date DATE NOT NULL,
    semester INT NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    attempt INT DEFAULT 1,
    comments TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (discipline_id) REFERENCES disciplines(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student (student_id),
    INDEX idx_discipline (discipline_id),
    INDEX idx_grade_date (grade_date),
    INDEX idx_academic_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица журналов посещаемости (опционально)
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    discipline_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    comments TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (discipline_id) REFERENCES disciplines(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, discipline_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ЗАПОЛНЕНИЕ ДАННЫМИ (РЕАЛЬНЫЕ ПРИМЕРЫ)
-- ============================================

-- Пользователи (пароли без хэширования, как требуется)
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', 'admin123', 'Иванов Иван Иванович', 'admin@polessu.by', 'admin'),
('petrov', 'petrov123', 'Петров Петр Петрович', 'petrov@polessu.by', 'teacher'),
('sidorova', 'sidorova123', 'Сидорова Анна Михайловна', 'sidorova@polessu.by', 'teacher'),
('kozlov', 'kozlov123', 'Козлов Дмитрий Сергеевич', 'kozlov@polessu.by', 'teacher'),
('novikova', 'novikova123', 'Новикова Елена Владимировна', 'novikova@polessu.by', 'teacher');

-- Специальности инженерного факультета ПолесГУ
INSERT INTO specialties (code, name, faculty) VALUES
('1-36 05 01', 'Машины и аппараты пищевых производств', 'Инженерный факультет'),
('1-36 05 02', 'Полиграфическое производство', 'Инженерный факультет'),
('1-36 03 01', 'Технология машиностроения', 'Инженерный факультет'),
('1-36 01 08', 'Вакуумная и компрессорная техника', 'Инженерный факультет'),
('1-36 07 01', 'Робототехнические системы и комплексы', 'Инженерный факультет');

-- Кафедры
INSERT INTO departments (name, code) VALUES
('Кафедра механики и машиноведения', 'КММ'),
('Кафедра технологии машиностроения', 'КТМ'),
('Кафедра автоматизации производственных процессов', 'КАПП'),
('Кафедра высшей математики и физики', 'КВМиФ'),
('Кафедра гуманитарных дисциплин', 'КГД');

-- Группы
INSERT INTO groups (name, specialty_id, course) VALUES
('МП-11', 1, 1),
('МП-21', 1, 2),
('МП-31', 1, 3),
('ПП-11', 2, 1),
('ПП-21', 2, 2),
('ТМ-11', 3, 1),
('ТМ-21', 3, 2),
('ТМ-31', 3, 3),
('ВКТ-11', 4, 1),
('РТС-11', 5, 1);

-- Студенты (разные группы, разные показатели успеваемости)
INSERT INTO students (last_name, first_name, middle_name, group_id, date_of_birth, enrollment_year, status) VALUES
-- Группа МП-11 (1 курс)
('Алексиевич', 'Артем', 'Дмитриевич', 1, '2005-03-15', 2024, 'active'),
('Борисевич', 'Богдана', 'Сергеевна', 1, '2005-07-22', 2024, 'active'),
('Волков', 'Владимир', 'Александрович', 1, '2005-01-10', 2024, 'active'),
('Григорьев', 'Глеб', 'Игоревич', 1, '2005-11-05', 2024, 'active'),
('Демидова', 'Дарья', 'Максимовна', 1, '2005-04-18', 2024, 'active'),
('Ермаков', 'Егор', 'Андреевич', 1, '2005-09-30', 2024, 'active'),
('Жукова', 'Жанна', 'Викторовна', 1, '2005-06-12', 2024, 'active'),
('Зайцев', 'Захар', 'Николаевич', 1, '2005-12-25', 2024, 'active'),
('Иванова', 'Ирина', 'Петровна', 1, '2005-02-28', 2024, 'active'),
('Котов', 'Кирилл', 'Олегович', 1, '2005-08-14', 2024, 'active'),

-- Группа МП-21 (2 курс)
('Лебедев', 'Леонид', 'Васильевич', 2, '2004-05-20', 2023, 'active'),
('Мельникова', 'Мария', 'Дмитриевна', 2, '2004-03-11', 2023, 'active'),
('Никитин', 'Никита', 'Сергеевич', 2, '2004-07-07', 2023, 'active'),
('Орлова', 'Ольга', 'Алексеевна', 2, '2004-09-19', 2023, 'active'),
('Павлов', 'Павел', 'Игоревич', 2, '2004-01-25', 2023, 'active'),
('Романова', 'Руслана', 'Максимовна', 2, '2004-11-30', 2023, 'active'),
('Соколов', 'Степан', 'Владимирович', 2, '2004-04-15', 2023, 'active'),
('Тимофеева', 'Татьяна', 'Евгеньевна', 2, '2004-06-08', 2023, 'active'),
('Устинов', 'Ульян', 'Дмитриевич', 2, '2004-12-03', 2023, 'active'),
('Федоров', 'Филипп', 'Александрович', 2, '2004-02-17', 2023, 'active'),

-- Группа МП-31 (3 курс - смешанная успеваемость)
('Харитонов', 'Харитон', 'Николаевич', 3, '2003-08-22', 2022, 'active'),
('Цветкова', 'Цвета', 'Игоревна', 3, '2003-04-14', 2022, 'active'),
('Шестаков', 'Шамиль', 'Рустамович', 3, '2003-10-09', 2022, 'active'),
('Щербакова', 'Элина', 'Вадимовна', 3, '2003-06-27', 2022, 'active'),
('Юдин', 'Юрий', 'Михайлович', 3, '2003-03-05', 2022, 'active'),
('Яковлев', 'Ярослав', 'Дмитриевич', 3, '2003-09-18', 2022, 'active'),
('Абрамова', 'Алина', 'Сергеевна', 3, '2003-01-30', 2022, 'active'),
('Быков', 'Борис', 'Владимирович', 3, '2003-07-12', 2022, 'active'),
('Виноградов', 'Виталий', 'Алексеевич', 3, '2003-05-25', 2022, 'active'),
('Герасимова', 'Галина', 'Петровна', 3, '2003-11-08', 2022, 'active'),

-- Группа ПП-11 (1 курс)
('Денисов', 'Даниил', 'Максимович', 4, '2005-02-14', 2024, 'active'),
('Елисеева', 'Екатерина', 'Андреевна', 4, '2005-06-19', 2024, 'active'),
('Фролов', 'Федор', 'Ильич', 4, '2005-09-07', 2024, 'active'),
('Гусева', 'Глафира', 'Сергеевна', 4, '2005-04-23', 2024, 'active'),
('Ильин', 'Илья', 'Викторович', 4, '2005-12-11', 2024, 'active'),

-- Группа ТМ-31 (3 курс - отличники и хорошисты)
('Кудрявцев', 'Константин', 'Олегович', 8, '2003-03-28', 2022, 'active'),
('Лазарева', 'Людмила', 'Васильевна', 8, '2003-07-16', 2022, 'active'),
('Медведев', 'Максим', 'Дмитриевич', 8, '2003-05-09', 2022, 'active'),
('Назарова', 'Наталья', 'Игоревна', 8, '2003-09-21', 2022, 'active'),
('Осипов', 'Олег', 'Петрович', 8, '2003-01-04', 2022, 'active'),
('Попова', 'Полина', 'Алексеевна', 8, '2003-11-17', 2022, 'active'),
('Рябов', 'Роман', 'Сергеевич', 8, '2003-04-30', 2022, 'active'),
('Соловьева', 'Светлана', 'Михайловна', 8, '2003-08-13', 2022, 'active'),
('Тарасов', 'Тимур', 'Рустамович', 8, '2003-06-26', 2022, 'active'),
('Уварова', 'Ульяна', 'Владимировна', 8, '2003-10-02', 2022, 'active'),

-- Группа ВКТ-11 (1 курс)
('Фомин', 'Фома', 'Николаевич', 9, '2005-03-19', 2024, 'active'),
('Хохлова', 'Христина', 'Дмитриевна', 9, '2005-07-04', 2024, 'active'),
('Зубков', 'Зиновий', 'Александрович', 9, '2005-11-28', 2024, 'active'),
('Яшина', 'Яна', 'Сергеевна', 9, '2005-05-15', 2024, 'active'),
('Беляев', 'Бенедикт', 'Игоревич', 9, '2005-09-08', 2024, 'active'),

-- Группа РТС-11 (1 курс)
('Власов', 'Всеволод', 'Максимович', 10, '2005-04-07', 2024, 'active'),
('Громова', 'Гретта', 'Алексеевна', 10, '2005-08-21', 2024, 'active'),
('Дорофеев', 'Давид', 'Владимирович', 10, '2005-02-03', 2024, 'active'),
('Ежова', 'Ева', 'Дмитриевна', 10, '2005-06-16', 2024, 'active'),
('Зимин', 'Захар', 'Сергеевич', 10, '2005-10-29', 2024, 'active');

-- Дисциплины
INSERT INTO disciplines (name, department_id, teacher_id, semester, hours_total, hours_lecture, hours_practice, control_type) VALUES
('Высшая математика', 4, 3, 1, 180, 72, 72, 'exam'),
('Физика', 4, 3, 1, 144, 54, 54, 'exam'),
('Информатика', 4, 4, 1, 108, 36, 54, 'exam_and_credit'),
('Инженерная графика', 1, 2, 1, 126, 36, 72, 'credit'),
('История Беларуси', 5, 5, 1, 72, 36, 18, 'credit'),
('Иностранный язык', 5, 5, 1, 108, 54, 36, 'credit'),
('Теоретическая механика', 1, 2, 2, 144, 72, 54, 'exam'),
('Сопротивление материалов', 1, 2, 2, 126, 54, 54, 'exam'),
('Детали машин', 1, 2, 3, 162, 72, 72, 'exam'),
('Технология машиностроения', 2, 4, 3, 180, 72, 90, 'exam'),
('Гидравлика', 1, 2, 2, 108, 54, 36, 'exam'),
('Теплотехника', 1, 2, 3, 144, 72, 54, 'exam'),
('Основы робототехники', 3, 4, 3, 126, 54, 54, 'exam'),
('Программирование микроконтроллеров', 3, 4, 3, 144, 36, 90, 'exam_and_credit'),
('Полиграфические технологии', 2, 4, 2, 126, 54, 54, 'exam');

-- Оценки (реальные примеры за разные семестры и годы)
-- Отличники (средний балл 9.5-10)
INSERT INTO grades (student_id, discipline_id, grade, grade_date, semester, academic_year, created_by) VALUES
-- Кудрявцев Константин (отличник, группа ТМ-31)
(56, 1, 10, '2022-12-20', 1, '2022-2023', 3),
(56, 2, 10, '2022-12-22', 1, '2022-2023', 3),
(56, 3, 9, '2022-12-25', 1, '2022-2023', 4),
(56, 4, 10, '2022-12-18', 1, '2022-2023', 2),
(56, 5, 9, '2022-12-15', 1, '2022-2023', 5),
(56, 6, 10, '2022-12-19', 1, '2022-2023', 5),
(56, 7, 10, '2023-06-10', 2, '2022-2023', 2),
(56, 8, 10, '2023-06-15', 2, '2022-2023', 2),
(56, 9, 10, '2023-12-12', 3, '2023-2024', 2),
(56, 10, 9, '2023-12-18', 3, '2023-2024', 4),

-- Лазарева Людмила (отличница)
(57, 1, 10, '2022-12-20', 1, '2022-2023', 3),
(57, 2, 9, '2022-12-22', 1, '2022-2023', 3),
(57, 3, 10, '2022-12-25', 1, '2022-2023', 4),
(57, 4, 10, '2022-12-18', 1, '2022-2023', 2),
(57, 5, 10, '2022-12-15', 1, '2022-2023', 5),
(57, 6, 9, '2022-12-19', 1, '2022-2023', 5),
(57, 7, 10, '2023-06-10', 2, '2022-2023', 2),
(57, 8, 9, '2023-06-15', 2, '2022-2023', 2),
(57, 9, 10, '2023-12-12', 3, '2023-2024', 2),
(57, 10, 10, '2023-12-18', 3, '2023-2024', 4),

-- Хорошисты (средний балл 7.5-8.9)
-- Медведев Максим (хорошист)
(58, 1, 8, '2022-12-20', 1, '2022-2023', 3),
(58, 2, 7, '2022-12-22', 1, '2022-2023', 3),
(58, 3, 8, '2022-12-25', 1, '2022-2023', 4),
(58, 4, 9, '2022-12-18', 1, '2022-2023', 2),
(58, 5, 8, '2022-12-15', 1, '2022-2023', 5),
(58, 6, 8, '2022-12-19', 1, '2022-2023', 5),
(58, 7, 8, '2023-06-10', 2, '2022-2023', 2),
(58, 8, 7, '2023-06-15', 2, '2022-2023', 2),
(58, 9, 8, '2023-12-12', 3, '2023-2024', 2),
(58, 10, 8, '2023-12-18', 3, '2023-2024', 4),

-- Студенты с низкой успеваемостью (средний балл < 6)
-- Юдин Юрий (низкая успеваемость)
(60, 1, 4, '2022-12-20', 1, '2022-2023', 3),
(60, 2, 3, '2022-12-22', 1, '2022-2023', 3),
(60, 3, 5, '2022-12-25', 1, '2022-2023', 4),
(60, 4, 6, '2022-12-18', 1, '2022-2023', 2),
(60, 5, 5, '2022-12-15', 1, '2022-2023', 5),
(60, 6, 4, '2022-12-19', 1, '2022-2023', 5),
(60, 7, 4, '2023-06-10', 2, '2022-2023', 2),
(60, 8, 3, '2023-06-15', 2, '2022-2023', 2),

-- Быков Борис (проблемный студент)
(63, 1, 3, '2022-12-20', 1, '2022-2023', 3),
(63, 2, 4, '2022-12-22', 1, '2022-2023', 3),
(63, 3, 3, '2022-12-25', 1, '2022-2023', 4),
(63, 4, 5, '2022-12-18', 1, '2022-2023', 2),
(63, 5, 4, '2022-12-15', 1, '2022-2023', 5),
(63, 6, 3, '2022-12-19', 1, '2022-2023', 5),

-- Студенты 1 курса (только 1 семестр)
-- Алексиевич Артем (хорошист)
(1, 1, 8, '2024-12-18', 1, '2024-2025', 3),
(1, 2, 7, '2024-12-20', 1, '2024-2025', 3),
(1, 3, 8, '2024-12-22', 1, '2024-2025', 4),
(1, 4, 9, '2024-12-15', 1, '2024-2025', 2),
(1, 5, 8, '2024-12-10', 1, '2024-2025', 5),
(1, 6, 8, '2024-12-12', 1, '2024-2025', 5),

-- Борисевич Богдана (отличница)
(2, 1, 10, '2024-12-18', 1, '2024-2025', 3),
(2, 2, 9, '2024-12-20', 1, '2024-2025', 3),
(2, 3, 10, '2024-12-22', 1, '2024-2025', 4),
(2, 4, 10, '2024-12-15', 1, '2024-2025', 2),
(2, 5, 9, '2024-12-10', 1, '2024-2025', 5),
(2, 6, 10, '2024-12-12', 1, '2024-2025', 5),

-- Волков Владимир (средняя успеваемость)
(3, 1, 6, '2024-12-18', 1, '2024-2025', 3),
(3, 2, 6, '2024-12-20', 1, '2024-2025', 3),
(3, 3, 7, '2024-12-22', 1, '2024-2025', 4),
(3, 4, 6, '2024-12-15', 1, '2024-2025', 2),
(3, 5, 7, '2024-12-10', 1, '2024-2025', 5),
(3, 6, 6, '2024-12-12', 1, '2024-2025', 5),

-- Григорьев Глеб (низкая успеваемость)
(4, 1, 4, '2024-12-18', 1, '2024-2025', 3),
(4, 2, 3, '2024-12-20', 1, '2024-2025', 3),
(4, 3, 5, '2024-12-22', 1, '2024-2025', 4),
(4, 4, 4, '2024-12-15', 1, '2024-2025', 2),
(4, 5, 5, '2024-12-10', 1, '2024-2025', 5),
(4, 6, 4, '2024-12-12', 1, '2024-2025', 5);

-- Дополнительные оценки для других студентов для полноты картины
-- Группа МП-21 (2 курс, 2 семестра)
(11, 1, 9, '2023-06-10', 2, '2023-2024', 3),
(11, 7, 8, '2024-06-12', 2, '2023-2024', 2),
(11, 8, 9, '2024-06-15', 2, '2023-2024', 2),
(12, 1, 7, '2023-06-10', 2, '2023-2024', 3),
(12, 7, 8, '2024-06-12', 2, '2023-2024', 2),
(12, 8, 7, '2024-06-15', 2, '2023-2024', 2);

-- Представления для аналитики
CREATE OR REPLACE VIEW v_student_average AS
SELECT 
    s.id as student_id,
    CONCAT(s.last_name, ' ', s.first_name, ' ', s.middle_name) as full_name,
    g.name as group_name,
    sp.name as specialty,
    COUNT(gr.grade) as total_grades,
    ROUND(AVG(gr.grade), 2) as average_grade,
    MAX(CASE WHEN gr.grade >= 9 THEN 1 ELSE 0 END) as has_excellent,
    MIN(gr.grade) as min_grade
FROM students s
JOIN groups g ON s.group_id = g.id
JOIN specialties sp ON g.specialty_id = sp.id
LEFT JOIN grades gr ON s.id = gr.student_id
WHERE s.status = 'active'
GROUP BY s.id, g.name, sp.name;

CREATE OR REPLACE VIEW v_group_statistics AS
SELECT 
    g.id as group_id,
    g.name as group_name,
    g.course,
    sp.name as specialty,
    COUNT(DISTINCT s.id) as total_students,
    ROUND(AVG(gr.grade), 2) as group_average,
    SUM(CASE WHEN AVG(gr.grade) >= 9 THEN 1 ELSE 0 END) as excellent_students,
    SUM(CASE WHEN AVG(gr.grade) >= 7 AND AVG(gr.grade) < 9 THEN 1 ELSE 0 END) as good_students,
    SUM(CASE WHEN AVG(gr.grade) >= 4 AND AVG(gr.grade) < 7 THEN 1 ELSE 0 END) as satisfactory_students,
    SUM(CASE WHEN AVG(gr.grade) < 4 THEN 1 ELSE 0 END) as poor_students,
    ROUND(SUM(CASE WHEN gr.grade >= 4 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(gr.grade), 0), 2) as success_rate
FROM groups g
JOIN specialties sp ON g.specialty_id = sp.id
LEFT JOIN students s ON g.id = s.group_id AND s.status = 'active'
LEFT JOIN grades gr ON s.id = gr.student_id
GROUP BY g.id, g.name, g.course, sp.name;

CREATE OR REPLACE VIEW v_faculty_summary AS
SELECT 
    COUNT(DISTINCT s.id) as total_students,
    ROUND(AVG(gr.grade), 2) as faculty_average,
    SUM(CASE WHEN v.average_grade >= 9 THEN 1 ELSE 0 END) as excellent_count,
    SUM(CASE WHEN v.average_grade >= 7 AND v.average_grade < 9 THEN 1 ELSE 0 END) as good_count,
    SUM(CASE WHEN v.average_grade >= 4 AND v.average_grade < 7 THEN 1 ELSE 0 END) as satisfactory_count,
    SUM(CASE WHEN v.average_grade < 4 THEN 1 ELSE 0 END) as poor_count,
    ROUND(SUM(CASE WHEN gr.grade >= 4 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(gr.grade), 0), 2) as overall_success_rate
FROM students s
JOIN v_student_average v ON s.id = v.student_id
LEFT JOIN grades gr ON s.id = gr.student_id
WHERE s.status = 'active';
