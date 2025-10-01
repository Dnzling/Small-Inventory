<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

// Handle OPTIONS request (preflight)
if ($method == 'OPTIONS') {
    http_response_code(200); // Respond with 200 for OPTIONS
    exit();
}

$host = "sql104.infinityfree.com";
$user = "if0_40063365";
$pass = "Faithanne143";
$dbname = "if0_40063365_inventory_db";

$db = new mysqli($host, $user, $pass, $dbname);
if ($db->connect_error) die(json_encode(['error' => 'Connection failed: ' . $db->connect_error]));

$input = json_decode(file_get_contents('php://input'), true);

if ($method == 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
    } else {
        $result = $db->query("SELECT * FROM items ORDER BY id DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
    }
}
elseif ($method == 'POST') {
    // Check the action type
    if (isset($input['action'])) {
        switch ($input['action']) {
           case 'create':
                // Create a new item
                if (isset($input['name'], $input['category'], $input['quantity'], $input['price'])) {
                    $stmt = $db->prepare("INSERT INTO items (name, category, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssid", $input['name'], $input['category'], $input['quantity'], $input['price']);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        echo json_encode(['success' => true, 'id' => $db->insert_id]);
                    } else {
                        echo json_encode(['error' => 'Failed to create item']);
                    }
                } else {
                    echo json_encode(['error' => 'Missing item details']);
                }
                break;
                
            case 'delete':
                if (isset($input['id'])) {
                    $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
                    $stmt->bind_param("i", $input['id']);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['error' => 'Item not found or failed to delete']);
                    }
                } else {
                    echo json_encode(['error' => 'Missing item ID']);
                }
                break;

            case 'update':
                if (isset($input['id'], $input['name'], $input['category'], $input['quantity'], $input['price'])) {
                    $stmt = $db->prepare("UPDATE items SET name = ?, category = ?, quantity = ?, price = ? WHERE id = ?");
                    $stmt->bind_param("ssidi", $input['name'], $input['category'], $input['quantity'], $input['price'], $input['id']);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['error' => 'Failed to update item']);
                    }
                } else {
                    echo json_encode(['error' => 'Missing item details']);
                }
                break;
                
                
            case 'bulk_create':
                if (isset($input['items']) && is_array($input['items'])) {
                    $stmt = $db->prepare("INSERT INTO items (name, category, quantity, price) VALUES (?, ?, ?, ?)");

                    foreach ($input['items'] as $item) {
                        if (isset($item['name'], $item['category'], $item['quantity'], $item['price'])) {
                            $stmt->bind_param("ssid", $item['name'], $item['category'], $item['quantity'], $item['price']);
                            $stmt->execute();
                        }
                    }

                    echo json_encode(['success' => true, 'message' => 'Bulk insert completed']);
                } else {
                    echo json_encode(['error' => 'No items provided']);
                }
                break;

            default:
                echo json_encode(['error' => 'Invalid action']);
                break;
                
                
        }
    } else {
        echo json_encode(['error' => 'Missing action parameter']);
    }
}

$db->close();
?>
