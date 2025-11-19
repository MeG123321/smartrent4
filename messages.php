<?php
// Your existing PHP code...

// To be added or modified in the appropriate section:
if (isset($_POST['assign_property'])) {
    // Assuming a variable $property_id exists for the property in question
    $query = "UPDATE properties SET is_rented=1 WHERE id = ?";
    // Execute the query to set is_rented to 1
    // Your database connection and execution code here...

    // Redirect to user_panel.php instead of management_assignment.php
    header('Location: user_panel.php');
    exit();
}

// The rest of your PHP code...