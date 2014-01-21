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
    public  static $dbRelizName = null;

    /**
     * @var PDO
     */
    private static $dbDebug = null;
    public static $dbDebugName = null;

    public static function connectDB($host, $user, $pas, $dbName, $dbDebug = true) {

        $dsn = "mysql:dbname={$dbName};host={$host}";
        try {
            $db = new PDO($dsn, $user, $pas);
            if ($dbDebug) {
                self::$dbDebug = $db;
                self::$dbDebugName = $dbName;
            } else {
                self::$dbReliz = $db;
                self::$dbRelizName = $dbName;
            }

        } catch (\PDOException $e) {
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