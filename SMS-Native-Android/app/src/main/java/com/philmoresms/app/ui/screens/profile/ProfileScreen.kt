package com.philmoresms.app.ui.screens.profile

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
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
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)

    fun fetchProfile() {
        loading = true
        viewModelScope.launch {
            try {
                val response = RetrofitClient.apiService.getUserProfile()
                if (response.isSuccessful && response.body()?.status == "success") {
                    userData = response.body()?.data?.get("user") as? Map<String, Any>
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
                .padding(20.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            // Profile Image Placeholder
            Surface(
                modifier = Modifier.size(100.dp),
                shape = CircleShape,
                color = Primary.copy(alpha = 0.1f)
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Icon(Icons.Default.Person, contentDescription = null, modifier = Modifier.size(60.dp), tint = Primary)
                }
            }

            Text(
                text = user?.get("username")?.toString() ?: "Loading...",
                fontSize = 20.sp,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(top = 16.dp),
                color = TextPrimary
            )
            Text(
                text = user?.get("email")?.toString() ?: "",
                fontSize = 14.sp,
                color = TextSecondary
            )

            Spacer(modifier = Modifier.height(32.dp))

            // Profile Details
            ProfileItem(Icons.Default.Phone, "Phone Number", user?.get("phone")?.toString() ?: "N/A")
            ProfileItem(Icons.Default.AccountBalanceWallet, "Wallet Balance", "₦${user?.get("balance") ?: 0.0}")
            ProfileItem(Icons.Default.Share, "Referral Code", user?.get("referral_code")?.toString() ?: "N/A")
            ProfileItem(Icons.Default.CalendarToday, "Joined Date", user?.get("created_at")?.toString()?.split(" ")?.get(0) ?: "N/A")

            Spacer(modifier = Modifier.weight(1f))

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
                Text(text = label, fontSize = 12.sp, color = TextSecondary)
                Text(text = value, fontSize = 15.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
            }
        }
    }
}
