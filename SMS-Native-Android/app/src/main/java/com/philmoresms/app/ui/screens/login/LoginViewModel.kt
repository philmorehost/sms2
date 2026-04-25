package com.philmoresms.app.ui.screens.login

import android.app.Application
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.utils.PrefsHelper
import kotlinx.coroutines.launch

class LoginViewModel(application: Application) : AndroidViewModel(application) {
    private val prefs = PrefsHelper(application)
    
    var login by mutableStateOf("")
    var password by mutableStateOf("")
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var loginSuccess by mutableStateOf(false)
    
    var showBiometricOption by mutableStateOf(false)

    init {
        // Check if biometric is enabled and we have saved credentials
        if (prefs.biometricEnabled && prefs.getSavedLogin() != null) {
            showBiometricOption = true
        }
    }

    fun onLoginClick() {
        if (login.isBlank() || password.isBlank()) {
            error = "Please fill all fields"
            return
        }

        performLogin(login, password)
    }

    fun onBiometricSuccess() {
        val savedLogin = prefs.getSavedLogin()
        val savedPass = prefs.getSavedPassword()
        if (savedLogin != null && savedPass != null) {
            performLogin(savedLogin, savedPass)
        }
    }

    private fun performLogin(l: String, p: String) {
        loading = true
        error = null

        viewModelScope.launch {
            try {
                val response = RetrofitClient.apiService.login(l, p)
                if (response.isSuccessful && response.body()?.status == "success") {
                    response.body()?.token?.let {
                        RetrofitClient.setToken(it)
                    }
                    // Save credentials for future biometric login if not already saved
                    if (!prefs.biometricEnabled) {
                        prefs.saveCredentials(l, p)
                        prefs.biometricEnabled = true
                    }
                    loginSuccess = true
                } else {
                    error = response.body()?.message ?: "Login failed (Server Error)"
                }
            } catch (e: Exception) {
                val msg = e.message ?: ""
                error = if (msg.contains("JsonReader") || msg.contains("malformed")) {
                    "Server Error: The server returned an invalid response. Please check your Database configuration in app/config.php."
                } else {
                    msg.ifBlank { "Network Error: Please check your internet connection" }
                }
            } finally {
                loading = false
            }
        }
    }
}
