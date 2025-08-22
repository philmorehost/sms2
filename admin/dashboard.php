<?php
$page_title = 'Admin Dashboard';
include 'includes/header.php';

// Fetch stats for the dashboard
// Total Users
$total_users = $conn->query("SELECT COUNT(id) as count FROM users WHERE is_admin = 0")->fetch_assoc()['count'];
// Total Messages Sent
$total_messages = $conn->query("SELECT COUNT(id) as count FROM messages")->fetch_assoc()['count'];
// Total Groups
$total_groups = $conn->query("SELECT COUNT(id) as count FROM phonebook_groups")->fetch_assoc()['count'];
// Total Contacts
$total_contacts = $conn->query("SELECT COUNT(id) as count FROM phonebook_contacts")->fetch_assoc()['count'];

?>

<div class="row">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-primary">
            <div class="inner">
                <h3><?php echo $total_users; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="users.php" class="stat-box-footer">Manage Users <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-success">
            <div class="inner">
                <h3><?php echo $total_messages; ?></h3>
                <p>Messages Sent</p>
            </div>
            <div class="icon">
                <i class="fas fa-paper-plane"></i>
            </div>
            <a href="reports.php" class="stat-box-footer">View Reports <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-info">
            <div class="inner">
                <h3><?php echo $total_groups; ?></h3>
                <p>Contact Groups</p>
            </div>
            <div class="icon">
                <i class="fas fa-address-book"></i>
            </div>
            <a href="#" class="stat-box-footer">View Details <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stat-box bg-secondary">
            <div class="inner">
                <h3><?php echo $total_contacts; ?></h3>
                <p>Total Contacts</p>
            </div>
            <div class="icon">
                <i class="fas fa-book"></i>
            </div>
            <a href="#" class="stat-box-footer">View Details <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent User Registrations</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Username</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_users_stmt = $conn->query("SELECT username, email, phone_number, created_at FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 5");
                            while ($row = $recent_users_stmt->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td><?php echo $row['created_at']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
