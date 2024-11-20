<?php
session_start(); // Start the session

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evanscode";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

$response = ['status' => '', 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the request is from Google Sign-In or regular subscription
    if (isset($_POST['google-signin'])) {
        // Google Sign-In
        $email = filter_var($_POST['newsletter-email'], FILTER_SANITIZE_EMAIL);
        $name = filter_var($_POST['newsletter-name'], FILTER_SANITIZE_STRING);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($name)) {
            // Check if email already exists
            $sql = "SELECT * FROM subscribers WHERE email = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $response['status'] = 'error';
                $response['message'] = 'Failed to prepare statement.';
                echo json_encode($response);
                exit();
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $response['status'] = 'info';
                $response['message'] = 'You are already subscribed.';
            } else {
                // Insert new email and name
                $sql = "INSERT INTO subscribers (email, name) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $response['status'] = 'error';
                    $response['message'] = 'Failed to prepare statement.';
                    echo json_encode($response);
                    exit();
                }
                $stmt->bind_param("ss", $email, $name);

                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Subscription successful!';
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'There was an error with your subscription.';
                }
            }
            $stmt->close();
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Invalid email address or name.';
        }
    } else {
        // Regular subscription
        $email = filter_var($_POST['newsletter-email'], FILTER_SANITIZE_EMAIL);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Check if email already exists
            $sql = "SELECT * FROM subscribers WHERE email = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $response['status'] = 'error';
                $response['message'] = 'Failed to prepare statement.';
                echo json_encode($response);
                exit();
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $response['status'] = 'info';
                $response['message'] = 'You are already subscribed.';
            } else {
                // Insert new email
                $sql = "INSERT INTO subscribers (email) VALUES (?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $response['status'] = 'error';
                    $response['message'] = 'Failed to prepare statement.';
                    echo json_encode($response);
                    exit();
                }
                $stmt->bind_param("s", $email);

                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Subscription successful!';
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'There was an error with your subscription.';
                }
            }
            $stmt->close();
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Invalid email address.';
        }
    }

    $conn->close();
    echo json_encode($response);
    exit();
}

$response['status'] = 'error';
$response['message'] = 'Invalid request method.';
echo json_encode($response);
exit();
?>
