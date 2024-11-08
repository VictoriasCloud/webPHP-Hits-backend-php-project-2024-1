<?php
function getConsultationById($consultationId) {
    global $Link;

    // Проверка наличия консультации с заданным ID
    $consultationData = getConsultationData($consultationId);
    if (!$consultationData) {
        return;
    }

    // Получаем информацию о специальности консультации
    $specialityData = getSpecialityData($consultationData['specialityId']);
    if (!$specialityData) {
        return;
    }

    // Получаем все комментарии к консультации
    $comments = getConsultationComments($consultationId);

    // Собираем все данные о консультации
    $consultationInfo = [
        "id" => $consultationData['id'],
        "createTime" => $consultationData['createTime'],
        "inspectionId" => $consultationData['inspectionId'],
        "speciality" => [
            "id" => $specialityData['id'],
            "createTime" => $specialityData['createTime'],
            "name" => $specialityData['name']
        ],
        "comments" => $comments
    ];

    echo json_encode($consultationInfo);
    setHTTPSStatus("200");
}

//  получение данных консультации
function getConsultationData($consultationId) {
    global $Link;
    $query = "SELECT * FROM consultation WHERE id='$consultationId'";
    $result = $Link->query($query);

    if (!$result) {
        setHTTPSStatus("500", "Internal Server Error: " . $Link->error);
        return null;
    }

    if ($result->num_rows === 0) {
        setHTTPSStatus("404", "Consultation not found");
        return null;
    }

    return $result->fetch_assoc();
}

// получение данных о специальности консультации
function getSpecialityData($specialityId) {
    global $Link;
    $query = "SELECT * FROM speciality WHERE id='$specialityId'";
    $result = $Link->query($query);

    if (!$result) {
        setHTTPSStatus("500", "Internal Server Error: " . $Link->error);
        return null;
    }

    return $result->fetch_assoc();
}

// получение  всех комментариев с именами авторов
function getConsultationComments($consultationId) {
    global $Link;
    $comments = [];
    $commentsQuery = "SELECT * FROM comments WHERE idConsultation='$consultationId'";
    $commentsResult = $Link->query($commentsQuery);

    if (!$commentsResult) {
        setHTTPSStatus("500", "Internal Server Error: " . $Link->error);
        return [];
    }

    while ($commentData = $commentsResult->fetch_assoc()) {

        $authorName = getDoctorNameById($commentData['authorId']);
        if (!$authorName) {
            setHTTPSStatus("500", "Error fetching author name");
            return [];
        }

        $comments[] = [
            "id" => $commentData['id'],
            "createTime" => $commentData['createTime'],
            "modifiedDate" => $commentData['modifiedDate'],
            "content" => $commentData['content'],
            "authorId" => $commentData['authorId'],
            "author" => $authorName,
            "parentId" => $commentData['idParentComment']
        ];
    }

    return $comments;
}

// получение имени автора по id
function getDoctorNameById($doctorId) {
    global $Link;
    $query = "SELECT name FROM doctor WHERE id='$doctorId'";
    $result = $Link->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['name'];
    }

    return null;
}

