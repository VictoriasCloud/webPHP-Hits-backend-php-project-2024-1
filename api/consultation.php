<?php
    include_once "consultationFunctions/getConsultationById.php";
    include_once "consultationFunctions/getAlistOfMedicalInspectionsForConsultation.php";
    include_once "consultationFunctions/addComment.php";
    include_once "consultationFunctions/editComment.php";

    function route($method, $urlList, $requestData){

        switch ($method) {
            case 'GET':
                switch ($urlList[2]) {
                    case 'getConsultationById':
                        getConsultationById();
                        break;
                    case 'getAlistOfMedicalInspectionsForConsultation':
                        getAlistOfMedicalInspectionsForConsultation();
                        break;
                    default:
                    //или 400 ошибка(неверный запрос к урлу)
                        setHTTPSStatus("404", "There is no such path as 'consultation/$urlList[1]'");
                        break;    
                }
                break;

            case 'POST':
                switch ($urlList[2]) {
                    case 'addComment':  
                        addComment($requestData);
                        break;
                    default:
                    //или 400 ошибка(неверный запрос к урлу)
                        setHTTPSStatus("404", "There is no such path as 'consultation/$urlList[1]'");
                        break;             
                }                  
                break;
            case 'PUT':
                switch ($urlList[2]) {
                    case 'editComment':  
                        editComment($requestData);
                        break;
                    default:
                    //или 400 ошибка(неверный запрос к урлу)
                        setHTTPSStatus("404", "There is no such path as 'consultation/$urlList[1]'");
                        break;             
                }                  
                break;
                        
            
            default:
                //или 400 ошибка(неверный запрос к урлу/синтаксическая ошибка)
                setHTTPSStatus("404", "There is no such method here or Method Not Allowed(405)");
                break;
        }
    }