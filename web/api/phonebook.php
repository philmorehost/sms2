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

} elseif ($action === 'add_group') {
    $group_name = trim($_POST['group_name'] ?? '');
    if (empty($group_name)) mobile_api_error('Group name is required');

    $stmt = $conn->prepare("INSERT INTO phonebook_groups (user_id, group_name) VALUES (?, ?)");
    $stmt->bind_param("is", $user['id'], $group_name);
    if ($stmt->execute()) {
        mobile_api_success(['group_id' => $conn->insert_id], 'Group created successfully');
    } else {
        mobile_api_error('Failed to create group');
    }

} elseif ($action === 'delete_group') {
    $group_id = (int)$_POST['group_id'];
    if ($group_id <= 0) mobile_api_error('Valid group ID required');

    // Delete contacts first
    $stmt = $conn->prepare("DELETE FROM phonebook_contacts WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user['id']);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM phonebook_groups WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user['id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        mobile_api_success([], 'Group deleted successfully');
    } else {
        mobile_api_error('Group not found or could not be deleted');
    }

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
    $phone     = trim($_POST['phone_number'] ?? $_POST['phone'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');

    if (empty($phone)) mobile_api_error('Phone number required');

    $stmt = $conn->prepare("INSERT INTO phonebook_contacts (user_id, group_id, phone_number, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user['id'], $group_id, $phone, $first_name, $last_name);

    if ($stmt->execute()) {
        mobile_api_success(['contact_id' => $conn->insert_id], 'Contact added');
    } else {
        mobile_api_error('Failed to add contact');
    }

} elseif ($action === 'delete_contact') {
    $contact_id = (int)$_POST['contact_id'];
    if ($contact_id <= 0) mobile_api_error('Valid contact ID required');

    $stmt = $conn->prepare("DELETE FROM phonebook_contacts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $contact_id, $user['id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        mobile_api_success([], 'Contact deleted');
    } else {
        mobile_api_error('Contact not found');
    }

} else {
    mobile_api_error('Invalid action');
}
?>
