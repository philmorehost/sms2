package com.philmoresms.app.ui.screens.messaging

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.ui.components.FintechButton
import com.philmoresms.app.ui.components.FintechInput
import com.philmoresms.app.ui.theme.Background
import com.philmoresms.app.ui.theme.TextPrimary
import kotlinx.coroutines.launch

class SmsViewModel : ViewModel() {
    var senderId by mutableStateOf("")
    var recipients by mutableStateOf("")
    var message by mutableStateOf("")
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    var smsConfig by mutableStateOf<Map<String, Any>?>(null)
    
    val charCount get() = message.length
    
    val filteredRecipients get() = recipients.split(Regex("[\\s,;\\n]+"))
        .filter { it.isNotBlank() }
        .map { it.replace(Regex("[^0-9+]"), "") }
        .filter { it.replace("+", "").length in 10..15 }
        .distinct()

    val recipientCount get() = filteredRecipients.size

    val smsUnits: Int get() {
        val chars1Unit = (smsConfig?.get("chars_1unit") as? Number)?.toInt() ?: 160
        val charsMultUnit = (smsConfig?.get("chars_multunit") as? Number)?.toInt() ?: 153
        
        return if (charCount <= chars1Unit) {
            if (charCount > 0) 1 else 0
        } else {
            kotlin.math.ceil(charCount.toDouble() / charsMultUnit).toInt()
        }
    }

    val maxUnits get() = (smsConfig?.get("max_units") as? Number)?.toInt() ?: 0
    val canSend get() = maxUnits == 0 || smsUnits <= maxUnits

    fun fetchConfig() {
        viewModelScope.launch {
            try {
                val response = RetrofitClient.apiService.getPaymentSettings()
                if (response.isSuccessful && response.body()?.status == "success") {
                    smsConfig = response.body()?.data?.get("sms_config") as? Map<String, Any>
                }
            } catch (e: Exception) {}
        }
    }

    fun sendSms(type: String) {
        val cleanRecipients = filteredRecipients.joinToString(",")
        if (senderId.isBlank() || cleanRecipients.isBlank() || message.isBlank()) {
            error = "Please fill all fields with valid data"
            return
        }

        if (!canSend) {
            error = "Message exceeds maximum allowed pages ($maxUnits)"
            return
        }

        loading = true
        error = null
        success = null

        viewModelScope.launch {
            try {
                val response = when (type) {
                    "bulk" -> RetrofitClient.apiService.sendSms(senderId, cleanRecipients, message, "promotional")
                    "global" -> RetrofitClient.apiService.sendSms(senderId, cleanRecipients, message, "global")
                    "voice" -> RetrofitClient.apiService.sendVoice(senderId, cleanRecipients, message)
                    else -> RetrofitClient.apiService.sendSms(senderId, cleanRecipients, message)
                }

                if (response.isSuccessful && response.body()?.status == "success") {
                    success = response.body()?.message ?: "Sent successfully"
                    message = ""
                    recipients = ""
                } else {
                    error = response.body()?.message ?: "Failed to send"
                }
            } catch (e: Exception) {
                error = e.message ?: "An unexpected error occurred"
            } finally {
                loading = false
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SmsScreen(
    type: String,
    onBack: () -> Unit,
    viewModel: SmsViewModel = viewModel()
) {
    LaunchedEffect(Unit) {
        viewModel.fetchConfig()
    }

    val title = when (type) {
        "bulk" -> "Bulk SMS"
        "voice" -> "Voice SMS"
        "global" -> "Global SMS"
        else -> "Send SMS"
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(title, fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(Background)
                .padding(padding)
                .verticalScroll(rememberScrollState())
                .padding(20.dp)
        ) {
            Text(
                text = "Send messages to multiple recipients instantly.",
                fontSize = 14.sp,
                color = Color.Gray,
                modifier = Modifier.padding(bottom = 24.dp)
            )

            FintechInput(
                value = viewModel.senderId,
                onValueChange = { viewModel.senderId = it },
                label = if (type == "voice") "Caller ID" else "Sender ID",
                placeholder = "e.g. PhilmoreSMS"
            )

            Spacer(modifier = Modifier.height(16.dp))

            Column {
                FintechInput(
                    value = viewModel.recipients,
                    onValueChange = { viewModel.recipients = it },
                    label = "Recipients",
                    placeholder = "Separate numbers with comma or newline",
                    isMultiline = true
                )
                Text(
                    text = "Valid Recipients: ${viewModel.recipientCount}",
                    fontSize = 12.sp,
                    color = if (viewModel.recipientCount > 0) com.philmoresms.app.ui.theme.Primary else Color.Gray,
                    modifier = Modifier.padding(top = 4.dp, start = 4.dp)
                )
            }

            Spacer(modifier = Modifier.height(16.dp))

            Column {
                FintechInput(
                    value = viewModel.message,
                    onValueChange = { viewModel.message = it },
                    label = "Message",
                    placeholder = "Type your message here...",
                    isMultiline = true
                )
                
                Row(
                    modifier = Modifier.fillMaxWidth().padding(top = 4.dp, start = 4.dp),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text(
                        text = "Chars: ${viewModel.charCount}",
                        fontSize = 12.sp,
                        color = Color.Gray
                    )
                    Text(
                        text = "Units: ${viewModel.smsUnits} / ${if (viewModel.maxUnits > 0) viewModel.maxUnits else "∞"} pages",
                        fontSize = 12.sp,
                        color = if (viewModel.canSend) com.philmoresms.app.ui.theme.Primary else MaterialTheme.colorScheme.error,
                        fontWeight = FontWeight.Bold
                    )
                }
            }

            if (viewModel.error != null) {
                Text(
                    text = viewModel.error!!,
                    color = MaterialTheme.colorScheme.error,
                    fontSize = 14.sp,
                    modifier = Modifier.padding(vertical = 12.dp)
                )
            }

            if (viewModel.success != null) {
                Text(
                    text = viewModel.success!!,
                    color = Color(0xFF4CAF50),
                    fontSize = 14.sp,
                    modifier = Modifier.padding(vertical = 12.dp)
                )
            }

            Spacer(modifier = Modifier.height(32.dp))

            FintechButton(
                text = if (viewModel.loading) "Sending..." else "Send Now",
                onClick = { viewModel.sendSms(type) },
                enabled = !viewModel.loading && viewModel.canSend && viewModel.message.isNotEmpty()
            )
            
            if (!viewModel.canSend) {
                Text(
                    text = "Message limit exceeded. Please reduce the length.",
                    color = MaterialTheme.colorScheme.error,
                    fontSize = 12.sp,
                    modifier = Modifier.padding(top = 8.dp).fillMaxWidth(),
                    textAlign = androidx.compose.ui.text.style.TextAlign.Center
                )
            }
        }
    }
}
