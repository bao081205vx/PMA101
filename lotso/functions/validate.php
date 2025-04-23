<?php
function validateRequiredFields($data, $required) {
    $missing = array_diff($required, array_keys($data));
    if (!empty($missing)) {
        sendResponse(400, false, 'Missing required fields: ' . implode(', ', $missing));
    }
    return true;
}

function validateInt($value, $fieldName) {
    if (!filter_var($value, FILTER_VALIDATE_INT)) {
        sendResponse(400, false, "Invalid {$fieldName}: must be an integer");
    }
    return true;
}

function validateFloat($value, $fieldName) {
    if (!filter_var($value, FILTER_VALIDATE_FLOAT)) {
        sendResponse(400, false, "Invalid {$fieldName}: must be a number");
    }
    return true;
}

function validateString($value, $fieldName, $maxLength = null) {
    if (!is_string($value)) {
        sendResponse(400, false, "Invalid {$fieldName}: must be a string");
    }
    if ($maxLength && strlen($value) > $maxLength) {
        sendResponse(400, false, "{$fieldName} is too long (maximum is {$maxLength} characters)");
    }
    return true;
}

function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(400, false, 'Invalid email format');
    }
    return true;
}

function validateDate($date, $format = 'Y-m-d H:i:s') {
    // Thử với định dạng đầy đủ trước
    $d = DateTime::createFromFormat($format, $date);
    if ($d && $d->format($format) === $date) {
        return true;
    }
    
    // Nếu không thành công, thử với định dạng ngày tháng
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if ($d && $d->format('Y-m-d') === $date) {
        return true;
    }
    
    sendResponse(false, 'Định dạng ngày không hợp lệ. Sử dụng Y-m-d hoặc Y-m-d H:i:s', null, 400);
    return false;
}
