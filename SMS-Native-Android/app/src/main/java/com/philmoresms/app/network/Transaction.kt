package com.philmoresms.app.network

import com.google.gson.annotations.SerializedName
import androidx.annotation.Keep

@Keep
data class Transaction(
    @SerializedName("id") val id: String,
    @SerializedName("description") val description: String,
    @SerializedName("amount") val amount: Double,
    @SerializedName("created_at") val created_at: String
)
