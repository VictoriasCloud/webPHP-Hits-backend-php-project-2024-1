<?php
include_once "dictionaryFunctions/searchICD10Diagnoses.php";
include_once "dictionaryFunctions/getRootICD10Elements.php";
include_once "dictionaryFunctions/getSpecialitiesList.php";

function route($method, $urlList, $requestData) {
    if ($method === 'GET') {
        if (isset($urlList[2]) && $urlList[1] === 'dictionary') {
            switch ($urlList[2]) {
                case 'speciality':  ///api/dictionary/speciality
                    if (count($urlList) === 3) {
                        getSpecialitiesList();
                    } else {
                        setHTTPSStatus("400", "Invalid Path");
                        break;
                    }
                    break;

                case 'icd10':
                    ///api/dictionary/icd10/roots
                    if (isset($urlList[3]) && $urlList[3] === 'roots') {
                        if (count($urlList) === 4) {
                            getRootICD10Elements();
                        } else {
                            setHTTPSStatus("400", "Invalid Path");
                            break;
                        }
                    } elseif (count($urlList) === 3) {  
                        ///api/dictionary/icd10
                        searchICD10Diagnoses();
                    } else {
                        setHTTPSStatus("400", "Invalid Path");
                        break;
                    }
                    break;

                default:
                    setHTTPSStatus("404", "There is no such path in 'dictionary' with '$urlList[2]'");
                    break;
            }
        } else {
            setHTTPSStatus("404", "Invalid Path.");
            
        }
    } else {
        setHTTPSStatus("405", "Invalid Path");
        
    }
}
