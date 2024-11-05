<?php
    include_once "doctorFunctions/registration.php";
    include_once "doctorFunctions/getProfile.php";
    include_once "doctorFunctions/editProfile.php";
    include_once "doctorFunctions/login.php";
    include_once "doctorFunctions/logout.php";
    function route($method, $urlList, $requestData){
        global $Link;
        switch ($method) {
            case 'GET':
                if ($urlList[2] === 'profile') {
                    getProfile(explode(' ', getallheaders()['Authorization'])[1]);
                    } 
                else {
                    setHTTPSStatus(404, "GET route not found for 'doctor'");
                }
                break;

            case 'POST':
                switch ($urlList[2]) {
                    case 'register':  
                        registerDoctor($requestData);
                        break;
                    case 'login':  
                        login($requestData);
                        break;
                    case 'logout':  
                        logout(explode(' ', getallheaders()['Authorization'])[1]);
                        break;
                    default:
                    //или 400 ошибка(неверный запрос к урлу)
                        setHTTPSStatus("404", "There is no such path as 'account/$urlList[1]'");
                        break;             
                }                  
                break;

            case 'PUT':
                if ($urlList[2] === 'profile') {
                    editProfile($requestData);
                } else {
                    setHTTPSStatus(404, "PUT route not found for 'doctor'");
                }
                break;
            
            default:
                //или 400 ошибка(неверный запрос к урлу/синтаксическая ошибка)
                setHTTPSStatus("404", "There is no such method here");
                break;
        }
    }