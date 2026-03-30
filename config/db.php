<?php
declare(strict_types=1);

function db_connect()
{
    static $conn = null;

    if ($conn !== null) {
        return $conn;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: 'personel_talep';
    $user = getenv('DB_USER') ?: 'postgres';
    $password = getenv('DB_PASSWORD') ?: '1123';

    $connString = sprintf(
        'host=%s port=%s dbname=%s user=%s password=%s',
        $host,
        $port,
        $dbname,
        $user,
        $password
    );

    $conn = @pg_connect($connString);
    if (!$conn) {
        die('Veritabani baglantisi kurulamadi.');
    }

    pg_set_client_encoding($conn, 'UTF8');
    return $conn;
}
