package com.philmoresms.app.ui.screens.payment

import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ContentCopy
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.ui.components.FintechButton
import com.philmoresms.app.ui.components.FintechInput
import com.philmoresms.app.ui.theme.Background
import com.philmoresms.app.ui.theme.Primary
import com.philmoresms.app.ui.theme.TextPrimary
import com.philmoresms.app.ui.theme.TextSecondary
import kotlinx.coroutines.launch

class PaymentViewModel : ViewModel() {
    var amount by mutableStateOf("")
    var reference by mutableStateOf("")
    var paymentMethod by mutableStateOf("paystack") // paystack | manual
    
    var manualSettings by mutableStateOf<Map<String, Any>?>(null)
    var authorizationUrl by mutableStateOf<String?>(null)
    
    var loading by mutableStateOf(false)
    var settingsLoading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    var vatPercentage by mutableStateOf(0.0)

    fun fetchSettings() {
        settingsLoading = true
        error = null
        viewModelScope.launch {
            try {
                val response = RetrofitClient.apiService.getPaymentSettings()
                if (response.isSuccessful && response.body()?.status == "success") {
                    val data = response.body()?.data
                    @Suppress("UNCHECKED_CAST")
                    manualSettings = data?.get("manual_payment") as? Map<String, Any>
                    vatPercentage = (data?.get("vat_percentage") as? Number)?.toDouble() ?: 0.0
                } else {
                    error = "Settings Error: ${response.body()?.message ?: response.code()}"
                }
            } catch (e: Exception) {
                error = "Network Error: ${e.message}"
            } finally {
                settingsLoading = false
            }
        }
    }

    fun initPayment() {
        val amt = amount.toDoubleOrNull() ?: 0.0
        if (amt <= 0) {
            error = "Enter a valid amount"
            return
        }

        loading = true
        error = null

        viewModelScope.launch {
            try {
                if (paymentMethod == "paystack") {
                    val response = RetrofitClient.apiService.initPaystack(amt)
                    if (response.isSuccessful && response.body()?.status == "success") {
                        val authUrl = response.body()?.data?.get("authorization_url")
                        if (!authUrl.isNullOrBlank()) {
                            authorizationUrl = authUrl
                        } else {
                            error = "Paystack: Authorization URL not found in response"
                        }
                    } else {
                        val errorMsg = response.body()?.message ?: "Initialization failed (HTTP ${response.code()})"
                        error = "Paystack Error: $errorMsg"
                    }
                }
            } catch (e: Exception) {
                error = "Connection Error: ${e.message}"
            } finally {
                loading = false
            }
        }
    }

    fun submitManual() {
        val amt = amount.toDoubleOrNull() ?: 0.0
        if (amt <= 0 || reference.isBlank()) {
            error = "Amount and Reference are required"
            return
        }

        loading = true
        error = null
        success = null
        
        viewModelScope.launch {
            try {
                val response = RetrofitClient.apiService.submitManualPayment(amt, reference, "")
                if (response.isSuccessful && response.body()?.status == "success") {
                    success = "Payment proof submitted successfully! We will verify and fund your wallet soon."
                    amount = ""
                    reference = ""
                } else {
                    error = "Submission Error: ${response.body()?.message ?: "Failed (HTTP ${response.code()})"}"
                }
            } catch (e: Exception) {
                error = "Network Error: ${e.message}"
            } finally {
                loading = false
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PaymentScreen(
    onBack: () -> Unit,
    viewModel: PaymentViewModel = viewModel()
) {
    LaunchedEffect(Unit) {
        viewModel.fetchSettings()
    }

    if (viewModel.authorizationUrl != null) {
        PaystackWebView(
            url = viewModel.authorizationUrl!!,
            onClose = { viewModel.authorizationUrl = null; onBack() }
        )
    } else {
        Scaffold(
            topBar = {
                TopAppBar(
                    title = { Text("Add Funds", fontWeight = FontWeight.Bold) },
                    navigationIcon = {
                        IconButton(onClick = onBack) {
                            Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                        }
                    }
                )
            }
        ) { padding ->
            Box(modifier = Modifier.fillMaxSize()) {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .background(Background)
                        .padding(padding)
                        .verticalScroll(rememberScrollState())
                        .padding(20.dp)
                ) {
                    // Header Card
                    Surface(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(20.dp),
                        color = Color.White,
                        shadowElevation = 2.dp
                    ) {
                        Column(modifier = Modifier.padding(20.dp)) {
                            Text("Funding Amount", fontSize = 14.sp, color = TextSecondary)
                            Spacer(modifier = Modifier.height(8.dp))
                            FintechInput(
                                value = viewModel.amount,
                                onValueChange = { viewModel.amount = it },
                                label = "",
                                placeholder = "0.00"
                            )
                            
                            if (viewModel.vatPercentage > 0) {
                                val amt = viewModel.amount.toDoubleOrNull() ?: 0.0
                                val charge = amt * (viewModel.vatPercentage / 100)
                                val total = amt - charge
                                
                                Spacer(modifier = Modifier.height(16.dp))
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceBetween
                                ) {
                                    Text("Charges (${viewModel.vatPercentage}%):", fontSize = 13.sp, color = TextSecondary)
                                    Text("₦${"%.2f".format(charge)}", fontSize = 13.sp, color = Color.Red, fontWeight = FontWeight.Bold)
                                }
                                HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp), color = Color.LightGray.copy(alpha = 0.3f))
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceBetween
                                ) {
                                    Text("Net Credit:", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                                    Text("₦${"%.2f".format(total)}", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Primary)
                                }
                            }
                        }
                    }

                    Spacer(modifier = Modifier.height(24.dp))
                    Text("Payment Method", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                    Spacer(modifier = Modifier.height(16.dp))
                    
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        MethodCard("Online (Paystack)", viewModel.paymentMethod == "paystack", Modifier.weight(1f)) { viewModel.paymentMethod = "paystack" }
                        MethodCard("Bank Transfer", viewModel.paymentMethod == "manual", Modifier.weight(1f)) { viewModel.paymentMethod = "manual" }
                    }

                    if (viewModel.paymentMethod == "manual") {
                        ManualPaymentDetails(viewModel)
                    } else {
                        Spacer(modifier = Modifier.height(24.dp))
                        Surface(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(16.dp),
                            color = Primary.copy(alpha = 0.05f)
                        ) {
                            Text(
                                "Secure payment via Paystack. Your wallet will be funded instantly.",
                                modifier = Modifier.padding(16.dp),
                                fontSize = 13.sp,
                                color = Primary
                            )
                        }
                    }

                    if (viewModel.error != null) {
                        Surface(
                            modifier = Modifier.fillMaxWidth().padding(top = 16.dp),
                            shape = RoundedCornerShape(12.dp),
                            color = Color(0xFFFFEBEE)
                        ) {
                            Text(text = viewModel.error!!, color = Color.Red, fontSize = 13.sp, modifier = Modifier.padding(12.dp))
                        }
                    }
                    if (viewModel.success != null) {
                        Surface(
                            modifier = Modifier.fillMaxWidth().padding(top = 16.dp),
                            shape = RoundedCornerShape(12.dp),
                            color = Color(0xFFE8F5E9)
                        ) {
                            Text(text = viewModel.success!!, color = Color(0xFF2E7D32), fontSize = 13.sp, modifier = Modifier.padding(12.dp))
                        }
                    }

                    Spacer(modifier = Modifier.height(32.dp))

                    FintechButton(
                        text = if (viewModel.loading) "Processing..." else "Continue",
                        onClick = { if (viewModel.paymentMethod == "paystack") viewModel.initPayment() else viewModel.submitManual() },
                        enabled = !viewModel.loading && !viewModel.settingsLoading
                    )
                }
                
                if (viewModel.settingsLoading) {
                    Box(modifier = Modifier.fillMaxSize().background(Color.Black.copy(alpha = 0.1f)), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = Primary)
                    }
                }
            }
        }
    }
}

