<?php

function addCommentToConsultation($consultationId, $requestData) {
    global $Link;

    // Получаем ID врача из токена
    $doctorId = getDoctorIdFromToken();
    if (!$doctorId) {
        return;
    }

    // Проверка существования консультации
    $consultationData = getConsultationData($consultationId);
    if (!$consultationData) {
        return;
    }

    // Проверка существования родительского комментария
    $parentCommentId = $requestData->body->parentId ?? null;
    if ($parentCommentId !== null) {
        if (!parentCommentExists($parentCommentId)) {
            setHTTPSStatus("404", "Parent comment not found or does not exist");
            return;
        }
    }

    // Проверка прав пользователя на добавление комментария
    if (!canAddComment($doctorId, $consultationData, $parentCommentId)) {
        setHTTPSStatus("403", "User doesn't have add comment to consultation (unsuitable specialty and not the inspection author)");
        return;
    }

    $content = $requestData->body->content ?? null;
    if (empty($content) || strlen($content) <= 1) {
        setHTTPSStatus("400", "Comment content must be more than one character");
        return;
    }
    $createTime = date('Y-m-d\TH:i:s.u');

    //добавление
    $insertCommentQuery = "INSERT INTO comments (createTime, content, authorId, idParentComment, idConsultation) 
                           VALUES ('$createTime', '$content', '$doctorId', '$parentCommentId', '$consultationId')";

    if ($Link->query($insertCommentQuery) !== TRUE) {
        setHTTPSStatus("500", "InternalServerError: " . $Link->error);
        return;
    }

    $newCommentId = $Link->insert_id;
    echo json_encode($newCommentId);
    setHTTPSStatus("200");
}



// Проверка существования родительского комментария
function parentCommentExists($parentCommentId) {
    global $Link;
    $parentCommentQuery = "SELECT id FROM comments WHERE id='$parentCommentId'";
    $parentCommentResult = $Link->query($parentCommentQuery);

    //возвращаем true, если комментарий найден
    return $parentCommentResult && $parentCommentResult->num_rows > 0;
}

// Проверка прав на добавление комментария
function canAddComment($doctorId, $consultationData, $parentCommentId) {
    global $Link;

    // Проверка, является ли пользователь автором консультации
    if ($consultationData['idDoctor'] == $doctorId) {
        return true;
    }

    // Проверка специальности пользователя
    $specialityQuery = "SELECT speciality FROM doctor WHERE id='$doctorId' 
                        AND speciality='{$consultationData['specialityId']}'";
    $specialityResult = $Link->query($specialityQuery);
    if ($specialityResult && $specialityResult->num_rows > 0) {
        return true;
    }

    // Проверка, является ли пользователь автором родительского комментария, если он указан
    if ($parentCommentId) {
        $parentAuthorQuery = "SELECT authorId FROM comments WHERE id='$parentCommentId'";
        $parentAuthorResult = $Link->query($parentAuthorQuery);
        if ($parentAuthorResult) {
            $parentAuthorId = $parentAuthorResult->fetch_assoc()['authorId'];
            if ($parentAuthorId == $doctorId) {
                return true;
            }
        }
    }

    return false;
}
