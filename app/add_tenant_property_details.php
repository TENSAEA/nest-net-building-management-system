<?php
    header('Content-Type: application/json');

    // Include database connection
    include '../db.php'; // Adjust the path as necessary

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize and validate inputs
        $tenant_id = isset($_POST['tenant_id']) ? mysqli_real_escape_string($conn, $_POST['tenant_id']) : '';
        $room = isset($_POST['room']) ? mysqli_real_escape_string($conn, $_POST['room']) : '';
        $tenants_property_no = isset($_POST['tenants_property_no']) ? mysqli_real_escape_string($conn, $_POST['tenants_property_no']) : '';
        $property_names = isset($_POST['property_name']) ? $_POST['property_name'] : [];
        $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];
        $descriptions = isset($_POST['description']) ? $_POST['description'] : [];

        if (empty($tenant_id) || empty($room) || empty($tenants_property_no)) {
            echo json_encode(['success' => false, 'error' => 'Required fields are missing.']);
            exit;
        }

        // Insert into tenant_property table
        $insert_query = "INSERT INTO tenants_property (tenant, room, tenants_property_no) VALUES ('$tenant_id', '$room', '$tenants_property_no')";
        if (mysqli_query($conn, $insert_query)) {
            $tenant_property_id = mysqli_insert_id($conn);

            // Insert property details
            $details_query = "INSERT INTO tenants_property_details (tenants_property_no, property_name, quantity, description) VALUES ";
            $details_values = [];
            for ($i = 0; $i < count($property_names); $i++) {
                $p_name = mysqli_real_escape_string($conn, $property_names[$i]);
                $quantity = mysqli_real_escape_string($conn, $quantities[$i]);
                $description = mysqli_real_escape_string($conn, $descriptions[$i]);
                $details_values[] = "('$tenants_property_no', '$p_name', '$quantity', '$description')";
            }
            $details_query .= implode(", ", $details_values);

            if (mysqli_query($conn, $details_query)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add property details: ' . mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add tenant property: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    }
    ?>