<?php
    include_once "doctorFunctions/registration.php";
    include_once "doctorFunctions/getProfile.php";
    include_once "doctorFunctions/editProfile.php";
    include_once "doctorFunctions/login.php";
    include_once "doctorFunctions/logout.php";
    function route($method, $urlList, $requestData){
        global $Link;
        echo "ураааа";
        switch ($method) {
            case 'GET':
                //getProfile($requestData);
                //break;

            case 'POST':
                switch ($urlList[2]) {
                    case 'register':  
                        echo "ураааа";
                        registerDoctor($requestData);
                        break;

                    /*case 'login':  
                        login($requestData);
                        break;

                    case 'logout':  
                        logout($requestData);
                        break;

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