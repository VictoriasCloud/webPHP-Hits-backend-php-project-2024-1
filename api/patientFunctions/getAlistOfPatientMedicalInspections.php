<?php

function getAlistOfPatientMedicalInspections(){
    global $Link;
    
    $checkTokenResult = checkToken($Link);
    $patientId = $_GET['patientId'];
    $grouped = $_GET['grouped'];
    $icdRoots = $_GET['icdRoots'];
    $page = $_GET['page'];
    $size = $_GET['size'];
    validateArguments($grouped, $icdRoots, $page, $size);
    
    // Проверяем, существует ли пациент с указанным идентификатором
    $checkPatientQuery = "SELECT * FROM patient WHERE id='$patientId'";
    $checkPatientResult = $Link->query($checkPatientQuery);

    if ($checkPatientResult->num_rows == 1 && $checkTokenResult) {
        // Если пациент найден, получаем данные из inspection

        $startRow = ($page - 1) * $size;
        $endRow = $startRow + $size;
        
        // Формируем запрос для получения осмотров пациента
        $listOfInspectionsQuery = "SELECT * FROM inspection WHERE idPatient='$patientId'";
        
        // Если grouped=true, сортируем осмотры по столбцу date
        if ($grouped === 'true') {
            $listOfInspectionsQuery .= " ORDER BY date DESC";
        }
        
        // Добавляем лимит и оффсет для пагинации
        $listOfInspectionsQuery .= " LIMIT $startRow, $size";
        
        $listOfInspectionsResult = $Link->query($listOfInspectionsQuery);
        
        if ($listOfInspectionsResult->num_rows > 0) {
            $ArrayOfInspections = [];
            
            while ($row = $listOfInspectionsResult->fetch_assoc()) {
                // Получаем id текущего осмотра
                $inspectionId = $row['id'];
                
                // Формируем запрос для получения диагнозов, относящихся к текущему осмотру
                $diagnosisQuery = "SELECT * FROM diagnosis WHERE idInspection='$inspectionId'";
                $diagnosisResult = $Link->query($diagnosisQuery);
                
                // Создаем массив для хранения диагнозов текущего осмотра
                $diagnoses = [];
                
                // Проверяем, есть ли результаты запроса диагнозов
                if ($diagnosisResult->num_rows > 0) {
                    while ($diagnosisRow = $diagnosisResult->fetch_assoc()) {
                        // Добавляем каждый диагноз в массив
                        $diagnoses[] = [
                            'id' => $diagnosisRow['id'],
                            'createTime' => $diagnosisRow['createTime'],
                            'code' => $diagnosisRow['code'],
                            'name' => $diagnosisRow['name'],
                            'description' => $diagnosisRow['description'],
                            'type' => $diagnosisRow['type']
                        ];
                    }
                }
                
                // Добавляем информацию о диагнозах к осмотру
                $row['diagnoses'] = $diagnoses;
                $ArrayOfInspections[] = $row;
            }
            
            // Формируем объект JSON с осмотрами и пагинацией
            $response = [
                'inspections' => $ArrayOfInspections,
                'pagination' => [
                    'size' => $size,
                    'count' => $listOfInspectionsResult->num_rows,
                    'current' => $page
                ]
            ];
            // Возвращаем данные в виде JSON
            echo json_encode($response);
            setHTTPSStatus("200", "inspections have been successfully received");
        } else {
            // Если осмотры не найдены, возвращаем статус 404 (Not Found)
            setHTTPSStatus("404", "Inspections not found");
        }
    } else {
        // Если пациент не найден, возвращаем статус 404 (Not Found)
        setHTTPSStatus("404", "Patient not found");
    }
}

function validateArguments($grouped, $icdRoots, $page, $size){

    if (validatePaginationParameters($page, $size)&&($grouped==true or $grouped==false)){
        if (!is_null($icdRoots)){
            if (searchICD10Roots($icdRoots)){
                return true;
            }
            return false;
        }
        return true;
    }
    return false;
}

function searchICD10Roots($icdRoots){
    global $Link;
    $query = "SELECT * FROM icd10 WHERE idParent IS NULL AND (id='$icdRoots' OR mkb_code='$icdRoots' OR mkb_name='$icdRoots')";
    // Выполнение запроса
    $result = $Link->query($query);
        // Проверка на ошибку выполнения запроса
    if (!$result) {
        setHTTPSStatus("500", "InternalServerError");
        return false;
    }
        // Подготовка данных для ответа
    $rootElements = [];
    while ($row = $result->fetch_assoc()) {
        $rootElements[] = $row;
    
    }
    if (empty($rootElements)){ 
        setHTTPSStatus("400", "Invalid argument(icdRoots) for filtration");
        return false;
    }
    //setHTTPSStatus("200", "Root ICD10 is exist");
    return true;
}