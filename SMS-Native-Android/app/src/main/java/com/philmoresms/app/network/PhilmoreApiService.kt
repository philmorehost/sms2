package com.philmoresms.app.network

import retrofit2.Response
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.http.POST
import androidx.annotation.Keep

@Keep
interface PhilmoreApiService {

    @FormUrlEncoded
    @POST("auth.php?action=login")
    suspend fun login(
        @Field("login") login: String,
        @Field("password") password: String
    ): Response<BaseResponse>

    @POST("dashboard.php")
    suspend fun getSummary(): Response<BaseResponse>

    @FormUrlEncoded
    @POST("auth.php?action=register")
    suspend fun register(
        @Field("username") username: String,
        @Field("email") email: String,
        @Field("password") password: String,
        @Field("phone") phone: String
    ): Response<BaseResponse>

    @FormUrlEncoded
    @POST("auth.php?action=forgot_password")
    suspend fun forgotPassword(
        @Field("email") email: String
    ): Response<BaseResponse>
}
