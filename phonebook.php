<?php
$page_title = 'Phone Book';
require_once 'app/bootstrap.php';

// Handle form submissions for groups and contacts
$errors = [];
$success = '';

// -- GROUP MANAGEMENT --
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_group'])) {
    $group_name = trim($_POST['group_name']);
    if (!empty($group_name)) {
        $stmt = $conn->prepare("INSERT INTO phonebook_groups (user_id, group_name) VALUES (?, ?)");
        $stmt->bind_param("is", $current_user['id'], $group_name);
        if ($stmt->execute()) {
            $success = "Group '$group_name' created successfully.";
        } else {
            $errors[] = "Failed to create group.";
        }
        $stmt->close();
    } else {
        $errors[] = "Group name cannot be empty.";
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_group'])) {
    $group_id_to_delete = (int)$_POST['group_id'];
    $stmt = $conn->prepare("DELETE FROM phonebook_groups WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id_to_delete, $current_user['id']);
    if ($stmt->execute()) {
        $success = "Group deleted successfully.";
    } else {
        $errors[] = "Failed to delete group.";
    }
    $stmt->close();
}

// -- CONTACT MANAGEMENT --
// Add Contact
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_contact'])) {
    $phone_number = trim($_POST['phone_number']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $birthday = !empty($_POST['birthday']) ? trim($_POST['birthday']) : null;
    $group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;

    if (!empty($phone_number)) {
        $stmt = $conn->prepare("INSERT INTO phonebook_contacts (user_id, group_id, phone_number, first_name, last_name, birthday) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $current_user['id'], $group_id, $phone_number, $first_name, $last_name, $birthday);
        if ($stmt->execute()) {
            $success = "Contact added successfully.";
        } else {
            $errors[] = "Failed to add contact.";
        }
        $stmt->close();
    } else {
        $errors[] = "Phone number cannot be empty.";
    }
}
// Edit Contact
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_contact'])) {
    $contact_id = filter_input(INPUT_POST, 'contact_id', FILTER_VALIDATE_INT);
    $phone_number = trim($_POST['phone_number']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $birthday = !empty($_POST['birthday']) ? trim($_POST['birthday']) : null;
    $group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;

    if ($contact_id && !empty($phone_number)) {
        $stmt = $conn->prepare("UPDATE phonebook_contacts SET phone_number = ?, first_name = ?, last_name = ?, birthday = ?, group_id = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssssiii", $phone_number, $first_name, $last_name, $birthday, $group_id, $contact_id, $current_user['id']);
        if ($stmt->execute()) {
            $success = "Contact updated successfully.";
        } else {
            $errors[] = "Failed to update contact.";
        }
        $stmt->close();
    } else {
        $errors[] = "Invalid data for updating contact.";
    }
}
// Delete Contact
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_contact'])) {
    $contact_id_to_delete = (int)$_POST['contact_id'];
    $stmt = $conn->prepare("DELETE FROM phonebook_contacts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $contact_id_to_delete, $current_user['id']);
    if ($stmt->execute()) {
        $success = "Contact deleted successfully.";
    } else {
        $errors[] = "Failed to delete contact.";
    }
    $stmt->close();
}
// Upload Contacts
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_contacts'])) {
    if (isset($_FILES['contact_file']) && $_FILES['contact_file']['error'] == 0) {
        $file = $_FILES['contact_file'];
        $group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
        $allowed_types = ['text/plain', 'text/csv', 'application/vnd.ms-excel'];

        if (in_array($file['type'], $allowed_types)) {
            $file_path = $file['tmp_name'];
            $handle = fopen($file_path, "r");
            if ($handle) {
                $imported_count = 0;
                $stmt = $conn->prepare("INSERT INTO phonebook_contacts (user_id, group_id, phone_number, first_name, last_name) VALUES (?, ?, ?, ?, ?)");

                while (($line = fgets($handle)) !== false) {
                    $data = str_getcsv(trim($line));
                    $phone_number = $data[0] ?? null;
                    $first_name = $data[1] ?? null;
                    $last_name = $data[2] ?? null;

                    if (!empty($phone_number)) {
                        $stmt->bind_param("iisss", $current_user['id'], $group_id, $phone_number, $first_name, $last_name);
                        if ($stmt->execute()) {
                            $imported_count++;
                        }
                    }
                }
                fclose($handle);
                $stmt->close();
                $success = "Successfully imported $imported_count contacts.";
            } else {
                $errors[] = "Could not open the uploaded file.";
            }
        } else {
            $errors[] = "Invalid file type. Please upload a TXT or CSV file.";
        }
    } else {
        $errors[] = "File upload error. Please try again.";
    }
}


// Fetch groups and contacts for the current user
$groups = [];
$stmt_groups = $conn->prepare("SELECT id, group_name FROM phonebook_groups WHERE user_id = ? ORDER BY group_name ASC");
$stmt_groups->bind_param("i", $current_user['id']);
$stmt_groups->execute();
$result_groups = $stmt_groups->get_result();
while ($row = $result_groups->fetch_assoc()) {
    $groups[] = $row;
}
$stmt_groups->close();

$selected_group = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 'all';
$contacts = [];
$sql_contacts = "SELECT c.id, c.phone_number, c.first_name, c.last_name, c.birthday, g.group_name FROM phonebook_contacts c LEFT JOIN phonebook_groups g ON c.group_id = g.id WHERE c.user_id = ?";
if ($selected_group !== 'all' && is_numeric($selected_group)) {
    $sql_contacts .= " AND c.group_id = ?";
    $stmt_contacts = $conn->prepare($sql_contacts);
    $stmt_contacts->bind_param("ii", $current_user['id'], $selected_group);
} else {
    $stmt_contacts = $conn->prepare($sql_contacts);
    $stmt_contacts->bind_param("i", $current_user['id']);
}
$stmt_contacts->execute();
$result_contacts = $stmt_contacts->get_result();
while ($row = $result_contacts->fetch_assoc()) {
    $contacts[] = $row;
}
$stmt_contacts->close();

include 'includes/header.php';
?>
<link rel="stylesheet" href="css/phonebook.css">

<div class="row">
    <!-- Group Management -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Contact Groups</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                    <i class="fas fa-plus"></i> New Group
                </button>
            </div>
            <div class="list-group list-group-flush">
                <a href="phonebook.php" class="list-group-item list-group-item-action <?php echo ($selected_group === 'all') ? 'active' : ''; ?>">
                    <i class="fas fa-inbox me-2"></i> All Contacts
                </a>
                <?php foreach ($groups as $group): ?>
                <a href="phonebook.php?group_id=<?php echo $group['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo ($selected_group == $group['id']) ? 'active' : ''; ?>">
                    <span><i class="far fa-folder me-2"></i> <?php echo htmlspecialchars($group['group_name']); ?></span>
                    <form action="phonebook.php" method="POST" onsubmit="return confirm('Are you sure?');" class="d-inline">
                        <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                        <button type="submit" name="delete_group" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                    </form>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Contact List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Contacts</h5>
                 <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addContactModal">
                        <i class="fas fa-plus"></i> Add Contact
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadContactsModal">
                        <i class="fas fa-upload"></i> Upload Contacts
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <p class="mb-0"><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Phone Number</th>
                            <th>First Name</th>
                            <th>Birthday</th>
                            <th>Group</th>
                            <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contacts)): ?>
                        <tr><td colspan="5" class="text-center">No contacts found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($contacts as $contact): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contact['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($contact['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($contact['birthday'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($contact['group_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="#" class="btn btn-info btn-xs" data-bs-toggle="modal" data-bs-target="#editContactModal<?php echo $contact['id']; ?>"><i class="fas fa-edit"></i></a>
                                        <form action="phonebook.php" method="POST" onsubmit="return confirm('Are you sure?');" class="d-inline">
                                            <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                            <button type="submit" name="delete_contact" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Add Group Modal -->
<div class="modal fade" id="addGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="phonebook.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="group_name">Group Name</label>
                        <input type="text" class="form-control" name="group_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_group" class="btn btn-primary">Save Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="phonebook.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" class="form-control" name="phone_number" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control" name="first_name">
                    </div>
                    <div class="form-group mb-3">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control" name="last_name">
                    </div>
                    <div class="form-group mb-3">
                        <label for="birthday">Birthday</label>
                        <input type="date" class="form-control" name="birthday">
                    </div>
                    <div class="form-group">
                        <label for="group_id">Group</label>
                        <select name="group_id" class="form-control">
                            <option value="">-- No Group --</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_contact" class="btn btn-primary">Save Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Contact Modals -->
<?php foreach ($contacts as $contact): ?>
<div class="modal fade" id="editContactModal<?php echo $contact['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="phonebook.php" method="POST">
                <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Phone Number</label>
                        <input type="text" class="form-control" name="phone_number" value="<?php echo htmlspecialchars($contact['phone_number']); ?>" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>First Name</label>
                        <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($contact['first_name']); ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label>Last Name</label>
                        <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($contact['last_name']); ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label>Birthday</label>
                        <input type="date" class="form-control" name="birthday" value="<?php echo htmlspecialchars($contact['birthday']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Group</label>
                        <select name="group_id" class="form-control">
                            <option value="">-- No Group --</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['id']; ?>" <?php if(isset($contact['group_name']) && $contact['group_name'] == $group['group_name']) echo 'selected'; ?>><?php echo htmlspecialchars($group['group_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_contact" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>


<!-- Upload Contacts Modal -->
<div class="modal fade" id="uploadContactsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="phonebook.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Contacts from File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        Upload a .txt or .csv file. Each line should contain one contact.
                        <br><strong>Formats:</strong>
                        <br> - <code>phone_number</code> (one number per line)
                        <br> - <code>phone_number,first_name,last_name</code> (comma-separated)
                    </p>
                    <div class="form-group">
                        <label for="contact_file">Contact File</label>
                        <input type="file" class="form-control" name="contact_file" required>
                    </div>
                    <div class="form-group">
                        <label for="group_id">Add to Group (Optional)</label>
                        <select name="group_id" class="form-control">
                            <option value="">-- No Group --</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="upload_contacts" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
