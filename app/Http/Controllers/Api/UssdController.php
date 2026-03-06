<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AfricasTalkingUssdService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UssdController extends Controller
{
    public function africasTalking(Request $request, AfricasTalkingUssdService $service): Response
    {
        $payload = array_merge($request->all(), [
            'token' => (string) ($request->header('X-USSD-TOKEN') ?? $request->input('token') ?? ''),
        ]);

        $reply = $service->handle($payload);

        return response($reply, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
