<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'db';
$port = '5430';
$dbname = 'mydb';
$username = 'KLOUD';
$password = 'Homelike34';

// Попробуем разные варианты подключения для PostgreSQL
$connection_attempts = [
    "pgsql:host=$host;port=$port;dbname=$dbname",
    "pgsql:host=127.0.0.1;port=$port;dbname=$dbname",
    "pgsql:host=$host;dbname=$dbname"
];

$pdo = null;
$connection_error = '';

foreach ($connection_attempts as $dsn) {
    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        break; // Успешное подключение
    } catch(PDOException $e) {
        $connection_error = $e->getMessage();
        continue; // Пробуем следующий вариант
    }
}

// Если все попытки неудачны
if (!$pdo) {
    echo json_encode([
        'success' => false, 
        'message' => 'Не удалось подключиться к базе данных PostgreSQL. Проверьте:',
        'details' => [
            'error' => $connection_error,
            'suggestions' => [
                '1. Убедитесь, что PostgreSQL сервер запущен',
                '2. Проверьте настройки подключения (host, port, username, password)',
                '3. Убедитесь, что база данных "notes_db" существует',
                '4. Выполните init_postgresql.sql для создания структуры БД'
            ]
        ]
    ]);
    exit;
}

// Получение метода запроса
$method = $_SERVER['REQUEST_METHOD'];

// Обработка GET запросов (получение всех заметок)
if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM notes ORDER BY created_at DESC");
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'notes' => $notes
        ]);
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения заметок: ' . $e->getMessage()
        ]);
    }
}

// Обработка POST запросов
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Неверный формат данных']);
        exit;
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            createNote($pdo, $input);
            break;
            
        case 'update':
            updateNote($pdo, $input);
            break;
            
        case 'delete':
            deleteNote($pdo, $input);
            break;
            
        case 'get':
            getNote($pdo, $input['id'] ?? 0);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
    }
}

// Функция создания заметки
function createNote($pdo, $data) {
    $title = trim($data['title'] ?? '');
    $content = trim($data['content'] ?? '');
    
    if (empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Заголовок и содержание обязательны']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO notes (title, content, created_at, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([$title, $content]);
        
        $noteId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Заметка успешно создана',
            'note_id' => $noteId
        ]);
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка создания заметки: ' . $e->getMessage()
        ]);
    }
}

// Функция обновления заметки
function updateNote($pdo, $data) {
    $id = $data['id'] ?? 0;
    $title = trim($data['title'] ?? '');
    $content = trim($data['content'] ?? '');
    
    if (!$id || empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'ID, заголовок и содержание обязательны']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE notes SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $result = $stmt->execute([$title, $content, $id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Заметка успешно обновлена'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Заметка не найдена'
            ]);
        }
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка обновления заметки: ' . $e->getMessage()
        ]);
    }
}

// Функция удаления заметки
function deleteNote($pdo, $data) {
    $id = $data['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID заметки обязателен']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Заметка успешно удалена'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Заметка не найдена'
            ]);
        }
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка удаления заметки: ' . $e->getMessage()
        ]);
    }
}

// Функция получения одной заметки по ID
function getNote($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
        $stmt->execute([$id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($note) {
            echo json_encode([
                'success' => true,
                'note' => $note
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Заметка не найдена'
            ]);
        }
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения заметки: ' . $e->getMessage()
        ]);
    }
}
?>

