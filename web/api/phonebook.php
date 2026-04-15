<?php
// web/api/phonebook.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? 'list_groups';

if ($action === 'list_groups') {
    $groups = [];
    $stmt = $conn->prepare("SELECT id, group_name, created_at FROM phonebook_groups WHERE user_id = ? ORDER BY group_name ASC");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }
    mobile_api_success(['groups' => $groups]);
} elseif ($action === 'list_contacts') {
    $group_id = (int)$_GET['group_id'];
    $contacts = [];
    $stmt = $conn->prepare("SELECT id, phone_number, first_name, last_name FROM phonebook_contacts WHERE user_id = ? AND group_id = ?");
    $stmt->bind_param("ii", $user['id'], $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }
    mobile_api_success(['contacts' => $contacts]);
} elseif ($action === 'add_contact') {
    $group_id = (int)$_POST['group_id'];
    $phone = $_POST['phone'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';

    if (empty($phone)) mobile_api_error('Phone number required');

    $stmt = $conn->prepare("INSERT INTO phonebook_contacts (user_id, group_id, phone_number, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user['id'], $group_id, $phone, $first_name, $last_name);

    if ($stmt->execute()) {
        mobile_api_success([], 'Contact added');
    } else {
        mobile_api_error('Failed to add contact');
    }
}
?>
