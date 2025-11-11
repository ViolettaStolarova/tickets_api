<?php
header("Content-Type: application/json; charset=UTF-8");

require_once 'config/Auth.php';
require_once 'models/TicketModel.php';

$auth = new Auth();
$ticketModel = new TicketModel();

$headers = getallheaders();
$apiKey = $headers['X-API-Key'] ?? '';

if (!$auth->validateApiKey($apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized: Invalid or missing API key']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($uri, '/'));

if ($uri[0] !== 'api' || $uri[1] !== 'tickets') {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
    exit;
}

$id = $uri[2] ?? null;
if ($id !== null && (!is_numeric($id) || $id <= 0)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

function validateTicket($data, $isUpdate = false) {
    $required = ['event_id', 'type', 'price', 'available_quantity'];
    foreach ($required as $field) {
        if (!$isUpdate && !isset($data[$field])) {
            return "Field '$field' is required";
        }
        if ($isUpdate && isset($data[$field]) && $data[$field] === '') {
            return "Field '$field' cannot be empty";
        }
    }
    if (isset($data['event_id']) && (!is_numeric($data['event_id']) || $data['event_id'] <= 0)) {
        return 'event_id must be a positive integer';
    }
    if (isset($data['type']) && !is_string($data['type'])) {
        return 'type must be a string';
    }
    if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
        return 'price must be a non-negative number';
    }
    if (isset($data['available_quantity']) && (!is_numeric($data['available_quantity']) || $data['available_quantity'] < 0)) {
        return 'available_quantity must be a non-negative integer';
    }
    return null;
}

switch ($method) {
    case 'GET':
        if ($id) {
            $ticket = $ticketModel->getById($id);
            if ($ticket) {
                http_response_code(200);
                echo json_encode($ticket);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket not found']);
            }
        } else {
            $tickets = $ticketModel->getAll();
            http_response_code(200);
            echo json_encode($tickets);
        }
        break;

    case 'POST':
        if ($id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID should not be provided']);
            break;
        }
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            break;
        }
        $error = validateTicket($input);
        if ($error) {
            http_response_code(400);
            echo json_encode(['error' => $error]);
            break;
        }
        try {
            $newId = $ticketModel->create($input);
            http_response_code(201);
            echo json_encode(['message' => 'Ticket created', 'id' => $newId]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            break;
        }
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            break;
        }
        $error = validateTicket($input, true);
        if ($error) {
            http_response_code(400);
            echo json_encode(['error' => $error]);
            break;
        }
        try {
            $success = $ticketModel->update($id, $input);
            if ($success) {
                http_response_code(200);
                echo json_encode(['message' => 'Ticket updated']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            break;
        }
        try {
            $success = $ticketModel->delete($id);
            if ($success) {
                http_response_code(200);
                echo json_encode(['message' => 'Ticket deleted']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}