<?php
namespace Admin\Model;

use PDO;
use PDOException;

class Model
{
    protected $pdo = null;
    
    public function __construct() {
        try {
            $this->pdo = new PDO('sqlsrv:server='. \DB_HOST .';database='. \DB_NAME, \DB_USER, \DB_PASS, \DB_OPTIONS);
        } catch (PDOException $e) {
            error_log('Veritabanı hatası: ' . $e->getMessage() . PHP_EOL, 3,\APP_ROOT . '/error_log.php');
            die($e->getMessage());
        }
    }

    public function createTable($tableName, $columns)
    {
        $sql = "
        DECLARE @result VARCHAR(100);
        IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '".$tableName."')
        BEGIN
            SET @result = 'TABLO VAR.';
        END
        ELSE
        BEGIN
            CREATE TABLE $tableName (";
        $sql .= implode(', ', array_map(function($column) {
            return $column['name'] . ' ' . $column['type'];
        }, $columns));
        $sql .= ");
            SET @result = 'TABLO EKLENDI.';
        END;
        SELECT @result AS Statement;";

        try {
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result;
        } catch (PDOException $e) {
            echo 'Table Creation Error: ' . $e->getMessage();
        }
    }
}