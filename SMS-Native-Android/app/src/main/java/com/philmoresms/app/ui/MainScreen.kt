package com.philmoresms.app.ui

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import androidx.navigation.NavDestination.Companion.hierarchy
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.philmoresms.app.ui.navigation.Screen
import com.philmoresms.app.ui.screens.dashboard.DashboardScreen
import com.philmoresms.app.ui.screens.history.HistoryScreen
import com.philmoresms.app.ui.screens.login.LoginScreen
import com.philmoresms.app.ui.screens.messaging.SmsScreen
import com.philmoresms.app.ui.screens.profile.ProfileScreen
import com.philmoresms.app.ui.screens.senderid.SenderIdScreen
import com.philmoresms.app.ui.screens.payment.PaymentScreen

@Composable
fun MainScreen() {
    val navController = rememberNavController()
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentDestination = navBackStackEntry?.destination

    val showBottomBar = currentDestination?.route != Screen.Login.route

    Scaffold(
        bottomBar = {
            if (showBottomBar) {
                BottomAppBar(
                    containerColor = Color.White,
                    tonalElevation = 8.dp,
                    actions = {
                        IconButton(onClick = {
                            navController.navigate(Screen.Dashboard.route) {
                                popUpTo(navController.graph.findStartDestination().id) { saveState = true }
                                launchSingleTop = true
                                restoreState = true
                            }
                        }) {
                            Icon(Icons.Default.Home, contentDescription = "Home", 
                                tint = if (currentDestination?.route == Screen.Dashboard.route) MaterialTheme.colorScheme.primary else Color.Gray)
                        }
                        IconButton(onClick = {
                            navController.navigate(Screen.History.route) {
                                popUpTo(navController.graph.findStartDestination().id) { saveState = true }
                                launchSingleTop = true
                                restoreState = true
                            }
                        }) {
                            Icon(Icons.Default.History, contentDescription = "History",
                                tint = if (currentDestination?.route == Screen.History.route) MaterialTheme.colorScheme.primary else Color.Gray)
                        }
                        
                        Spacer(Modifier.weight(1f))
                        
                        IconButton(onClick = {
                            navController.navigate(Screen.SenderId.route) {
                                popUpTo(navController.graph.findStartDestination().id) { saveState = true }
                                launchSingleTop = true
                                restoreState = true
                            }
                        }) {
                            Icon(Icons.Default.AssignmentInd, contentDescription = "Sender IDs",
                                tint = if (currentDestination?.route == Screen.SenderId.route) MaterialTheme.colorScheme.primary else Color.Gray)
                        }
                        IconButton(onClick = {
                            navController.navigate(Screen.Profile.route) {
                                popUpTo(navController.graph.findStartDestination().id) { saveState = true }
                                launchSingleTop = true
                                restoreState = true
                            }
                        }) {
                            Icon(Icons.Default.Person, contentDescription = "Profile",
                                tint = if (currentDestination?.route == Screen.Profile.route) MaterialTheme.colorScheme.primary else Color.Gray)
                        }
                    },
                    floatingActionButton = {
                        FloatingActionButton(
                            onClick = { navController.navigate(Screen.Sms.createRoute("bulk")) },
                            shape = CircleShape,
                            containerColor = MaterialTheme.colorScheme.primary,
                            contentColor = Color.White,
                            elevation = FloatingActionButtonDefaults.elevation()
                        ) {
                            Icon(Icons.Default.Send, contentDescription = "Send SMS")
                        }
                    }
                )
            }
        }
    ) { innerPadding ->
        NavHost(
            navController = navController,
            startDestination = Screen.Login.route,
            modifier = Modifier.padding(innerPadding)
        ) {
            composable(Screen.Login.route) {
                LoginScreen(onLoginSuccess = {
                    navController.navigate(Screen.Dashboard.route) {
                        popUpTo(Screen.Login.route) { inclusive = true }
                    }
                })
            }
            composable(Screen.Dashboard.route) {
                DashboardScreen(
                    onNavigateToService = { service ->
                        when (service) {
                            "Bulk SMS" -> navController.navigate(Screen.Sms.createRoute("bulk"))
                            "Voice SMS" -> navController.navigate(Screen.Sms.createRoute("voice"))
                            "Global SMS" -> navController.navigate(Screen.Sms.createRoute("global"))
                            "OTP" -> navController.navigate(Screen.Otp.route)
                            "Sender ID" -> navController.navigate(Screen.SenderId.route)
                            "Payment" -> navController.navigate(Screen.Payment.route)
                            "History" -> navController.navigate(Screen.History.route)
                            else -> {}
                        }
                    },
                    onProfileClick = {
                        navController.navigate(Screen.Profile.route)
                    }
                )
            }
            composable(Screen.Sms.route) { backStackEntry ->
                val type = backStackEntry.arguments?.getString("type") ?: "bulk"
                SmsScreen(
                    type = type,
                    onBack = { navController.popBackStack() }
                )
            }
            composable(Screen.SenderId.route) {
                SenderIdScreen(onBack = { navController.popBackStack() })
            }
            composable(Screen.History.route) {
                HistoryScreen(onBack = { navController.popBackStack() })
            }
            composable(Screen.Profile.route) {
                ProfileScreen(onLogout = {
                    navController.navigate(Screen.Login.route) {
                        popUpTo(0) { inclusive = true }
                    }
                })
            }
            composable(Screen.Otp.route) {
                // Using SmsScreen for OTP for now as it's similar
                SmsScreen(
                    type = "otp",
                    onBack = { navController.popBackStack() }
                )
            }
            composable(Screen.Payment.route) {
                PaymentScreen(onBack = { navController.popBackStack() })
            }
        }
    }
}
