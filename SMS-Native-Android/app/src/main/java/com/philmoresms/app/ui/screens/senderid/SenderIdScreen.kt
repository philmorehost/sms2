package com.philmoresms.app.ui.screens.senderid

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Info
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
import com.philmoresms.app.ui.theme.Primary
import kotlinx.coroutines.launch

class SenderIdViewModel : ViewModel() {
    var senderId by mutableStateOf("")
    var message by mutableStateOf("")
    var companyName by mutableStateOf("")
    var natureOfBusiness by mutableStateOf("")
    var type by mutableStateOf("promotional")
    
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    fun submitRequest() {
        if (senderId.isBlank() || message.isBlank()) {
            error = "Sender ID and Sample Message are required"
            return
        }

        loading = true
        error = null
        success = null

        viewModelScope.launch {
            try {
                val response = RetrofitClient.apiService.requestSenderId(
                    senderId, message, type, companyName, natureOfBusiness
                )
                if (response.isSuccessful && response.body()?.status == "success") {
                    success = response.body()?.message ?: "Submitted successfully"
                    senderId = ""
                    message = ""
                } else {
                    error = response.body()?.message ?: "Failed to submit"
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
fun SenderIdScreen(
    onBack: () -> Unit,
    viewModel: SenderIdViewModel = viewModel()
) {
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Register Sender ID", fontWeight = FontWeight.Bold) },
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
            Card(
                colors = CardDefaults.cardColors(containerColor = Primary.copy(alpha = 0.1f)),
                modifier = Modifier.padding(bottom = 24.dp)
            ) {
                Row(modifier = Modifier.padding(16.dp)) {
                    Icon(Icons.Default.Info, contentDescription = null, tint = Primary)
                    Spacer(modifier = Modifier.width(12.dp))
                    Column {
                        Text("Example Registration", fontWeight = FontWeight.Bold, color = Primary)
                        Text(
                            "Sender ID: PhilmoreSMS\nMessage: Your OTP is 123456. Do not share.",
                            fontSize = 13.sp,
                            color = Color.DarkGray
                        )
                    }
                }
            }

            FintechInput(
                value = viewModel.senderId,
                onValueChange = { viewModel.senderId = it },
                label = "Requested Sender ID",
                placeholder = "Max 11 characters"
            )

            Spacer(modifier = Modifier.height(16.dp))

            FintechInput(
                value = viewModel.message,
                onValueChange = { viewModel.message = it },
                label = "Sample Message",
                placeholder = "Provide a sample message you intend to send",
                isMultiline = true
            )

            Spacer(modifier = Modifier.height(16.dp))

            Text("Route Type", fontSize = 14.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(bottom = 8.dp))
            Row {
                listOf("promotional", "corporate").forEach { t ->
                    Row(verticalAlignment = androidx.compose.ui.Alignment.CenterVertically, modifier = Modifier.padding(end = 16.dp)) {
                        RadioButton(selected = viewModel.type == t, onClick = { viewModel.type = t })
                        Text(t.replaceFirstChar { it.uppercase() }, modifier = Modifier.padding(start = 4.dp))
                    }
                }
            }

            Spacer(modifier = Modifier.height(16.dp))

            FintechInput(
                value = viewModel.companyName,
                onValueChange = { viewModel.companyName = it },
                label = "Company Name (Optional)",
                placeholder = "Enter your business name"
            )

            if (viewModel.error != null) {
                Text(text = viewModel.error!!, color = MaterialTheme.colorScheme.error, fontSize = 14.sp, modifier = Modifier.padding(vertical = 8.dp))
            }

            if (viewModel.success != null) {
                Text(text = viewModel.success!!, color = Color(0xFF4CAF50), fontSize = 14.sp, modifier = Modifier.padding(vertical = 8.dp))
            }

            Spacer(modifier = Modifier.height(32.dp))

            FintechButton(
                text = if (viewModel.loading) "Submitting..." else "Submit for Review",
                onClick = { viewModel.submitRequest() },
                enabled = !viewModel.loading
            )
        }
    }
}
