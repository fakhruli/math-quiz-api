<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors','On');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$config['db']['host']   = "127.0.0.1";
$config['db']['user']   = "root";
$config['db']['pass']   = "fakhrul379";
$config['db']['dbname'] = "db_math";

$app = new \Slim\App(['settings' => $config]);
$container = $app->getContainer();

$container['db'] = function($c){
        $db = $c['settings']['db'];
        $pdo = new PDO("mysql:host=" . $db['host'] . ";port=3306;dbname=" . $db['dbname'],
            $db['user'], $db['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;

};

$app->get('/',function($req, $res, $arg){
    $sql = "select * from guru";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $data['result'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return json_encode($data);
});

// register device
$app->post('/reg/device',function (Request $req, Response $res){
    $request = $req->getParsedBody();
    $result = registerDevice($request['email'],$request['token']);
    switch ($result) {
        case 0:
            $response['error'] = false;
            $response['message'] = 'Device registered successfully';
            break;
        case 2:
            $response['error'] = true;
            $response['message'] = 'Device already registered';
            break;
        default:
            $response['error'] = true;
            $response['message']='Device not registered';
            break;
    }
    return json_encode($response);
});

// send single push
$app->post('/send/single',function(Request $req, Response $res){
    $request = $req->getParsedBody();
    $title = $request['title'];
    $message = $request['message'];
    $mPushNotification = getPush($title,$message);
    $deviceToken = getTokenGuru();
    print_r($deviceToken);
    return send($deviceToken, $mPushNotification);
});

// send all device
$app->post('/send/all',function(Request $req, Response $res){
    $request = $req->getParsedBody();
    $email = $request['email'];
    $title = $request['title'];
    $message = $request['message'];
    $image = $request['image'];
    $mPushNotification = getPush($title,$message,$image);
    $deviceToken = getAllTokens($email);
    print_r($deviceToken);
    return send($deviceToken, $mPushNotification);
});

// get all device
$app->get('/device',function(Request $req, Response $res){
    return json_encode(getAllDevices());
});

// For Real Apps

// simpan nama player
// {nama} = nama player
$app->post('/reg/siswa/',function(Request $req, Response $res){
    $request = $req->getParsedBody();
    $nama = $request['nama'];
    $token = $request['token'];
    if (!cekPlayerData($nama,$token)) {
        $no = getNoPlayer();
        $message = "player baru";
        $result = $this->db->exec('insert into player values(null,"'.$no.'","'.$nama.'","'.$token.'",0)');
    } else {
        $sql = "select no_player from player where nama = '".$nama."' and token='".$token."'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $no = $result[0];
        $message = "player lama";
    }

    if ($result) {
        $data = array(
            'failure' => false,
            'message' => 'berhasil get data',
            'no' => $no,
            'role' => 'siswa'
        );
        $title = "guru";
        $mPushNotification = getPush($title,$message);
        $deviceToken = getTokenGuru();
        send($deviceToken, $mPushNotification);
    } else {
        $data = array(
            'failure' => true,
            'message' => 'gagal get data'
        );
    }
    return json_encode($data);
});

// simpan nama guru
// {nama} = nama guru
$app->post('/reg/guru/',function(Request $req, Response $res){
    $request = $req->getParsedBody();
    $nama = $request['nama'];
    $token = $request['token'];
    if (!cekGuruData($nama,$token)) {
        $no = getNoPlayer();
        $message = "player baru";
        $result =$this->db->exec('insert into guru values(NULL,"'.$nama.'","'.$token.'")');
    } else {
        $sql = "select id_guru from guru where nama_guru = '".$nama."' and token='".$token."'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $no = $result[0];
        $message = "Guru lama";
    }

    if ($result) {
        $data = array(
            'failure' => false,
            'message' => 'berhasil get data',
            'no' => $no,
            'role' => 'guru'
        );
    } else {
        $data = array(
            'failure' => true,
            'message' => 'gagal get data'
        );
    }
    return json_encode($data);
});

// login guru
// {nama} nama guru
$app->get('/guru/{nama}/{token}',function(Request $req, Response $res){
    $nama = $req->getAttribute('nama');
    $token = $req->getAttribute('token');
    $stmt = $this->db->prepare('select id_guru from guru where nama_guru = "'.$nama.'"');
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($result) {
        $data = array(
            'failure' => false,
            'message' => 'berhasil get data',
            'role' => "guru"
        );
    } else {
        $data = array(
            'failure' => true,
            'message' => 'gagal get data'
        );
    }
    return json_encode($data);
});

// get all siswa
$app->get('/siswa/all',function(Request $req, Response $res){
    $db = $this->db;
    $sql = "select * from player";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tokens = array();
    $namas = array();
    $nosiswas = array();
    for ($i=0; $i < count($result); $i++) {
        array_push($tokens,$result[$i]['token']);
        array_push($namas,$result[$i]['nama']);
        array_push($nosiswas,$result[$i]['no_player']);
    }
    $data = array(
        'nama' => $namas,
        'no_siswa' => $nosiswas,
        'tokens' => $tokens
    );
    return json_encode($data);
});

// trigger start game
$app->get('/game/{param}',function(Request $req){
    $param = $req->getAttribute('param');
    if ($param == 'mulai') {
        $data = array(
            'failure' => false,
            'message' => 'mulai permainan',
            'role' => 'guru'
        );
        $title = "siswa";
        $message = "mulai";
        $mPushNotification = getPush($title,$message);
        $deviceToken = getAllPlayerToken();

    } elseif ($param == 'next') {
        $data = array(
            'failure' => false,
            'message' => 'next soal',
            'role' => 'guru'
        );
        $title = "siswa";
        $message = "next";
        $mPushNotification = getPush($title,$message);
        $deviceToken = getAllPlayerToken();
        send($deviceToken, $mPushNotification);
    } elseif ($param == 'reset') {
        $stmt = $this->db->exec('delete from player');
        $data = array(
            'failure' => false,
            'message' => 'reset permainan',
            'role' => 'guru'
        );
    } else {
        $data = array(
            'failure' => false,
            'message' => 'permainan dihentikan',
            'role' => 'guru'
        );
        $title = "siswa";
        $message = "stop";
        $mPushNotification = getPush($title,$message);
        $deviceToken = getAllPlayerToken();
        send($deviceToken, $mPushNotification);
    }
    return send($deviceToken, $mPushNotification);
});

// waktu giliran
// {pos} = posisi sekarang
$app->get('/numpos/{pos}', function(Request $req, Response $res){
    $numpos = $req->getAttribute('pos') + 1;
    return $res->getBody()->write($numpos);
});

// get random soal
$app->get('/nosoal',function(){
    $numbers = range(1,10);
    shuffle($numbers);
    $data = array_slice($numbers,0,10);
    return json_encode($data);
});

// ambil soal dari db
// {no} = no player
$app->get('/quiz/{no}', function(Request $req, Response $res){
    $no = $req->getAttribute('no');
    $stmt = $this->db->prepare('select * from soal ');
    $stmt->execute();
    $data['soal'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return json_encode($data);
});

// cek jawaban
$app->get('/jawab/{jawab}/{noSoal}/{noPlayer}/{score}',function(Request $req, Response $res){
    $jawab = $req->getAttribute('jawab');
    $noSoal = $req->getAttribute('noSoal');
    $noPlayer = $req->getAttribute('noPlayer');
    $score = $req->getAttribute('score');
    $stmt = $this->db->prepare('select benar from soal where id_soal = "'.$noSoal.'"');
    $stmt->execute();
    $result= $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($jawab == $result[0]['benar']) {
        $title = "siswa";
        $message = "benar";
        $mPushNotification = getPush($title,$message);
        $deviceToken = getAllPlayerToken();
        send($deviceToken, $mPushNotification);

        $title = "guru";
        $message = "benar";
        $mPushNotification = getPush($title,$message);
        $deviceToken = getTokenGuru();
        send($deviceToken, $mPushNotification);

        $score += 10;
    } else {
        $title = "siswa";
        $message = "salah";
        $mPushNotification = getPush($title,$message);
        $deviceToken = getAllPlayerToken();
        send($deviceToken, $mPushNotification);

        $title = "guru";
        $message = "salah";
        $mPushNotification = getPush($title,$message);
        $deviceToken = getTokenGuru();
        send($deviceToken, $mPushNotification);
    }

    $update = $this->db->exec("update player set score = '".$score."' where no_player = '".$noPlayer."'");

    $data = array(
        'failure' => false,
        'message' => 'berhasil sent data',
        'role' => "siswa"
    );

    return json_encode($data);
});

$app->get('/jawaban/benar/{noSoal}',function(Request $req, Response $res){
    $noSoal = $req->getAttribute('noSoal');
    $stmt = $this->db->prepare('select benar from soal where id_soal = "'.$noSoal.'"');
    $stmt->execute();
    $result= $stmt->fetchAll(PDO::FETCH_ASSOC);
    $benar = $result[0]['benar'];

    $stmt = $this->db->prepare('select jawab'.$benar.' from soal where id_soal = "'.$noSoal.'"');
    $stmt->execute();
    $jawab = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return json_encode($jawab[0]);
});

$app->get('/user/total',function(){
    $stmt = $this->db->prepare('select count(no_player) from player');
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return json_encode($data[0]);
});

$app->get('/score/{no}',function(Request $req, Response $res){
    $no = $req->getAttribute('no');

    if ($no != "0") {
        $stmt = $this->db->prepare('select score from player where no_player = "'.$no.'"');
    } else {
        $stmt = $this->db->prepare('select score from player');
    }

    $stmt->execute();
    $score = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    for ($i=0; $i < sizeOf($score); $i++) { 
        array_push($res, $score[$i]['score']);
    }
    $data['score'] = $res;
    return json_encode($data);
});

$app->get('/test',function(){
    $title = "guru";
    $message = "score baru";
    $mPushNotification = getPush($title,$message);
    $deviceToken = getTokenGuru();
    return send($deviceToken, $mPushNotification);
});

$app->run();

// ambil nomor player
function getNoPlayer() {
    $db = getConnection();
    $stmt = $db->prepare('select max(no_player) as max from player');
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $res = $data[0]['max'];
    return json_encode($res + 1);
}

function cekPlayerData($nama,$token) {
    $db = getConnection();
    $sql = "select id_player from player where nama = '".$nama."' and token = '".$token."'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $result;
}

function cekGuruData($nama,$token) {
    $db = getConnection();
    $sql = "select id_guru from guru where nama_guru='".$nama."' and token = '".$token."'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $result;
}

// store token
function registerDevice($email,$token) {
    $db = getConnection();
    if (isEmailExist($email)) {
        if ($db->exec("insert into device values(null,'".$email."','".$token."')")) {
            return 0;
        } else {
            return 1;
        }
    } else {
        return 2;
    }
}

// check email already exists
function isEmailExist($email) {
    $db = getConnection();
    $sql = "select id from device where email = '".$email."'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result > 0;
}

// getting specified token guru
function getTokenGuru(){
    $db = getConnection();
    $res = array();
    $sql = "select token from guru";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    for ($i=0; $i < sizeOf($result); $i++) {
        array_push($res, $result[$i]['token']);
    }
    return $res;
}

function getAllPlayerToken() {
    $db = getConnection();
    $res = array();
    $sql = "select token from player";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    for ($i=0; $i < sizeof($result); $i++) {
        array_push($res,$result[$i]['token']);
    }
    return $res;
}

// get all devices
function getAllDevices(){
    $db = getConnection();
    $sql = "select * from device";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result['device'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

// push Message
function getPush($title,$message){
    $res = array();
    $res['data']['title'] = $title;
    $res['data']['message'] = $message;
    return $res;
}

function send($registration_id,$message){
    $fields = array(
        'registration_ids' => $registration_id,
        'data' => $message
    );
    return sendPushNotification($fields);
}

function sendPushNotification($fields){
    $key = 'AAAAqCfQT7o:APA91bFbJ8QiEBhQp9ZKn3Xh-7CUOIkO4VouJYK3pyS3-sxGolsCSNzygptjPK0cLNyQYWJaOXC9Yf5Ig2_xSsaE4grwqzlNkw9ewzilvbTgpI_eddoM01R4ZkTO-y_FEIG3Bp9f57Mp';
    $url = 'https://fcm.googleapis.com/fcm/send';
    $headers = array(
        'Authorization: key='.$key,
        'Content-type: application/json'
    );

    // initializing curl to open a connectiona
    $ch = curl_init();

    // setting curl url
    curl_setopt($ch,CURLOPT_URL,$url);

    // setting post method
    curl_setopt($ch,CURLOPT_POST,true);

    // adding header
    curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

    // disabling ssl support
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);

    // adding fields in json format
    curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($fields));

    // execute curl request
    $result = curl_exec($ch);
    if ($result == FALSE) {
        die('curl failed: '.curl_error($ch));
    }

    curl_close($ch);
    return $result;
}

// connection
function getConnection() {
    $dbhost = "127.0.0.1";
	$dbuser = "root";
	$dbpass = "fakhrul379";
	$dbname = "db_math";
    $pdo = new PDO("mysql:host=" . $dbhost . ";port=3306;dbname=" . $dbname,
        $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}
