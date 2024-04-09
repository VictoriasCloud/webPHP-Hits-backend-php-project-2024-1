
<?php
function getRootICD10Elements() {
    global $Link;

    // Проверка токена
    if (checkToken($Link)) {
    // Подготовка запроса для выборки корневых элементов
    $query = "SELECT * FROM icd10 WHERE idParent='null'";

    // Выполнение запроса
    $result = $Link->query($query);
    // Проверка на ошибку выполнения запроса
    if (!$result) {
        setHTTPSStatus("500", "InternalServerError");
        return;
    }
    // Подготовка данных для ответа
    $rootElements = [];
    while ($row = $result->fetch_assoc()) {
        $rootElements[] = $row;
    }

    // Формирование ответа в формате JSON
    echo json_encode($rootElements);
    }
    setHTTPSStatus("200", "Root ICD-10 elements retrieved");

    return;
}