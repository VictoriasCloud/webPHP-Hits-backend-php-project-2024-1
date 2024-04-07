<?php
function searchICD10Diagnoses() {
    global $Link;

    if (checkToken($Link)){

        $request= $_GET['request'];
        $page= $_GET['page'];
        $size= $_GET['size'];

        if ($page >= 1 || $size >= 1) {
        
            // Подготовка запроса для поиска диагноза и начало строки с которой выводить результат из бд
            $startOfString = ($page - 1) * $size;
            $searchQuery = "SELECT * FROM icd10 WHERE mkb_name LIKE '%$request%' OR mkb_code LIKE '%$request%' LIMIT $startOfString, $size";
        
            // Выполнение запроса
            $result = $Link->query($searchQuery);
        
            // Подготовка данных для ответа (records-каждый элемент массива-одна запись)
            $records = [];
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }

            $countOfPages=getICDPageCount($Link, $request, $size);

            //в сваггере такая ошибка вывелась, поэтому сюда и вставила проверку
            if ($page>$countOfPages){
                setHTTPSStatus("400", "Invalid value for attribute page/Bad Request");
                return;
            }
            $pagination = [
                "sizeForElements" => count($records),
                "countOfPages" => $countOfPages, // Вернуть общее количество записей
                "current" => $page
            ];
        
            // Формирование ответа в формате JSON
            echo json_encode(['records' => $records, 'pagination' => $pagination]);
        }
        else{
            setHTTPSStatus("400", "Some fields in request are invalid");
            return;
        }
    }
}

// Функция для вычисления общего количества страниц
function getICDPageCount($link, $request, $size) {
    //получение общего количества записей в таблице icd10 согласно критериям
    $query = "SELECT COUNT(*) AS count FROM icd10 WHERE mkb_name LIKE '%$request%' OR mkb_code LIKE '%$request%'";
    $result = $link->query($query);
    $totalCount = $result->fetch_assoc()['count'];
    // ceil округляет дробь в большую сторону
    $pageCount = ceil($totalCount / $size);
    return $pageCount;
}

