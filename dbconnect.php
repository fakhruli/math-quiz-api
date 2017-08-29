<?php
class dbconnect{
    private $con;

    function __construct(){

    }

    function konek(){
        include_once dirname(__FILE__).'/config.php';
        $pdo = new PDO("mysql:host='".DB_HOST."';dbname='".DB_NAME."'",DB_USERNAME,DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }
}
