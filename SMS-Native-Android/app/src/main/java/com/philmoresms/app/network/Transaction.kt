package com.philmoresms.app.network

import com.google.gson.annotations.SerializedName
import androidx.annotation.Keep

@Keep
data class Transaction(
    @SerializedName("id") val id: String,
    @SerializedName("reference") val reference: String?,
    @SerializedName("type") val type: String?,
    @SerializedName("amount") val amount: Double,
    @SerializedName("status") val status: String?,
    @SerializedName("description") val description: String,
    @SerializedName("created_at") val created_at: String
)
