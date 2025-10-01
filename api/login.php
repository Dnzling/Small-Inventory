<?php
session_start(); 
    
$host = "sql104.infinityfree.com";
$user = "if0_40063365";
$pass = "Faithanne143";
$dbname = "if0_40063365_inventory_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        die("Email and password are required!");
    }

    $stmt = $conn->prepare("SELECT password, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashedPassword, $name);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            // ✅ Save user session
            echo "Login success!";
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
        } else {
            echo "Invalid password!";
        }
    } else {
        echo "No such user!";
    }

    $stmt->close();
}

$conn->close();
?>
