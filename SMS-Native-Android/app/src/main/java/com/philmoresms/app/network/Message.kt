package com.philmoresms.app.network

import com.google.gson.annotations.SerializedName
import androidx.annotation.Keep

@Keep
data class Message(
    @SerializedName("id") val id: String,
    @SerializedName("senderID") val senderId: String,
    @SerializedName("recipients") val recipients: String,
    @SerializedName("message") val message: String,
    @SerializedName("units") val units: Int,
    @SerializedName("status") val status: String,
    @SerializedName("created_at") val created_at: String
)
