<?php
$page_title = 'VTU API Documentation';
require_once 'app/bootstrap.php';
include 'includes/header.php';
?>
<style>
    .api-endpoint { border-left: 3px solid var(--primary-color); padding-left: 1rem; margin-bottom: 2rem; }
    .api-endpoint .method { font-weight: 600; }
    .api-endpoint .url { font-family: monospace; background-color: var(--light-color); padding: 0.2rem 0.5rem; border-radius: 4px; }
    pre { background-color: var(--dark-color); color: #fff; padding: 1rem; border-radius: 0.5rem; }
    code { font-family: monospace; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">VTU Services API Documentation</h1>
</div>

<div class="card">
    <div class="card-body">
        <h4 class="card-title">Introduction</h4>
        <p>
            The VTU Services API allows you to integrate Airtime, Data, Cable TV, and other bill payments directly into your application.
            All VTU actions are handled through a single endpoint with different `action` parameters.
        </p>

        <h4 class="card-title mt-4">Authentication</h4>
        <p>
            Authentication is the same as the Messaging API. Your API key must be included in the body of your `POST` requests with the parameter name `token`.
        </p>

        <hr>

        <h4 class="card-title mt-4">Main Endpoint</h4>
        <p>All VTU requests are sent to the following single endpoint:</p>
        <p>
            <span class="method text-success">POST</span>
            <span class="url"><?php echo SITE_URL; ?>/ajax/vtu_handler.php</span>
        </p>
        <p>You must specify the desired operation using the `action` parameter in your POST request.</p>

        <hr>

        <!-- Airtime Purchase -->
        <div id="purchase-airtime" class="api-endpoint">
            <h5>Purchase Airtime</h5>
            <h6>Action: <code>purchase_airtime</code></h6>
            <p>This endpoint allows you to purchase airtime for a specific phone number.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>action</code></td><td>string</td><td><strong>Required.</strong> Must be set to `purchase_airtime`.</td></tr>
                        <tr><td><code>phone</code></td><td>string</td><td><strong>Required.</strong> The recipient's phone number.</td></tr>
                        <tr><td><code>network</code></td><td>string</td><td><strong>Required.</strong> The mobile network (e.g., 'MTN', 'GLO').</td></tr>
                        <tr><td><code>amount</code></td><td>number</td><td><strong>Required.</strong> The amount of airtime to purchase.</td></tr>
                    </tbody>
                </table>
            </div>
            <h6>Example Success Response</h6>
            <pre><code>{
    "success": true,
    "message": "Your airtime request has been submitted successfully and is being processed."
}</code></pre>
        </div>
        <!-- End Airtime Purchase -->

        <!-- Data Purchase -->
        <div id="purchase-data" class="api-endpoint">
            <h5>Purchase Data Bundles</h5>
            <h6>Action: <code>purchase_data</code></h6>
            <p>This endpoint allows you to purchase a data bundle for a specific phone number.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>action</code></td><td>string</td><td><strong>Required.</strong> Must be set to `purchase_data`.</td></tr>
                        <tr><td><code>phone</code></td><td>string</td><td><strong>Required.</strong> The recipient's phone number.</td></tr>
                        <tr><td><code>network</code></td><td>string</td><td><strong>Required.</strong> The mobile network (e.g., 'MTN', 'GLO').</td></tr>
                        <tr><td><code>dataplan_id</code></td><td>integer</td><td><strong>Required.</strong> The ID of the data plan product from the `vtu_products` table.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Data Purchase -->

        <!-- Cable TV -->
        <div id="cable-tv" class="api-endpoint">
            <h5>Cable TV Subscription</h5>
            <h6>Action: <code>purchase_cable_tv</code></h6>
            <p>This endpoint allows you to pay for a Cable TV subscription.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>action</code></td><td>string</td><td><strong>Required.</strong> Must be set to `purchase_cable_tv`.</td></tr>
                        <tr><td><code>serviceID</code></td><td>string</td><td><strong>Required.</strong> The cable provider slug (e.g., 'dstv', 'gotv').</td></tr>
                        <tr><td><code>billersCode</code></td><td>string</td><td><strong>Required.</strong> The user's smartcard/IUC number.</td></tr>
                        <tr><td><code>variation_code</code></td><td>string</td><td><strong>Required.</strong> The specific plan/bouquet code (e.g., 'dstv-padi').</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Cable TV -->

        <!-- Electricity -->
        <div id="electricity" class="api-endpoint">
            <h5>Electricity Bill Payment</h5>
            <h6>Action: <code>purchase_electricity</code></h6>
            <p>This endpoint allows you to pay for electricity (prepaid or postpaid).</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>action</code></td><td>string</td><td><strong>Required.</strong> Must be set to `purchase_electricity`.</td></tr>
                        <tr><td><code>serviceID</code></td><td>string</td><td><strong>Required.</strong> The electricity disco slug (e.g., 'ikeja-electric').</td></tr>
                        <tr><td><code>billersCode</code></td><td>string</td><td><strong>Required.</strong> The user's meter number.</td></tr>
                        <tr><td><code>variation_code</code></td><td>string</td><td><strong>Required.</strong> The meter type ('prepaid' or 'postpaid').</td></tr>
                        <tr><td><code>amount</code></td><td>number</td><td><strong>Required.</strong> The amount to pay.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Electricity -->

    </div>
</div>

<?php include 'includes/footer.php'; ?>
