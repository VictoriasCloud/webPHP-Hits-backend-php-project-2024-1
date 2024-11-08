<?php
include_once "consultationFunctions/getConsultationById.php";
include_once "consultationFunctions/getAlistOfMedicalInspectionsForConsultation.php";
include_once "consultationFunctions/addComment.php";
include_once "consultationFunctions/editComment.php";

function route($method, $urlList, $requestData) {
    global $Link;

    // Проверка токена на уровне роутинга
    $checkTokenResult = checkToken($Link);
    if (!$checkTokenResult) {
        return;
    }

    switch ($method) {
        case 'GET':
            if (count($urlList) === 2) {
                // GET /api/consultation - список осмотров для консультаций
                getAlistOfMedicalInspectionsForConsultation();
            } elseif (count($urlList) === 3 && is_numeric($urlList[2])) {
                // GET /api/consultation/{id} - получение конкретной консультации
                getConsultationById($urlList[2]);
            } else {
                setHTTPSStatus("404", "There is no such path as 'consultation/$urlList[1]'");
            }
            break;

            case 'POST':
                if (count($urlList) === 4 && $urlList[1] === 'consultation' && is_numeric($urlList[2]) && $urlList[3] === 'comment') {
                    // POST /api/consultation/{id}/comment
                    addCommentToConsultation($urlList[2], $requestData);  // передаем ID консультации и данные запроса
                } else {
                    setHTTPSStatus("404", "There is no such path as 'consultation/$urlList[1]'");
                }
                break;
            
            

        case 'PUT':
            if (count($urlList) === 4 && $urlList[1] === 'comment' && is_numeric($urlList[3])) {
                // PUT /api/consultation/comment/{id} - редактирование комментария
                editComment($urlList[3], $requestData);
            } else {
                setHTTPSStatus("404", "There is no such path as 'consultation/$urlList[1]'");
            }
            break;

        default:
            setHTTPSStatus("405", "Method Not Allowed");
            break;
    }
}
