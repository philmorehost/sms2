package com.philmoresms.app.ui.screens.profile

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.ui.theme.Background
import com.philmoresms.app.ui.theme.Primary
import com.philmoresms.app.ui.theme.TextPrimary
import com.philmoresms.app.ui.theme.TextSecondary
import kotlinx.coroutines.launch

class ProfileViewModel : ViewModel() {
    var userData by mutableStateOf<Map<String, Any>?>(null)
    var email by mutableStateOf("")
    var phone by mutableStateOf("")
    var password by mutableStateOf("")
    
    var loading by mutableStateOf(false)
    var isUpdating by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var successMessage by mutableStateOf<String?>(null)

    fun fetchProfile() {
        loading = true
        viewModelScope.launch {
            try {
                val response = RetrofitClient.apiService.getUserProfile()
                if (response.isSuccessful && response.body()?.status == "success") {
                    @Suppress("UNCHECKED_CAST")
                    userData = response.body()?.data?.get("user") as? Map<String, Any>
                    email = userData?.get("email")?.toString() ?: ""
                    phone = userData?.get("phone")?.toString() ?: ""
                } else {
                    error = response.body()?.message ?: "Failed to fetch profile"
                }
            } catch (e: Exception) {
                error = e.message
            } finally {
                loading = false
            }
        }
    }

    fun updateProfile() {
        isUpdating = true
        error = null
        successMessage = null
        viewModelScope.launch {
            try {
                val response = RetrofitClient.apiService.updateProfile(email, phone, password)
                if (response.isSuccessful && response.body()?.status == "success") {
                    successMessage = "Profile updated successfully"
                    password = ""
                    fetchProfile()
                } else {
                    error = response.body()?.message ?: "Update failed"
                }
            } catch (e: Exception) {
                error = e.message
            } finally {
                isUpdating = false
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProfileScreen(
    onLogout: () -> Unit,
    viewModel: ProfileViewModel = viewModel()
) {
    LaunchedEffect(Unit) {
        viewModel.fetchProfile()
    }

    val user = viewModel.userData
    val scrollState = androidx.compose.foundation.rememberScrollState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("My Profile", fontWeight = FontWeight.Bold) },
                actions = {
                    IconButton(onClick = onLogout) {
                        Icon(Icons.Default.Logout, contentDescription = "Logout", tint = MaterialTheme.colorScheme.error)
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
                .verticalScroll(scrollState)
                .padding(20.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            // Profile Image Placeholder
            Surface(
                modifier = Modifier.size(80.dp),
                shape = CircleShape,
                color = Primary.copy(alpha = 0.1f)
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Icon(Icons.Default.Person, contentDescription = null, modifier = Modifier.size(50.dp), tint = Primary)
                }
            }

            Text(
                text = user?.get("username")?.toString() ?: "User Profile",
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(top = 16.dp),
                color = TextPrimary
            )
            Text(
                text = user?.get("email")?.toString() ?: "",
                fontSize = 14.sp,
                color = TextSecondary
            )

            Spacer(modifier = Modifier.height(24.dp))

            // Editable Fields
            Column(modifier = Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(16.dp)) {
                com.philmoresms.app.ui.components.FintechInput(
                    value = viewModel.email,
                    onValueChange = { viewModel.email = it },
                    label = "Email Address",
                    placeholder = "Enter your email"
                )
                com.philmoresms.app.ui.components.FintechInput(
                    value = viewModel.phone,
                    onValueChange = { viewModel.phone = it },
                    label = "Phone Number",
                    placeholder = "Enter your phone"
                )
                com.philmoresms.app.ui.components.FintechInput(
                    value = viewModel.password,
                    onValueChange = { viewModel.password = it },
                    label = "New Password (Optional)",
                    placeholder = "Leave blank to keep current"
                )
            }

            if (viewModel.error != null) {
                Text(text = viewModel.error!!, color = MaterialTheme.colorScheme.error, fontSize = 14.sp, modifier = Modifier.padding(top = 12.dp))
            }
            if (viewModel.successMessage != null) {
                Text(text = viewModel.successMessage!!, color = Color(0xFF4CAF50), fontSize = 14.sp, modifier = Modifier.padding(top = 12.dp))
            }

            Spacer(modifier = Modifier.height(24.dp))

            com.philmoresms.app.ui.components.FintechButton(
                text = if (viewModel.isUpdating) "Saving..." else "Update Profile",
                onClick = { viewModel.updateProfile() },
                enabled = !viewModel.isUpdating
            )

            Spacer(modifier = Modifier.height(32.dp))

            // Other Info
            ProfileItem(Icons.Default.Share, "Referral Code", user?.get("referral_code")?.toString() ?: "N/A")
            ProfileItem(Icons.Default.CalendarToday, "Joined Date", user?.get("created_at")?.toString()?.split(" ")?.get(0) ?: "N/A")

            Spacer(modifier = Modifier.height(32.dp))
            Text(text = "App Version 1.0.0", fontSize = 12.sp, color = Color.LightGray)
        }
    }
}

@Composable
fun ProfileItem(icon: ImageVector, label: String, value: String) {
    Surface(
        modifier = Modifier.fillMaxWidth().padding(bottom = 12.dp),
        shape = RoundedCornerShape(16.dp),
        color = Color.White
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(icon, contentDescription = null, tint = Primary, modifier = Modifier.size(20.dp))
            Spacer(modifier = Modifier.width(16.dp))
            Column {
                Text(text = label, fontSize = 11.sp, color = TextSecondary)
                Text(text = value, fontSize = 14.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
            }
        }
    }
}
