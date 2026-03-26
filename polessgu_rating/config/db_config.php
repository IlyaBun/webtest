<?php
/**
 * Конфигурация подключения к базе данных
 * Система оценки успеваемости студентов инженерного факультета ПолесГУ
 */

// Параметры подключения к MySQL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'polessgu_rating');
define('DB_CHARSET', 'utf8mb4');

// Создание подключения
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Ошибка подключения: " . $conn->connect_error);
        }
        
        $conn->set_charset(DB_CHARSET);
        $conn->query("SET NAMES 'utf8mb4'");
        $conn->query("SET CHARACTER SET 'utf8mb4'");
    }
    
    return $conn;
}

// Функция для безопасного выполнения запросов
function dbQuery($sql, $params = []) {
    $conn = getDbConnection();
    
    if (empty($params)) {
        $result = $conn->query($sql);
    } else {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Ошибка подготовки запроса: " . $conn->error);
        }
        
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
    
    if ($result === false && $conn->error) {
        throw new Exception("Ошибка выполнения запроса: " . $conn->error);
    }
    
    return $result;
}

// Функция для получения одной строки
function dbFetchOne($sql, $params = []) {
    $result = dbQuery($sql, $params);
    if ($result instanceof mysqli_result) {
        return $result->fetch_assoc();
    }
    return null;
}

// Функция для получения всех строк
function dbFetchAll($sql, $params = []) {
    $result = dbQuery($sql, $params);
    $rows = [];
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// Функция для вставки с получением ID
function dbInsert($table, $data) {
    $conn = getDbConnection();
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $values = array_values($data);
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Ошибка подготовки запроса: " . $conn->error);
    }
    
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $insertId = $conn->insert_id;
    $stmt->close();
    
    return $insertId;
}

// Функция для обновления
function dbUpdate($table, $data, $where, $whereParams = []) {
    $conn = getDbConnection();
    $setParts = [];
    foreach (array_keys($data) as $column) {
        $setParts[] = "$column = ?";
    }
    $setClause = implode(', ', $setParts);
    $values = array_merge(array_values($data), $whereParams);
    
    $sql = "UPDATE $table SET $setClause WHERE $where";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Ошибка подготовки запроса: " . $conn->error);
    }
    
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $stmt->bind_param($types, ...$values);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Функция для удаления
function dbDelete($table, $where, $params = []) {
    $conn = getDbConnection();
    $sql = "DELETE FROM $table WHERE $where";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Ошибка подготовки запроса: " . $conn->error);
    }
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Закрытие соединения (вызывается автоматически при завершении скрипта)
function closeDbConnection() {
    global $conn;
    if ($conn !== null) {
        $conn->close();
        $conn = null;
    }
}

register_shutdown_function('closeDbConnection');
?>
