<?php
    include_once "dictionaryFunctions/searchICD10Diagnoses.php";
    function route($method, $urlList, $requestData){
        global $Link;

        if ($method === 'GET') {
            switch ($urlList[2]) {
                case 'searchICD10Diagnoses':  
                    echo "2ураа";
                    searchICD10Diagnoses();
                    break;
                case 'getRootICD10Elements':  
                    //getRootICD10Elements();
                    break;
                default:
                //или 400 ошибка(неверный запрос к урлу)
                    setHTTPSStatus("404", "There is no such path as 'account/$urlList[1]'");
                    break;             
            }                  
        }
        else {
            setHTTPSStatus("404", "There is no such method here");
        }
    }