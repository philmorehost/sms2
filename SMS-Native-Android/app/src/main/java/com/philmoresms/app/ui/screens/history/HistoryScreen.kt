package com.philmoresms.app.ui.screens.history

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.network.Transaction
import com.philmoresms.app.ui.screens.dashboard.TransactionItem
import com.philmoresms.app.ui.theme.Background
import kotlinx.coroutines.launch

class HistoryViewModel : ViewModel() {
    var transactions by mutableStateOf<List<Transaction>>(emptyList())
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)

    fun fetchHistory() {
        loading = true
        viewModelScope.launch {
            try {
                val response = RetrofitClient.apiService.getReports()
                if (response.isSuccessful && response.body()?.status == "success") {
                    @Suppress("UNCHECKED_CAST")
                    transactions = response.body()?.data?.get("transactions") as? List<Transaction> ?: emptyList()
                } else {
                    error = response.body()?.message ?: "Failed to fetch history"
                }
            } catch (e: Exception) {
                error = e.message
            } finally {
                loading = false
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HistoryScreen(
    onBack: () -> Unit,
    viewModel: HistoryViewModel = viewModel()
) {
    LaunchedEffect(Unit) {
        viewModel.fetchHistory()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Transaction History", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { padding ->
        if (viewModel.loading && viewModel.transactions.isEmpty()) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = androidx.compose.ui.Alignment.Center) {
                CircularProgressIndicator()
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .background(Background)
                    .padding(padding)
                    .padding(horizontal = 20.dp),
                contentPadding = PaddingValues(vertical = 20.dp)
            ) {
                items(viewModel.transactions) { transaction ->
                    TransactionItem(transaction)
                }
                
                if (viewModel.transactions.isEmpty()) {
                    item {
                        Text(
                            text = "No transactions found.",
                            modifier = Modifier.fillMaxWidth().padding(top = 100.dp),
                            textAlign = androidx.compose.ui.text.style.TextAlign.Center,
                            color = androidx.compose.ui.graphics.Color.Gray
                        )
                    }
                }
            }
        }
    }
}
