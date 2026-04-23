package com.philmoresms.app.network

import com.google.gson.annotations.SerializedName
import androidx.annotation.Keep

@Keep
data class BaseResponse(
    @SerializedName("status") val status: String,
    @SerializedName("message") val message: String? = null,
    @SerializedName("token") val token: String? = null,
    @SerializedName("stats") val stats: Stats? = null,
    @SerializedName("recent_transactions") val recent_transactions: List<Transaction>? = null
)
