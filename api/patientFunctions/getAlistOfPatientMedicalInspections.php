<?php
function getAlistOfPatientMedicalInspections($patientId, $params) { 
    global $Link;
    
    $checkTokenResult = checkToken($Link);
    $grouped = $params['grouped'] ?? 'false';
    $page = (int)($params['page'] ?? 1);  // Приводим к числу для консистентности
    $size = (int)($params['size'] ?? 5);  // Приводим к числу для консистентности

    // Собираем все значения icdRoots
    $icdRoots = $params['icdRoots'] ?? [];

    // Изменяем запрос, чтобы сначала выбрать все записи с фильтрацией, а затем выполнять пагинацию в PHP
    $query = "SELECT * FROM inspection WHERE idPatient='$patientId'";
    if ($grouped === 'true') {
        $query .= " AND previousInspectionId=''";
    }
    
    $result = $Link->query($query);
    if ($result === false) {
        setHTTPSStatus("500", "Database query error: " . $Link->error);
        return;
    }

    $allInspections = [];

    // Собираем все результаты без применения LIMIT
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Замена пустых значений на null
            foreach ($row as $key => $value) {
                if ($value === '') {
                    $row[$key] = null;
                }
            }

            if (!empty($icdRoots)) {
                $icdRootsList = implode("','", array_map('intval', $icdRoots));
                $diagnosisQuery = "SELECT * FROM diagnosis 
                                   WHERE idInspection='{$row['id']}' 
                                   AND type='Main' 
                                   AND icdDiagnosisId IN ('$icdRootsList')";
            } else {
                $diagnosisQuery = "SELECT * FROM diagnosis 
                                   WHERE idInspection='{$row['id']}' 
                                   AND type='Main'";
            }
            
            $diagnosisResult = $Link->query($diagnosisQuery);
            $mainDiagnosis = $diagnosisResult->fetch_assoc();

            if (!$mainDiagnosis) {
                continue;
            }
            
            // Замена пустых значений в диагнозах на null
            foreach ($mainDiagnosis as $dKey => $dValue) {
                if ($dValue === '') {
                    $mainDiagnosis[$dKey] = null;
                }
            }

            $row['diagnosis'] = $mainDiagnosis;
            $row['hasChain'] = (bool)$row['hasChain'];
            $row['hasNested'] = (bool)$row['hasNested'];
            $allInspections[] = $row;
        }
    }

    // Рассчитываем общее количество страниц
    $totalRecords = count($allInspections);
    $totalPages = (int)ceil($totalRecords / $size); // Приводим к числу для единообразия

    // Выполняем пагинацию с помощью array_slice
    $paginatedInspections = array_slice($allInspections, ($page - 1) * $size, $size);

    // Ответ с учетом новой пагинации и пересчитанного количества страниц
    echo json_encode([
        'inspections' => $paginatedInspections,
        'pagination' => [
            'size' => $size,
            'count' => $totalPages,  // количество возможных страниц
            'current' => $page
        ]
    ]);
    setHTTPSStatus("200", "Inspections have been successfully received");
}






function validateArguments($grouped, $icdRoots, $page, $size) {
    // Преобразуем icdRoots в массив, если это одиночное значение
    if (!is_array($icdRoots) && !is_null($icdRoots)) {
        $icdRoots = [$icdRoots];
    }

    if (validatePaginationParameters($page, $size) && ($grouped === 'true' || $grouped === 'false')) {
        // Если icdRoots присутствует и не пустой, проверяем его
        if (is_array($icdRoots) && !empty($icdRoots)) {
            if (searchICD10Roots($icdRoots)) {
                return true;
            }
            return false;
        } elseif (is_null($icdRoots) || $icdRoots === "" || empty($icdRoots)) {
            // Если icdRoots пустой или отсутствует, пропускаем проверку
            return true;
        } else {
            setHTTPSStatus("400", "icdRoots must be an array if specified");
            return false;
        }
    }
    return false;
}


function searchICD10Roots($icdRoots) {
    global $Link;

    // Проверяем каждый элемент массива icdRoots
    foreach ($icdRoots as $root) {
        $query = "SELECT * FROM icd10 WHERE idParent IS NULL AND (id='$root' OR mkb_code='$root' OR mkb_name='$root')";
        $result = $Link->query($query);

        if (!$result || $result->num_rows == 0) {
            setHTTPSStatus("400", "Invalid argument(icdRoots) for filtration: $root not found");
            return false;
        }
    }

    return true;
}

