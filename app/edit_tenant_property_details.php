<?php
    header('Content-Type: application/json');

    // Include database connection
    include '../db.php'; // Adjust the path as necessary

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize and validate inputs
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $tenant_id = isset($_POST['tenant_id']) ? mysqli_real_escape_string($conn, $_POST['tenant_id']) : '';
        $room = isset($_POST['room']) ? mysqli_real_escape_string($conn, $_POST['room']) : '';
        $tenants_property_no = isset($_POST['tenants_property_no']) ? mysqli_real_escape_string($conn, $_POST['tenants_property_no']) : '';
        $property_names = isset($_POST['property_name']) ? $_POST['property_name'] : [];
        $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];
        $descriptions = isset($_POST['description']) ? $_POST['description'] : [];

        if ($id <= 0 || empty($tenant_id) || empty($room) || empty($tenants_property_no)) {
            echo json_encode(['success' => false, 'error' => 'Required fields are missing or invalid.']);
            exit;
        }

        // Update tenant_property table
        $update_query = "UPDATE tenant_property SET tenant_id = '$tenant_id', room = '$room', tenants_property_no = '$tenants_property_no' WHERE id = '$id'";
        if (mysqli_query($conn, $update_query)) {
            // Delete existing property details
            $delete_query = "DELETE FROM tenant_property_details WHERE tenant_property_id = '$id'";
            if (mysqli_query($conn, $delete_query)) {
                // Insert updated property details
                $details_query = "INSERT INTO tenant_property_details (tenant_property_id, property_name, quantity, description) VALUES ";
                $details_values = [];
                for ($i = 0; $i < count($property_names); $i++) {
                    $p_name = mysqli_real_escape_string($conn, $property_names[$i]);
                    $quantity = mysqli_real_escape_string($conn, $quantities[$i]);
                    $description = mysqli_real_escape_string($conn, $descriptions[$i]);
                    $details_values[] = "('$id', '$p_name', '$quantity', '$description')";
                }
                $details_query .= implode(", ", $details_values);

                if (mysqli_query($conn, $details_query)) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to add property details: ' . mysqli_error($conn)]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete existing property details: ' . mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update tenant property: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    }
    ?>