<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Services\RegisterUserService;
use App\Helpers\ResponseServer;

class RegisterController extends Controller
{
    protected $registerUserService;

    public function __construct(RegisterUserService $registerUserService)
    {
        $this->registerUserService = $registerUserService;
    }

    public function store(RegisterUserRequest $request)
    {
        $user = $this->registerUserService->createUser($request->validated());

        return ResponseServer::registerUserResponse($user);
    }
}