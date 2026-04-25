package com.philmoresms.app.ui.screens.history

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Sms
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.philmoresms.app.network.Message
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.ui.theme.Background
import com.philmoresms.app.ui.theme.Primary
import com.philmoresms.app.ui.theme.SmsColor
import com.philmoresms.app.ui.theme.TextPrimary
import com.philmoresms.app.ui.theme.TextSecondary
import kotlinx.coroutines.launch
import androidx.compose.ui.input.nestedscroll.nestedScroll
import androidx.compose.material3.pulltorefresh.rememberPullToRefreshState
import androidx.compose.material3.pulltorefresh.PullToRefreshContainer

class MessageHistoryViewModel : ViewModel() {
    var messages by mutableStateOf<List<Message>>(emptyList())
    var loading by mutableStateOf(false)
    var isRefreshing = mutableStateOf(false)
    var error by mutableStateOf<String?>(null)

    fun fetchMessages() {
        if (!isRefreshing.value) loading = true
        viewModelScope.launch {
            try {
                val response = RetrofitClient.apiService.getMessageReports()
                if (response.isSuccessful && response.body()?.status == "success") {
                    messages = response.body()?.data?.get("messages") ?: emptyList()
                } else {
                    error = response.body()?.message ?: "Failed to fetch SMS history"
                }
            } catch (e: Exception) {
                error = e.message
            } finally {
                loading = false
                isRefreshing.value = false
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MessageHistoryScreen(
    onBack: () -> Unit,
    viewModel: MessageHistoryViewModel = viewModel()
) {
    val isRefreshing by viewModel.isRefreshing
    val pullRefreshState = rememberPullToRefreshState()

    if (pullRefreshState.isRefreshing) {
        LaunchedEffect(Unit) {
            viewModel.isRefreshing.value = true
            viewModel.fetchMessages()
        }
    }

    LaunchedEffect(isRefreshing) {
        if (!isRefreshing) {
            pullRefreshState.endRefresh()
        }
    }

    LaunchedEffect(Unit) {
        viewModel.fetchMessages()
    }

    var selectedMessage by remember { mutableStateOf<Message?>(null) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("SMS Sent History", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(Background)
                .padding(padding)
                .nestedScroll(pullRefreshState.nestedScrollConnection)
        ) {
            if (viewModel.loading && viewModel.messages.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize().padding(horizontal = 20.dp),
                    contentPadding = PaddingValues(vertical = 20.dp)
                ) {
                    items(viewModel.messages) { message ->
                        MessageItem(message) {
                            selectedMessage = message
                        }
                    }

                    if (viewModel.messages.isEmpty()) {
                        item {
                            Text(
                                text = "No sent messages found.",
                                modifier = Modifier.fillMaxWidth().padding(top = 100.dp),
                                textAlign = androidx.compose.ui.text.style.TextAlign.Center,
                                color = Color.Gray
                            )
                        }
                    }
                }
            }

            PullToRefreshContainer(
                state = pullRefreshState,
                modifier = Modifier.align(Alignment.TopCenter)
            )
        }

        if (selectedMessage != null) {
            AlertDialog(
                onDismissRequest = { selectedMessage = null },
                title = { Text("Message Details", fontWeight = FontWeight.Bold) },
                text = {
                    Column(modifier = Modifier.fillMaxWidth()) {
                        DetailRow("Sender ID", selectedMessage!!.senderId)
                        DetailRow("Recipients", selectedMessage!!.recipients)
                        DetailRow("Cost", "₦${selectedMessage!!.cost}")
                        DetailRow("Status", selectedMessage!!.status)
                        DetailRow("Date", selectedMessage!!.created_at)
                        Spacer(modifier = Modifier.height(8.dp))
                        Text("Message:", fontWeight = FontWeight.Bold, color = Color.Gray, fontSize = 12.sp)
                        Text(
                            text = selectedMessage!!.message,
                            color = TextPrimary,
                            fontSize = 14.sp,
                            modifier = Modifier.padding(top = 4.dp)
                        )
                    }
                },
                confirmButton = {
                    TextButton(onClick = { selectedMessage = null }) {
                        Text("Close")
                    }
                }
            )
        }
    }
}

@Composable
fun MessageItem(message: Message, onClick: () -> Unit) {
    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 12.dp)
            .clickable { onClick() },
        shape = RoundedCornerShape(16.dp),
        color = Color.White,
        shadowElevation = 1.dp
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .background(Background, RoundedCornerShape(12.dp)),
                contentAlignment = Alignment.Center
            ) {
                Icon(Icons.Default.Sms, contentDescription = null, tint = SmsColor)
            }
            Column(modifier = Modifier.weight(1f).padding(horizontal = 16.dp)) {
                Text(text = "To: ${message.recipients.take(15)}...", fontSize = 15.sp, fontWeight = FontWeight.Bold, maxLines = 1)
                Text(text = message.created_at, fontSize = 12.sp, color = TextSecondary)
            }
            Text(
                text = "₦${message.cost}",
                fontSize = 14.sp,
                fontWeight = FontWeight.Bold,
                color = Primary
            )
        }
    }
}
