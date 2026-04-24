package com.philmoresms.app.ui.screens.dashboard

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.philmoresms.app.ui.theme.*

@Composable
fun DashboardScreen(
    viewModel: DashboardViewModel = viewModel(),
    onNavigateToService: (String) -> Unit = {},
    onProfileClick: () -> Unit = {}
) {
    LaunchedEffect(Unit) {
        viewModel.fetchSummary()
    }

    val scrollState = rememberScrollState()
    val data = viewModel.data

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Background)
            .verticalScroll(scrollState)
            .padding(20.dp)
    ) {
        // Header
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 30.dp, top = 10.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column {
                Text(text = "Good Day, 👋", fontSize = 14.sp, color = TextSecondary)
                Text(
                    text = data?.stats?.username ?: "User",
                    fontSize = 22.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary
                )
            }
            Surface(
                modifier = Modifier.size(46.dp).clickable { onProfileClick() },
                shape = CircleShape,
                color = Primary,
                shadowElevation = 8.dp
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Icon(Icons.Default.Person, contentDescription = "Profile", tint = Color.White)
                }
            }
        }

        // Wallet Card
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .height(160.dp)
                .background(Primary, shape = RoundedCornerShape(24.dp))
                .padding(24.dp)
        ) {
            Column {
                Text(text = "TOTAL BALANCE", fontSize = 12.sp, color = Color.White.copy(alpha = 0.7f), fontWeight = FontWeight.Bold)
                Text(
                    text = "₦${data?.stats?.balance ?: 0.0}",
                    fontSize = 28.sp,
                    fontWeight = FontWeight.ExtraBold,
                    color = Color.White,
                    modifier = Modifier.padding(top = 8.dp)
                )
            }
            
            Button(
                onClick = { /* TODO: Top Up flow */ },
                modifier = Modifier.align(Alignment.BottomEnd),
                colors = ButtonDefaults.buttonColors(containerColor = Color.White),
                shape = RoundedCornerShape(12.dp),
                contentPadding = PaddingValues(horizontal = 15.dp, vertical = 10.dp)
            ) {
                Icon(Icons.Default.Add, contentDescription = null, tint = Primary, modifier = Modifier.size(18.dp))
                Spacer(modifier = Modifier.width(6.dp))
                Text(text = "Top Up", color = Primary, fontWeight = FontWeight.Bold, fontSize = 14.sp)
            }
        }

        Text(
            text = "Quick Services",
            fontSize = 18.sp,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(top = 32.dp, bottom = 20.dp)
        )

        // Services Grid
        Column {
            val services = listOf(
                ServiceData("Bulk SMS", Icons.Default.Sms, SmsColor),
                ServiceData("Global SMS", Icons.Default.Public, GlobalColor),
                ServiceData("Voice SMS", Icons.Default.RecordVoiceOver, VoiceColor),
                ServiceData("OTP", Icons.Default.VpnKey, OtpColor),
                ServiceData("Sender ID", Icons.Default.AssignmentInd, Primary)
            )
            
            services.chunked(2).forEach { row ->
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                    row.forEach { service ->
                        ServiceCard(service, modifier = Modifier.weight(1f), onClick = { onNavigateToService(service.label) })
                    }
                    if (row.size == 1) {
                        Spacer(modifier = Modifier.weight(1f))
                    }
                }
                Spacer(modifier = Modifier.height(16.dp))
            }
        }

        // Recent Transactions
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(top = 32.dp, bottom = 20.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(text = "Recent Transactions", fontSize = 18.sp, fontWeight = FontWeight.Bold)
            Text(
                text = "History", 
                color = Primary, 
                fontWeight = FontWeight.Bold, 
                fontSize = 14.sp,
                modifier = Modifier.clickable { onNavigateToService("History") }
            )
        }

        data?.recent_transactions?.forEach { transaction ->
            TransactionItem(transaction)
        }
    }
}

data class ServiceData(val label: String, val icon: ImageVector, val color: Color)

@Composable
fun ServiceCard(service: ServiceData, modifier: Modifier = Modifier, onClick: () -> Unit = {}) {
    Surface(
        modifier = modifier.height(100.dp).clickable { onClick() },
        shape = RoundedCornerShape(16.dp),
        color = Color.White,
        shadowElevation = 2.dp
    ) {
        Column(
            modifier = Modifier.fillMaxSize(),
            verticalArrangement = Arrangement.Center,
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Box(
                modifier = Modifier
                    .size(40.dp)
                    .background(service.color.copy(alpha = 0.1f), CircleShape),
                contentAlignment = Alignment.Center
            ) {
                Icon(service.icon, contentDescription = null, tint = service.color)
            }
            Text(
                text = service.label,
                fontSize = 12.sp,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(top = 8.dp),
                color = TextPrimary
            )
        }
    }
}

@Composable
fun TransactionItem(transaction: com.philmoresms.app.network.Transaction) {
    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 12.dp),
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
                Icon(
                    if (transaction.amount > 0) Icons.Default.ArrowUpward else Icons.Default.Sms,
                    contentDescription = null,
                    tint = if (transaction.amount > 0) Success else SmsColor
                )
            }
            Column(modifier = Modifier.weight(1f).padding(horizontal = 16.dp)) {
                Text(text = transaction.description, fontSize = 15.sp, fontWeight = FontWeight.Bold, maxLines = 1)
                Text(text = transaction.created_at, fontSize = 12.sp, color = TextSecondary)
            }
            Text(
                text = "${if (transaction.amount > 0) "+" else ""}₦${transaction.amount}",
                fontSize = 16.sp,
                fontWeight = FontWeight.Bold,
                color = if (transaction.amount > 0) Success else Danger
            )
        }
    }
}
