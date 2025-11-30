<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="FirmWizard API",
 *     version="1.0.0",
 *     description="AI-powered marketing platform for law firms",
 *     @OA\Contact(
 *         email="support@firmwizard.com"
 *     )
 * )
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Local Development Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="ApiKeyAuth",
 *     type="apiKey",
 *     in="header",
 *     name="X-API-Key",
 *     description="N8N API Key for authentication"
 * )
 */
abstract class Controller
{
    //
}
