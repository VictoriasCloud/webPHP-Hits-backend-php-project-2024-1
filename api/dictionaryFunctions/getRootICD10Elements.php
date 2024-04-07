
<?php
function getRootICD10Elements() {
    global $Link;

    // Проверка токена
    if (checkToken($Link)) {
    // Подготовка запроса для выборки корневых элементов
    $query = "SELECT * FROM icd10 WHERE idParent='null'";

    // Выполнение запроса
    $result = $Link->query($query);

    // Подготовка данных для ответа
    $rootElements = [];
    while ($row = $result->fetch_assoc()) {
        $rootElements[] = $row;
    }

    // Формирование ответа в формате JSON
    echo json_encode($rootElements);
    }

    return;
}