<?php

// Базовые данные для подключения
$host = 'localhost';  // Используем 'localhost' вместо 127.0.0.1
$port = 3306;         // Стандартный порт MySQL
$dbname = 'cw14413_rem';
$username = 'cw14413_rem';
$password = '2347890Aa';

echo "Проверяем подключение к базе данных...<br>";

try {
    // Пытаемся подключиться через TCP/IP
    $pdo1 = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname}", 
        $username, 
        $password, 
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    echo "Подключение через TCP/IP успешно.<br>";
    $pdo1 = null;
} catch (PDOException $e) {
    echo "Ошибка подключения через TCP/IP: " . $e->getMessage() . "<br>";
}

try {
    // Пытаемся подключиться через сокет (стандартный путь для Linux)
    $socket = '/tmp/mysql.sock'; // Стандартный путь для многих Linux-систем
    $pdo2 = new PDO(
        "mysql:unix_socket={$socket};dbname={$dbname}", 
        $username, 
        $password, 
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    echo "Подключение через сокет ({$socket}) успешно.<br>";
    $pdo2 = null;
} catch (PDOException $e) {
    echo "Ошибка подключения через сокет ({$socket}): " . $e->getMessage() . "<br>";
}

// Для некоторых хостингов используется другой путь к сокету
try {
    $socket2 = '/var/lib/mysql/mysql.sock'; // Альтернативный путь
    $pdo3 = new PDO(
        "mysql:unix_socket={$socket2};dbname={$dbname}", 
        $username, 
        $password, 
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    echo "Подключение через альтернативный сокет ({$socket2}) успешно.<br>";
    $pdo3 = null;
} catch (PDOException $e) {
    echo "Ошибка подключения через альтернативный сокет ({$socket2}): " . $e->getMessage() . "<br>";
}

// Получение информации о PHP и MySQL
echo "<hr>";
echo "PHP версия: " . phpversion() . "<br>";
echo "Расширения PDO:<br>";
print_r(PDO::getAvailableDrivers());
echo "<br>";

// Проверка на наличие расширения mysqli
if (extension_loaded('mysqli')) {
    echo "Расширение mysqli загружено.<br>";
} else {
    echo "Расширение mysqli не загружено.<br>";
}

// Вывод всех конфигурационных параметров PHP
echo "<hr>";
echo "Параметры PHP, связанные с MySQL:<br>";
$mysqlSettings = [];
foreach (ini_get_all() as $key => $value) {
    if (strpos($key, 'mysql') !== false || strpos($key, 'pdo') !== false) {
        $mysqlSettings[$key] = $value['local_value'];
    }
}
print_r($mysqlSettings);
