<?php
include_once "createSpeciality/speciality.php";

function route($method, $urlList, $requestData) {
    switch ($method) {
        case 'POST':
            if (isset($urlList[2]) && $urlList[2] === 'speciality') {
                //api/speciality
                if (count($urlList) === 3) {
                    createSpeciality($requestData);
                } else {
                    setHTTPSStatus("400", "Invalid path");
                }
            } else {
                setHTTPSStatus("404", "Invalid path: no such endpoint '$urlList[2]' in 'api'.");
            }
            break;

        default:
            setHTTPSStatus("405", "Method not allowed for this route");
            break;
    }
}
