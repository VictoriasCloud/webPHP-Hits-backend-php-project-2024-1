<?php
    include_once "createSpeciality/speciality.php";
    function route($method, $urlList, $requestData){
        switch ($method) {
            case 'POST':
                switch ($urlList[2]) {
                    case 'createSpeciality':  
                        echo "ураа3";
                        createSpeciality($requestData);
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