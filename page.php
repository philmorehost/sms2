<?php
require_once 'app/bootstrap.php';

if (!isset($_GET['slug'])) {
    header("Location: 404.php");
    exit();
}

$slug = $_GET['slug'];

$stmt = $conn->prepare("SELECT * FROM pages WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: 404.php");
    exit();
}

$page = $result->fetch_assoc();
$stmt->close();

$page_title = htmlspecialchars($page['title']);

// Decide which header/footer to use based on visibility
if ($page['visibility'] == 'private') {
    // This header includes a login check and will redirect if not logged in
    include 'includes/header.php';
} else {
    // This is a public page, use the public header
    include 'includes/public_header.php';
}

// If page is hidden, it should not be accessible directly unless maybe by an admin
// The is_admin() function is in helpers.php, which is included by both headers.
if ($page['visibility'] == 'hidden' && !is_admin()) {
    // We show a 404 to non-admins trying to access hidden pages.
    header("Location: 404.php");
    exit();
}

?>

<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <h1 class="card-title h3"><?php echo $page_title; ?></h1>
        </div>
        <div class="card-body">
    <?php
    // Sanitize the HTML content to prevent XSS attacks.
    // NOTE: The HtmlSanitizer library was added manually to app/vendor/ due to environment constraints preventing the use of Composer.
    require_once __DIR__ . '/app/vendor/HtmlSanitizer/HtmlDataMap.php';
    require_once __DIR__ . '/app/vendor/HtmlSanitizer/Whitelist.php';
    require_once __DIR__ . '/app/vendor/HtmlSanitizer/Sanitizer.php';

    // Use the custom namespace
    use MirazMac\HtmlSanitizer\Whitelist;
    use MirazMac\HtmlSanitizer\Sanitizer;

    // Create a whitelist with tags commonly used by a WYSIWYG editor
    $whitelist = new Whitelist();
    $whitelist->setTags([
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'p' => ['style'], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => ['cite'],
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'style'],
        'figure' => [], 'figcaption' => [],
        'div' => ['style'], 'span' => ['style'],
        'table' => ['width', 'height', 'cellpadding', 'cellspacing', 'border'],
        'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['scope'], 'td' => [],
        'br' => [], 'hr' => []
    ]);
    // Allow common protocols
    $whitelist->setProtocols(['http', 'https', 'mailto']);

    // Create a sanitizer instance
    $sanitizer = new Sanitizer($whitelist);

    // Sanitize and display the page body
    $sanitized_body = $sanitizer->sanitize($page['body']);
    echo $sanitized_body;
            ?>
        </div>
    </div>
</div>

<?php
// Include the corresponding footer
if ($page['visibility'] == 'private') {
    include 'includes/footer.php';
} else {
    include 'includes/public_footer.php';
}
?>
