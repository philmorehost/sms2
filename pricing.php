<?php
$page_title = 'Service Pricing';
require_once 'app/bootstrap.php';
include 'includes/header.php';

// Fetch all the settings, which now includes our service prices
$settings = get_settings();

// Define the services and their corresponding setting keys and descriptions
$services = [
    'Promotional SMS' => [
        'key' => 'price_sms_promo',
        'description' => 'Standard route for marketing and promotional messages.',
        'unit' => 'per SMS'
    ],
    'Corporate SMS' => [
        'key' => 'price_sms_corp',
        'description' => 'High-priority route for transactional alerts and notifications.',
        'unit' => 'per SMS'
    ],
    'Text-to-Speech (Voice SMS)' => [
        'key' => 'price_voice_tts',
        'description' => 'Send messages that are read out loud to the recipient.',
        'unit' => 'per call'
    ],
    'OTP Messages' => [
        'key' => 'price_otp',
        'description' => 'Specialized route for sending One-Time Passwords.',
        'unit' => 'per OTP'
    ],
    'WhatsApp Messages' => [
        'key' => 'price_whatsapp',
        'description' => 'Send template-based messages via WhatsApp.',
        'unit' => 'per message'
    ]
];

?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4">Our Pricing</h1>
        <p class="lead text-muted">Simple, transparent pricing for all our services. No hidden fees.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover text-center">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-start">Service</th>
                                    <th scope="col">Description</th>
                                    <th scope="col">Price per Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service_name => $details): ?>
                                    <?php
                                        // Get the price from settings, with a default of 0 if not set
                                        $price = (float)($settings[$details['key']] ?? 0);
                                    ?>
                                    <tr>
                                        <th scope="row" class="text-start fs-5"><?php echo $service_name; ?></th>
                                        <td class="align-middle"><?php echo $details['description']; ?></td>
                                        <td class="align-middle fs-4 fw-bold">
                                            <?php echo get_currency_symbol(); ?><?php echo number_format($price, 3); ?>
                                            <small class="text-muted d-block"><?php echo $details['unit']; ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center text-muted">
                    <p class="mb-0">Prices are subject to change. Your wallet will always be debited based on the rates shown here at the time of sending.</p>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
