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

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    if (!$d || $d->format($format) !== $date) {
        sendResponse(400, false, 'Invalid date format. Use ' . $format);
    }
    return true;
}
