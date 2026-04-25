package com.philmoresms.app.network

import retrofit2.Response
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.http.GET
import retrofit2.http.POST
import androidx.annotation.Keep

@Keep
interface PhilmoreApiService {

    @FormUrlEncoded
    @POST("auth.php?action=login")
    suspend fun login(
        @Field("login") login: String,
        @Field("password") password: String
    ): Response<BaseResponse<Unit>>

    @FormUrlEncoded
    @POST("messaging.php?action=send_sms")
    suspend fun sendSms(
        @Field("senderID") senderId: String,
        @Field("recipients") recipients: String,
        @Field("message") message: String,
        @Field("route") route: String = "promotional"
    ): Response<BaseResponse<Map<String, Any>>>

    @FormUrlEncoded
    @POST("messaging.php?action=send_voice")
    suspend fun sendVoice(
        @Field("callerID") callerId: String,
        @Field("recipients") recipients: String,
        @Field("message") message: String
    ): Response<BaseResponse<Map<String, Any>>>

    @GET("sender-ids.php?action=list")
    suspend fun getSenderIds(): Response<BaseResponse<Map<String, List<Map<String, Any>>>>>

    @FormUrlEncoded
    @POST("sender-ids.php?action=request")
    suspend fun requestSenderId(
        @Field("senderID") senderId: String,
        @Field("message") sampleMessage: String,
        @Field("type") type: String,
        @Field("company_name") companyName: String = "",
        @Field("nature_of_business") natureOfBusiness: String = ""
    ): Response<BaseResponse<Map<String, Any>>>

    @GET("user.php")
    suspend fun getUserProfile(): Response<BaseResponse<Map<String, Any>>>

    @GET("reports.php")
    suspend fun getReports(
        @retrofit2.http.Query("action") action: String = "transactions",
        @retrofit2.http.Query("type") type: String = "all"
    ): Response<BaseResponse<Map<String, Any>>>

    @FormUrlEncoded
    @POST("user.php?action=update")
    suspend fun updateProfile(
        @Field("email") email: String,
        @Field("phone") phone: String,
        @Field("password") password: String = ""
    ): Response<BaseResponse<Unit>>

    @GET("payment.php?action=settings")
    suspend fun getPaymentSettings(): Response<BaseResponse<Map<String, Any>>>

    @FormUrlEncoded
    @POST("payment.php?action=submit_manual")
    suspend fun submitManualPayment(
        @Field("amount") amount: Double,
        @Field("reference") reference: String,
        @Field("date") date: String
    ): Response<BaseResponse<Map<String, Any>>>

    @FormUrlEncoded
    @POST("payment.php?action=init_paystack")
    suspend fun initPaystack(
        @Field("amount") amount: Double
    ): Response<BaseResponse<Map<String, String>>>

    @POST("dashboard.php")
    suspend fun getSummary(): Response<BaseResponse<Unit>>

    @FormUrlEncoded
    @POST("auth.php?action=register")
    suspend fun register(
        @Field("username") username: String,
        @Field("email") email: String,
        @Field("password") password: String,
        @Field("phone") phone: String
    ): Response<BaseResponse<Unit>>

    @FormUrlEncoded
    @POST("auth.php?action=forgot_password")
    suspend fun forgotPassword(
        @Field("email") email: String
    ): Response<BaseResponse<Unit>>
}
