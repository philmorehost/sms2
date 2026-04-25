package com.philmoresms.app.network

import com.google.gson.annotations.SerializedName
import androidx.annotation.Keep

@Keep
data class Message(
    @SerializedName("id") val id: String,
    @SerializedName("sender_id") val senderId: String,
    @SerializedName("recipients") val recipients: String,
    @SerializedName("message") val message: String,
    @SerializedName("cost") val cost: Double,
    @SerializedName("status") val status: String,
    @SerializedName("created_at") val created_at: String
)
