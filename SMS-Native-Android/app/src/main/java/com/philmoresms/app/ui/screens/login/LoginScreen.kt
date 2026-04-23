package com.philmoresms.app.ui.screens.login

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.philmoresms.app.R
import com.philmoresms.app.ui.components.FintechButton
import com.philmoresms.app.ui.components.FintechInput
import com.philmoresms.app.ui.theme.Primary
import com.philmoresms.app.ui.theme.TextPrimary
import com.philmoresms.app.ui.theme.TextSecondary

@Composable
fun LoginScreen(
    onLoginSuccess: () -> Unit,
    viewModel: LoginViewModel = viewModel()
) {
    val scrollState = rememberScrollState()

    if (viewModel.loginSuccess) {
        LaunchedEffect(Unit) {
            onLoginSuccess()
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Color(0xFFF8FAFC))
            .verticalScroll(scrollState)
    ) {
        // Header
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .height(280.dp)
                .background(
                    Primary,
                    shape = RoundedCornerShape(bottomStart = 40.dp, bottomEnd = 40.dp)
                ),
            contentAlignment = Alignment.Center
        ) {
            // Decorative Circle
            Box(
                modifier = Modifier
                    .offset(x = 120.dp, y = (-100).dp)
                    .size(180.dp)
                    .background(Color.White.copy(alpha = 0.1f), shape = CircleShape)
            )

            // Logo Container
            Surface(
                modifier = Modifier.size(140.dp),
                shape = CircleShape,
                color = Color.White,
                shadowElevation = 10.dp
            ) {
                Image(
                    painter = painterResource(id = R.drawable.logo),
                    contentDescription = "Logo",
                    modifier = Modifier
                        .padding(20.dp)
                        .fillMaxSize(),
                    contentScale = ContentScale.Fit
                )
            }
        }

        // Content
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 30.dp)
                .offset(y = (-20).dp)
                .background(Color(0xFFF8FAFC), shape = RoundedCornerShape(topStart = 30.dp, topEnd = 30.dp))
                .padding(top = 20.dp)
        ) {
            Text(
                text = "Welcome Back",
                fontSize = 28.sp,
                fontWeight = FontWeight.ExtraBold,
                color = TextPrimary
            )
            Text(
                text = "Sign in to your account to continue broadcasting.",
                fontSize = 14.sp,
                color = TextSecondary,
                modifier = Modifier.padding(top = 8.dp, bottom = 32.dp),
                lineHeight = 20.sp
            )

            FintechInput(
                label = "Username or Email",
                value = viewModel.login,
                onValueChange = { viewModel.login = it },
                placeholder = "Enter your login"
            )

            FintechInput(
                label = "Password",
                value = viewModel.password,
                onValueChange = { viewModel.password = it },
                placeholder = "Enter your password",
                secureTextEntry = true
            )

            if (viewModel.error != null) {
                Text(
                    text = viewModel.error!!,
                    color = Color.Red,
                    fontSize = 14.sp,
                    modifier = Modifier.padding(bottom = 16.dp)
                )
            }

            FintechButton(
                title = if (viewModel.loading) "Verifying..." else "Login",
                onPress = { viewModel.onLoginClick() },
                modifier = Modifier.padding(top = 10.dp)
            )

            // Register Link
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 32.dp, bottom = 40.dp),
                horizontalArrangement = Arrangement.Center
            ) {
                Text(text = "New to Philmore? ", color = TextSecondary, fontSize = 14.sp)
                Text(
                    text = "Create Account",
                    color = Primary,
                    fontWeight = FontWeight.Bold,
                    fontSize = 14.sp
                )
            }
        }
    }
}
