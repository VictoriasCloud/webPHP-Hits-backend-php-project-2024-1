<?php

function editComment($commentId, $requestData) {
    global $Link;

    $doctorId = getDoctorIdFromToken();
    if (!$doctorId) {
        setHTTPSStatus("401", "Unauthorized");
        return;
    }

    if (!isCommentExists($commentId)) {
        setHTTPSStatus("404", "Comment not found");
        return;
    }

    if (!isUserCommentAuthor($commentId, $doctorId)) {
        setHTTPSStatus("403", "You do not have permission to edit this comment");
        return;
    }

    $newContent = getValidContent($requestData);
    if (!$newContent) {
        setHTTPSStatus("400", "Invalid content. Comment content must be at least 2 characters long.");
        return;
    }

    if (!updateCommentInDatabase($commentId, $newContent)) {
        setHTTPSStatus("500", "Error updating comment");
        return;
    }

    setHTTPSStatus("200");
}

function isCommentExists($commentId) {
    global $Link;
    $commentQuery = "SELECT id FROM comments WHERE id='$commentId'";
    $commentResult = $Link->query($commentQuery);
    return $commentResult && $commentResult->num_rows > 0;
}

// является ли пользователь автором комментария
function isUserCommentAuthor($commentId, $doctorId) {
    global $Link;
    $authorQuery = "SELECT authorId FROM comments WHERE id='$commentId'";
    $authorResult = $Link->query($authorQuery);
    return $authorResult && $authorResult->fetch_assoc()['authorId'] == $doctorId;
}

function getValidContent($requestData) {
    $content = $requestData->body->content ?? null;
    return ($content && strlen(trim($content)) >= 2) ? $content : null;
}

// Обновление комм-я
function updateCommentInDatabase($commentId, $newContent) {
    global $Link;
    $modifiedDate = date('Y-m-d\TH:i:s.u');
    $updateQuery = "UPDATE comments SET content='$newContent', modifiedDate='$modifiedDate' WHERE id='$commentId'";
    return $Link->query($updateQuery) === TRUE;
}