@Composable
fun MethodCard(label: String, selected: Boolean, modifier: Modifier, onClick: () -> Unit) {
    Surface(
        onClick = onClick,
        modifier = modifier.height(60.dp),
        shape = RoundedCornerShape(12.dp),
        color = if (selected) Primary else Color.White,
        border = if (selected) null else androidx.compose.foundation.BorderStroke(1.dp, Color.LightGray)
    ) {
        Box(contentAlignment = Alignment.Center) {
            Text(label, color = if (selected) Color.White else TextPrimary, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
fun ManualPaymentDetails(viewModel: PaymentViewModel) {
    val settings = viewModel.manualSettings
    val clipboard = LocalClipboardManager.current

    Column(modifier = Modifier.padding(top = 24.dp)) {
        Text("Transfer to the account below:", fontWeight = FontWeight.Bold, color = Primary, fontSize = 14.sp)
        
        Surface(
            modifier = Modifier.fillMaxWidth().padding(top = 12.dp),
            shape = RoundedCornerShape(16.dp),
            color = Color.White
        ) {
            Column(modifier = Modifier.padding(16.dp)) {
                DetailRow("Bank Name", settings?.get("bank_name")?.toString() ?: "Loading...", clipboard)
                DetailRow("Account Name", settings?.get("account_name")?.toString() ?: "Loading...", clipboard)
                DetailRow("Account Number", settings?.get("account_number")?.toString() ?: "Loading...", clipboard)
            }
        }

        Spacer(modifier = Modifier.height(24.dp))

        FintechInput(
            value = viewModel.reference,
            onValueChange = { viewModel.reference = it },
            label = "Payment Reference / Sender Name",
            placeholder = "Enter reference number or your name"
        )
    }
}

@Composable
fun DetailRow(label: String, value: String, clipboard: androidx.compose.ui.platform.ClipboardManager) {
    Row(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
        Column {
            Text(label, fontSize = 11.sp, color = TextSecondary)
            Text(value, fontSize = 15.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
        }
        IconButton(onClick = { clipboard.setText(AnnotatedString(value)) }) {
            Icon(Icons.Default.ContentCopy, contentDescription = null, modifier = Modifier.size(16.dp), tint = Primary)
        }
    }
}

@Composable
fun PaystackWebView(url: String, onClose: () -> Unit) {
    Scaffold(
        topBar = {
            @OptIn(ExperimentalMaterial3Api::class)
            TopAppBar(
                title = { Text("Secure Payment") },
                navigationIcon = { IconButton(onClick = onClose) { Icon(Icons.Default.ArrowBack, contentDescription = "Close") } }
            )
        }
    ) { padding ->
        AndroidView(
            modifier = Modifier.padding(padding).fillMaxSize(),
            factory = { context ->
                WebView(context).apply {
                    settings.javaScriptEnabled = true
                    settings.domStorageEnabled = true
                    webViewClient = object : WebViewClient() {
                        override fun shouldOverrideUrlLoading(view: WebView?, request: android.webkit.WebResourceRequest?): Boolean {
                            val interceptedUrl = request?.url?.toString()
                            if (interceptedUrl != null && (interceptedUrl.contains("callback") || interceptedUrl.contains("success"))) {
                                onClose()
                                return true
                            }
                            return false
                        }
                    }
                    loadUrl(url)
                }
            }
        )
    }
}
