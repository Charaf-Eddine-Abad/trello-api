<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Trello Task Management API",
 *     version="1.0.0",
 *     description="API documentation for the Trello-like Task Management System",
 *     @OA\Contact(
 *         email="charaf@gmail.com",
 *         name="Charaf"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Sanctum token"
 * )
 * 
 * @OA\Tag(
 *     name="Projects",
 *     description="Project management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 */
abstract class Controller
{
    //
}
