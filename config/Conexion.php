<?php

class Conexion {

    var $connection = NULL;

    /**
     * Funciones de conexion a base de datos...
     */
    private function getConnection() {
        if ($this->connection == NULL) {
            require_once __DIR__ . "/local.php";
            $this->connection = $connection = new PDO(
                    "mysql:host=$conf_db_host;dbname=$conf_db_database;", $conf_db_user, $conf_db_passwd
            );
        }
        return $this->connection;
    }

    public function queryList($sql) {
        $connection = $this->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function execute($sql) {
        $connection = $this->getConnection();
        $stmt = $connection->prepare($sql);
        return $stmt->execute();
    }

}
