<?php

class PDO_DB {

    private static $dbh;

    public static function factory() {

        if (!isset(self::$dbh)) {

            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '3306';
            $name = getenv('DB_NAME') ?: 'hackerexperience';
            $user = getenv('DB_USER') ?: 'heuser';
            $pass = getenv('DB_PASS') ?: '';
            $socket = getenv('DB_SOCKET');

            if ($socket) {
                $dsn = 'mysql:unix_socket=' . $socket . ';dbname=' . $name;
            } else {
                $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name;
            }

            $dbOptions = array(
                PDO::ATTR_CASE => PDO::CASE_LOWER,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION //TODO: remove this line on production (maybe not, just hide php errors, so I can see logs)
            );

            self::$dbh = new PDO($dsn, $user, $pass, $dbOptions);
        }

        return self::$dbh;
    }

}

?>
