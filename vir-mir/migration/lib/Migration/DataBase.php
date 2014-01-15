<?php
/**
 * Created by PhpStorm.
 * User: vir-mir
 * Date: 15.01.14
 * Time: 11:35
 */

namespace Migration;

use PDO;

class DataBase {

    /**
     * @var PDO
     */
    private static $dbReliz = null;

    /**
     * @var PDO
     */
    private static $dbDebug = null;

    public static function connectDB($host, $user, $pas, $db, $dbDebug = true) {

        $dsn = "mysql:dbname={$db};host={$host}";
        try {
            $db = new PDO($dsn, $user, $pas);
            if ($dbDebug) {
                self::$dbDebug = $db;
            } else {
                self::$dbReliz = $db;
            }

        } catch (PDOException $e) {
            echo 'Подключение не удалось: ' . $e->getMessage();
        }

    }

    /**
     * @return PDO
     */
    public static function getDebug() {
        return self::$dbDebug;
    }

    /**
     * @return PDO
     */
    public static function getReliz() {
        return self::$dbReliz;
    }

} 