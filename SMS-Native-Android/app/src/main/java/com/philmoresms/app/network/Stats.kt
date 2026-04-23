package com.philmoresms.app.network

import com.google.gson.annotations.SerializedName
import androidx.annotation.Keep

@Keep
data class Stats(
    @SerializedName("balance") val balance: Double,
    @SerializedName("username") val username: String? = null
)
