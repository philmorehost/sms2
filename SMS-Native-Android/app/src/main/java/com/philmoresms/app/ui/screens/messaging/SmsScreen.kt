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

    fun sendSms(type: String) {
        if (senderId.isBlank() || recipients.isBlank() || message.isBlank()) {
            error = "Please fill all fields"
            return
        }

        loading = true
        error = null
        success = null

        viewModelScope.launch {
            try {
                val response = when (type) {
                    "bulk" -> RetrofitClient.apiService.sendSms(senderId, recipients, message, "promotional")
                    "global" -> RetrofitClient.apiService.sendSms(senderId, recipients, message, "global")
                    "voice" -> RetrofitClient.apiService.sendVoice(senderId, recipients, message)
                    else -> RetrofitClient.apiService.sendSms(senderId, recipients, message)
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

            FintechInput(
                value = viewModel.recipients,
                onValueChange = { viewModel.recipients = it },
                label = "Recipients",
                placeholder = "Separate numbers with comma or newline",
                isMultiline = true
            )

            Spacer(modifier = Modifier.height(16.dp))

            FintechInput(
                value = viewModel.message,
                onValueChange = { viewModel.message = it },
                label = "Message",
                placeholder = "Type your message here...",
                isMultiline = true
            )

            if (viewModel.error != null) {
                Text(
                    text = viewModel.error!!,
                    color = MaterialTheme.colorScheme.error,
                    fontSize = 14.sp,
                    modifier = Modifier.padding(vertical = 8.dp)
                )
            }

            if (viewModel.success != null) {
                Text(
                    text = viewModel.success!!,
                    color = Color(0xFF4CAF50),
                    fontSize = 14.sp,
                    modifier = Modifier.padding(vertical = 8.dp)
                )
            }

            Spacer(modifier = Modifier.height(32.dp))

            FintechButton(
                text = if (viewModel.loading) "Sending..." else "Send Now",
                onClick = { viewModel.sendSms(type) },
                enabled = !viewModel.loading
            )
        }
    }
}
