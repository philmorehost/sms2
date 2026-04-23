package com.philmoresms.app.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable

private val LightColorScheme = lightColorScheme(
    primary = Primary,
    secondary = TextSecondary,
    tertiary = Info,
    background = Background,
    surface = Surface,
    onPrimary = Surface,
    onSecondary = Surface,
    onTertiary = Surface,
    onBackground = TextPrimary,
    onSurface = TextPrimary,
)

@Composable
fun PhilmoreTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = LightColorScheme,
        content = content
    )
}
