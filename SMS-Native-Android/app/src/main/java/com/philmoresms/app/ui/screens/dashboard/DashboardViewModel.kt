package com.philmoresms.app.ui.screens.dashboard

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.philmoresms.app.network.BaseResponse
import com.philmoresms.app.network.RetrofitClient
import kotlinx.coroutines.launch

class DashboardViewModel : ViewModel() {
    var data by mutableStateOf<BaseResponse?>(null)
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)

    fun fetchSummary() {
        loading = true
        error = null
        viewModelScope.launch {
            try {
                val response = RetrofitClient.apiService.getSummary()
                if (response.isSuccessful) {
                    data = response.body()
                } else {
                    error = "Failed to fetch dashboard data"
                }
            } catch (e: Exception) {
                error = e.message ?: "An unexpected error occurred"
            } finally {
                loading = false
            }
        }
    }
}
