<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$phonebook = ['groups' => [], 'contacts' => []];

// Fetch phonebook groups
$group_stmt = $conn->prepare("
    SELECT pg.id, pg.group_name, COUNT(pc.id) as contact_count
    FROM phonebook_groups pg
    LEFT JOIN phonebook_contacts pc ON pg.id = pc.group_id
    WHERE pg.user_id = ?
    GROUP BY pg.id, pg.group_name
    ORDER BY pg.group_name ASC
");
$group_stmt->bind_param("i", $user_id);
$group_stmt->execute();
$result = $group_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $phonebook['groups'][] = $row;
}
$group_stmt->close();

// Fetch contacts and their numbers, grouped by their group
$contact_stmt = $conn->prepare("
    SELECT pc.id, pc.group_id, pc.phone_number, pc.first_name, pc.last_name
    FROM phonebook_contacts pc
    WHERE pc.user_id = ?
    ORDER BY pc.group_id, pc.first_name, pc.last_name
");
$contact_stmt->bind_param("i", $user_id);
$contact_stmt->execute();
$result = $contact_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // We can group contacts by group_id on the client side if needed
    $phonebook['contacts'][] = $row;
}
$contact_stmt->close();


echo json_encode(['success' => true, 'phonebook' => $phonebook]);
?>
