<?php
// Удаление всех существующих токенов для врача
function deleteExistingTokens($doctorId) {
    global $Link;
    $deleteTokensQuery = "DELETE FROM token WHERE doctorId='$doctorId'";
    return $Link->query($deleteTokensQuery) === TRUE;
}

// Вставка нового токена
function insertToken($token, $doctorId, $createTime) {
    global $Link;
    $tokenInsertQuery = "INSERT INTO token(value, doctorId, createTime) VALUES('$token', '$doctorId', '$createTime')";
    return $Link->query($tokenInsertQuery) === TRUE;
}

// Получение информации о специальности доктора
function fetchSpeciality($specialityId) {
    global $Link;
    $query = "SELECT * FROM speciality WHERE id='$specialityId'";
    $result = $Link->query($query);
    if (!$result) {
        setHTTPSStatus("500", "InternalServerError: " . $Link->error);
        return false;
    }
    return $result->fetch_assoc();
}
