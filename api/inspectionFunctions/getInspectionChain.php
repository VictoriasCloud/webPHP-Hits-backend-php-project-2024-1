<?php
function getInspectionChain($inspectionId){

    global $Link;


    // Проверка авторизации пользователя
    $checkTokenResult = checkToken($Link);
    if (!$checkTokenResult) {
        setHTTPSStatus("401", "Unauthorized");
        return;
    }

    // Проверка наличия осмотра с указанным идентификатором
    $checkInspectionQuery = "SELECT * FROM inspection WHERE id='$inspectionId'";
    $checkInspectionResult = $Link->query($checkInspectionQuery);

    if ($checkInspectionResult->num_rows == 0) {
        // Если осмотр не найден, возвращаем статус 404 (Not Found)
        setHTTPSStatus("404", "Not Found");
        return;
    }

    // Получаем медицинскую цепочку для корневого осмотра
    $inspectionChain = [];
    $currentInspectionId = $inspectionId;

    // Используем while для обхода осмотров в цепочке
    while (true) {
        // Запрос для получения информации о текущем осмотре с диагнозом типа "Main"
        $getCurrentInspectionQuery = "SELECT i.id, i.createTime, i.date, i.conclusion, i.IdDoctor, d.name AS doctor, i.idPatient, p.name AS patient, 
                                        d.id AS diagnosis_id, d.createTime AS diagnosis_createTime, d.code, d.name AS diagnosis_name, d.description, d.type
                                        FROM inspection i
                                        INNER JOIN diagnosis d ON i.id = d.idInspection
                                        INNER JOIN patient p ON i.idPatient = p.id
                                        WHERE i.id = '$currentInspectionId' AND d.type = 'Main'";
        $currentInspectionResult = $Link->query($getCurrentInspectionQuery);

        if ($currentInspectionResult->num_rows > 0) {
            $currentInspectionData = $currentInspectionResult->fetch_assoc();

            // Добавляем информацию о диагнозе к текущему осмотру
            $currentInspectionData['diagnosis'] = [
                'id' => $currentInspectionData['diagnosis_id'],
                'createTime' => $currentInspectionData['diagnosis_createTime'],
                'code' => $currentInspectionData['code'],
                'name' => $currentInspectionData['diagnosis_name'],
                'description' => $currentInspectionData['description'],
                'type' => $currentInspectionData['type']
            ];

            // Удаляем лишние поля
            unset($currentInspectionData['diagnosis_id']);
            unset($currentInspectionData['diagnosis_createTime']);
            unset($currentInspectionData['code']);
            unset($currentInspectionData['diagnosis_name']);
            unset($currentInspectionData['description']);
            unset($currentInspectionData['type']);

            // Добавляем текущий осмотр в цепочку
            $inspectionChain[] = $currentInspectionData;

            // Проверяем, есть ли следующий осмотр в цепочке
            $getNextInspectionQuery = "SELECT id FROM inspection WHERE previousInspectionId = '$currentInspectionId'";
            $nextInspectionResult = $Link->query($getNextInspectionQuery);

            if ($nextInspectionResult->num_rows == 1) {
                $nextInspectionId = $nextInspectionResult->fetch_assoc()['id'];
                $currentInspectionId = $nextInspectionId; // Переходим к следующему осмотру в цепочке
            } else {
                break; // Завершаем цикл, если следующий осмотр не найден
            }
        } else {
            // Если информация о текущем осмотре с диагнозом "Main" не найдена, возвращаем ошибку 500 (InternalServerError)
            setHTTPSStatus("500", "InternalServerError");
            return;
        }
    }

    // Возвращаем медицинскую цепочку в виде JSON
    echo json_encode($inspectionChain);
    setHTTPSStatus("200", "Success");
}
