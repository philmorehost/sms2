package com.philmoresms.app.ui.navigation

sealed class Screen(val route: String) {
    object Login : Screen("login")
    object Dashboard : Screen("dashboard")
    object Sms : Screen("sms/{type}") {
        fun createRoute(type: String) = "sms/$type"
    }
    object SenderId : Screen("sender_id")
    object History : Screen("history")
    object Profile : Screen("profile")
    object Payment : Screen("payment")
}
