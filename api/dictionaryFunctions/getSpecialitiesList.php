<?php 
function getSpecialitiesList() {
    global $Link;

    // Проверка токена
    if (checkToken($Link)) {
        
        $name= $_GET['name'];
        $page= $_GET['page'];
        $size= $_GET['size'];

        if (validatePaginationParameters($page, $size)) {
            // начало строки с которой выводить результат из бд
            $startOfString = ($page - 1) * $size;
            $searchQuery = "SELECT * FROM speciality WHERE name LIKE '%$name%' LIMIT $startOfString, $size";
            $result = $Link->query($searchQuery);

            if ($result) {
                // Подготовка данных для ответа
                $specialties = [];
                while ($row = $result->fetch_assoc()) {
                    $specialties[] = $row;
                }

                // Вычисление количества страниц (count)
                $countQuery = "SELECT COUNT(*) AS total FROM speciality WHERE name LIKE '%$name%'";
                $countResult = $Link->query($countQuery);
                $totalCount = $countResult->fetch_assoc()['total'];
                $countOfPages = ceil($totalCount / $size);

                if ($page>$countOfPages){
                    setHTTPSStatus("400", "Invalid value for attribute page|page>maxPage");
                    return;
                }
                if ($result->num_rows === 0) {
                    setHTTPSStatus("400", "No specialties found|Invalid arguments for filtration");
                    return;
                }
                // Формирование ответа в формате JSON
                $answer = [
                    "specialties" => $specialties,
                    "pagination" => [
                        "size" => $size,
                        "countOfPages" => $countOfPages,
                        "current" => $page
                    ]
                ];
                echo json_encode($answer);

                // Отправка сообщения о успешном получении списка специальностей
                setHTTPSStatus("200", "Specialities paged list retrieved");
                }
            }
        else{
            setHTTPSStatus("500", "InternalServerError");
            return;
        }
    }
    return;
}


function validatePaginationParameters($page, $size) {
    // Проверка наличия параметров page и size
    if (empty($page) || empty($size)) {
        setHTTPSStatus("400", "Invalid arguments for pagination");
        return false;
    }
    return true;
}