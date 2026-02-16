<?php
$page_title = 'Banner Ads Management';
include 'includes/header.php';

$errors = [];
$success = '';

// Function to handle file uploads
function upload_banner_image($file) {
    $target_dir = "../uploads/banners/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return [ "error" => "File is not an image." ];
    }

    // Check file size (5MB limit)
    if ($file["size"] > 5000000) {
        return [ "error" => "Sorry, your file is too large." ];
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        return [ "error" => "Sorry, only JPG, JPEG, PNG & GIF files are allowed." ];
    }

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [ "path" => "uploads/banners/" . basename($file["name"]) ];
    } else {
        return [ "error" => "Sorry, there was an error uploading your file." ];
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_banner'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("SELECT image_path FROM banner_ads WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (file_exists("../" . $row['image_path'])) {
            unlink("../" . $row['image_path']);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM banner_ads WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Banner ad deleted successfully.";
    } else {
        $errors[] = "Failed to delete banner ad.";
    }
    $stmt->close();
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_banner'])) {
    $link = trim($_POST['link']);
    $expires_at = !empty($_POST['expires_at']) ? trim($_POST['expires_at']) : null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_result = upload_banner_image($_FILES['image']);
        if (isset($upload_result['path'])) {
            $image_path = $upload_result['path'];
            $stmt = $conn->prepare("INSERT INTO banner_ads (image_path, link, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $image_path, $link, $expires_at);
            if ($stmt->execute()) {
                $success = "Banner ad added successfully.";
            } else {
                $errors[] = "Failed to add banner ad to database.";
            }
            $stmt->close();
        } else {
            $errors[] = $upload_result['error'];
        }
    } else {
        $errors[] = "Banner image is required.";
    }
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_banner'])) {
    $id = (int)$_POST['id'];
    $link = trim($_POST['link']);
    $expires_at = !empty($_POST['expires_at']) ? trim($_POST['expires_at']) : null;

    $image_path_update_sql = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_result = upload_banner_image($_FILES['image']);
        if (isset($upload_result['path'])) {
            $image_path = $upload_result['path'];
            // You might want to delete the old image file here
            $image_path_update_sql = ", image_path = '" . $conn->real_escape_string($image_path) . "'";
        } else {
            $errors[] = $upload_result['error'];
        }
    }

    if(empty($errors)) {
        $sql = "UPDATE banner_ads SET link = ?, expires_at = ? " . $image_path_update_sql . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $link, $expires_at, $id);
        if ($stmt->execute()) {
            $success = "Banner ad updated successfully.";
        } else {
            $errors[] = "Failed to update banner ad.";
        }
        $stmt->close();
    }
}

// --- Search Logic ---
$search_term = $_GET['search'] ?? '';
$sql = "SELECT * FROM banner_ads";
$params = [];
$types = '';

if (!empty($search_term)) {
    $sql .= " WHERE link LIKE ?";
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $types = 's';
}

$sql .= " ORDER BY created_at DESC";

// Fetch all banner ads
$banners = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $banners[] = $row;
    }
    $stmt->close();
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">All Banner Ads</h3>
        <div class="card-tools">
            <form action="" method="GET" class="form-inline">
                <div class="input-group input-group-sm" style="width: 300px;">
                    <input type="text" name="search" class="form-control float-right" placeholder="Search by Link..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBannerModal">
            <i class="fas fa-plus"></i> Add Banner Ad
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Image</th>
                        <th>Link</th>
                        <th>Expires At</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($banners)): ?>
                        <tr><td colspan="5" class="text-center">No banner ads found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($banners as $banner): ?>
                        <tr>
                            <td><img src="../<?php echo htmlspecialchars($banner['image_path']); ?>" alt="Banner Ad" style="max-width: 200px;"></td>
                            <td><?php echo htmlspecialchars($banner['link']); ?></td>
                            <td><?php echo htmlspecialchars($banner['expires_at']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($banner['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#editBannerModal<?php echo $banner['id']; ?>">Edit</button>
                                <form action="banner-ads.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                                    <button type="submit" name="delete_banner" class="btn btn-danger btn-sm">Delete</button>
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

<!-- Add Banner Modal -->
<div class="modal fade" id="addBannerModal" tabindex="-1" aria-labelledby="addBannerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="banner-ads.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBannerModalLabel">Add Banner Ad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="image" class="form-label">Banner Image</label>
                        <input type="file" class="form-control" id="image" name="image" required>
                    </div>
                    <div class="mb-3">
                        <label for="link" class="form-label">Link (optional)</label>
                        <input type="url" class="form-control" id="link" name="link">
                    </div>
                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Expires At (optional)</label>
                        <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_banner" class="btn btn-primary">Add Banner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Banner Modals -->
<?php foreach ($banners as $banner): ?>
<div class="modal fade" id="editBannerModal<?php echo $banner['id']; ?>" tabindex="-1" aria-labelledby="editBannerModalLabel<?php echo $banner['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="banner-ads.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBannerModalLabel<?php echo $banner['id']; ?>">Edit Banner Ad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                    <div class="mb-3">
                        <label for="image" class="form-label">New Banner Image (optional)</label>
                        <input type="file" class="form-control" id="image" name="image">
                    </div>
                    <div class="mb-3">
                        <label for="link" class="form-label">Link (optional)</label>
                        <input type="url" class="form-control" id="link" name="link" value="<?php echo htmlspecialchars($banner['link']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Expires At (optional)</label>
                        <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($banner['expires_at']))); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_banner" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>


<?php include 'includes/footer.php'; ?>
