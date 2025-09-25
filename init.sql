-- Создал новый SQL файл для PostgreSQL
-- Создание базы данных (выполните от имени суперпользователя)
CREATE DATABASE notes_db;

-- Подключитесь к базе данных notes_db и выполните следующие команды:

-- Создание таблицы заметок
CREATE TABLE IF NOT EXISTS notes (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Создание индексов для оптимизации
CREATE INDEX IF NOT EXISTS idx_notes_created_at ON notes(created_at);
CREATE INDEX IF NOT EXISTS idx_notes_title ON notes(title);

-- Вставка примеров заметок
INSERT INTO notes (title, content) VALUES 
('Добро пожаловать!', 'Это ваша первая заметка. Вы можете редактировать или удалить её.'),
('Список покупок', 'Молоко, хлеб, яйца, масло'),
('Идеи для проекта', 'Добавить поиск по заметкам, категории, теги');

-- Создание функции для автоматического обновления updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Создание триггера для автоматического обновления updated_at
CREATE TRIGGER update_notes_updated_at 
    BEFORE UPDATE ON notes 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

