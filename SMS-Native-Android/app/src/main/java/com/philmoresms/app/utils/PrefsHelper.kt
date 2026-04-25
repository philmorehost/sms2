package com.philmoresms.app.utils

import android.content.Context
import android.content.SharedPreferences

class PrefsHelper(context: Context) {
    private val prefs: SharedPreferences = context.getSharedPreferences("philmoresms_prefs", Context.MODE_PRIVATE)

    companion object {
        private const val KEY_BIOMETRIC_ENABLED = "biometric_enabled"
        private const val KEY_SAVED_LOGIN = "saved_login"
        private const val KEY_SAVED_PASSWORD = "saved_password"
    }

    var biometricEnabled: Boolean
        get() = prefs.getBoolean(KEY_BIOMETRIC_ENABLED, false)
        set(value) = prefs.edit().putBoolean(KEY_BIOMETRIC_ENABLED, value).apply()

    fun saveCredentials(login: String, pass: String) {
        prefs.edit()
            .putString(KEY_SAVED_LOGIN, login)
            .putString(KEY_SAVED_PASSWORD, pass)
            .apply()
    }

    fun getSavedLogin(): String? = prefs.getString(KEY_SAVED_LOGIN, null)
    fun getSavedPassword(): String? = prefs.getString(KEY_SAVED_PASSWORD, null)

    fun clearCredentials() {
        prefs.edit().remove(KEY_SAVED_LOGIN).remove(KEY_SAVED_PASSWORD).apply()
    }
}
