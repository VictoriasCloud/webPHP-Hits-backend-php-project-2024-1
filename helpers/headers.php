<?php

// Вспомогательные функции для формирования HTTP-статусов

function setHTTPSStatus($status = "200", $message = null) {
    // Устанавливаем HTTP-статус на основе переданного значения
    switch ($status) {
        default:
        case "200":
            $statusHeader = "HTTP/1.0 200 OK";
            $responseType = 'message';
            break;
        case "400":
            $statusHeader = "HTTP/1.0 400 Bad Request";
            $responseType = 'error';
            break;
        case "401":
            $statusHeader = "HTTP/1.0 401 Unauthorized";
            $responseType = 'error';
            break;
        case "403":
            $statusHeader = "HTTP/1.0 403 Forbidden";
            $responseType = 'error';
            break;
        case "404":
            $statusHeader = "HTTP/1.0 404 Not Found";
            $responseType = 'error';
            break;
        case "409":
            $statusHeader = "HTTP/1.0 409 Conflict";
            $responseType = 'error';
            break;
        case "500":
            $statusHeader = "HTTP/1.0 500 Internal Server Error";
            $responseType = 'error';
            break;
    }

    // Отправляем HTTP-заголовок
    header($statusHeader);

    // Отправляем ответ в формате JSON с ключом 'message' для 200, 'error' для остальных
    if (!is_null($message)) {
        echo json_encode([$responseType => $message]);
    }
}
