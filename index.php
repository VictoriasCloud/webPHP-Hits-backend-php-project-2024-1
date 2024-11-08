<?php
include_once 'helpers/headers.php';
include_once 'helpers/checks.php';
include_once 'helpers/validations.php';
include_once 'helpers/checksForPatient.php';
include_once 'helpers/helperForInspection.php';

global $Link, $UploadDir;

function getData($method) {
    $data = new stdClass();
    if ($method != "GET") {
        $data->body = json_decode(file_get_contents('php://input')); 
    }
    $data->parameters = $_GET; // Получаем все параметры из GET
    return $data;
}

function getMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

header('Content-type: application/json');
$Link = mysqli_connect("127.0.0.1", "backend_demo_1", "password", "backend");
$UploadDir = "uploads";

if (!$Link) {
    setHTTPSStatus("500", "DB Connection error: " . mysqli_connect_error());
    exit;
}

$url = isset($_GET['q']) ? $_GET['q'] : '';
$url = rtrim($url, '/');
$urlList = explode('/', $url);

$router2 = $urlList[0] ?? '';
$router = $urlList[1] ?? '';
$requestData = getDataа(getMethod());
//var_dump($requestData); 
$method = getMethod();

if (file_exists(realpath(dirname(__FILE__)) . '/' . $router2 . '/' . $router . '.php')) {
    include_once 'api/' . $router . '.php';
    route($method, $urlList, $requestData);
} else {
    setHTTPSStatus("404", "There is no such path (index.php)/Not Found");
}

mysqli_close($Link);

function getDataа($method) {
    $data = new stdClass();
    
    if ($method != "GET") {
        $data->body = json_decode(file_get_contents('php://input')); 
    }

    // Инициализация параметров
    $data->parameters = [];
    
    // Ручной разбор строки запроса для сбора всех значений icdRoots
    if (isset($_SERVER['QUERY_STRING'])) {
        $queryArray = explode('&', $_SERVER['QUERY_STRING']);
        $icdRoots = [];

        foreach ($queryArray as $param) {
            list($key, $value) = explode('=', $param);
            $value = urldecode($value); // Декодируем значение

            if ($key === 'icdRoots') {
                // Добавляем все значения icdRoots в массив
                $icdRoots[] = $value;
            } else {
                $data->parameters[$key] = $value;
            }
        }

        // Устанавливаем массив icdRoots
        if (!empty($icdRoots)) {
            $data->parameters['icdRoots'] = $icdRoots;
        }
    }
    
    return $data;
}

