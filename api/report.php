<?php
include_once "reportFunctions/icdRootsReport.php";

function route($method, $urlList, $requestData) {
    if ($method === 'GET') {
        switch ($urlList[2]) {
            case 'icdrootsreport':
                getICD10RootsReport($requestData);
                break;
            default:
                setHTTPSStatus("404", "Invalid path");
                break;
        }
    } else {
        setHTTPSStatus("405", "Method not allowed");
    }
}
