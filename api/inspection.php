<?php
    include_once "inspectionFunctions/getInspectionChain.php";
    include_once "inspectionFunctions/editInspection.php";
    include_once "inspectionFunctions/getFullInfo.php";
    

    function route($method, $urlList, $requestData){
        ;
        switch ($method) {
            case 'GET':
                switch ($urlList[2]) {
                    case 'getInspectionChain':  
                        $inspectionId = $_GET['inspectionId']; 
                        getInspectionChain($inspectionId);
                        break;
                    case '':  
                        getFullInfo();
                        break;
                    default:
                    //или 400 ошибка(неверный запрос к урлу)
                        setHTTPSStatus("404", "There is no such path as 'account/$urlList[1]'");
                        break;             
                }                  
                break;

            case 'PUT':
                if( ($urlList[2])=='editInspection'){
                    editInspection($requestData);
                    break;
                }
            
            default:
                //или 400 ошибка(неверный запрос к урлу/синтаксическая ошибка)
                setHTTPSStatus("404", "There is no such method here");
                break;
        }
    }