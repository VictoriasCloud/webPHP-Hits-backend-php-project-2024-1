<?php
    include_once "dictionaryFunctions/searchICD10Diagnoses.php";
    include_once "dictionaryFunctions/getRootICD10Elements.php";
    include_once "dictionaryFunctions/getSpecialitiesList.php";
    function route($method, $urlList, $requestData){

        if ($method === 'GET') {
            switch ($urlList[2]) {
                case 'searchICD10Diagnoses':  
                    searchICD10Diagnoses();
                    break;
                case 'getRootICD10Elements':  
                    getRootICD10Elements();
                    break;
                case 'getSpecialitiesList':  
                    getSpecialitiesList();
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