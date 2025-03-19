<?php
        header('Content-Type: application/json');
        include '../db.php'; // Adjust the path as necessary

        $sql = "SELECT * FROM penality";
        $result = $conn->query($sql);

        if ($result) {
            $penalities = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'penalities' => $penalities]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch penalities.']);
        }

        $conn->close();
        ?>