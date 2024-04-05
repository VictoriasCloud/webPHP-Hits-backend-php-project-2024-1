<?php
    include_once "doctorFunctions/registration.php";
    include_once "doctorFunctions/getProfile.php";
    include_once "doctorFunctions/editProfile.php";
    include_once "doctorFunctions/login.php";
    include_once "doctorFunctions/logout.php";
    function route($method, $urlList, $requestData){
        global $Link;
        echo "1ураа";
        switch ($method) {
            case 'GET':
                echo "5ураа";
                getProfile(explode(' ', getallheaders()['Authorization'])[1]);
                break;

            case 'POST':
                switch ($urlList[2]) {
                    case 'register':  
                        echo "2ураа";
                        registerDoctor($requestData);
                        break;
                    case 'login':  
                        echo "3ураа";
                        login($requestData);
                        break;
                    case 'logout':  
                        echo "4ураа";
                        logout(explode(' ', getallheaders()['Authorization'])[1]);
                        break;
/*
                    default:
                        setHTTPSStatus("404", "There is no such path as 'account/$urlList[1]'");
                        break;  */                  
                }                  
                break;

            case 'PUT':
                //editProfile($requestData);
                break;
            
            default:
                setHTTPSStatus("404", "There is no such method here");
                break;
        }
    }