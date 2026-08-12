<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class PublicContactController extends Controller
{
    private const RECIPIENT = 'hima171221@gmail.com';

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $key = 'contact:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Too many messages. Please try again later.',
            ], 429);
        }
        RateLimiter::hit($key, 3600);

        $to = config('mail.contact_to') ?: self::RECIPIENT;

        $record = ContactMessage::query()->create([
            ...$data,
            'ip' => $request->ip(),
            'status' => 'new',
        ]);

        $mailed = false;
        try {
            Mail::to($to)->send(new ContactMessageMail($data));
            $record->update([
                'mailed_at' => now(),
                'status' => 'mailed',
            ]);
            $mailed = true;
        } catch (Throwable $e) {
            report($e);
            $record->update(['status' => 'mail_failed']);
        }

        // Message toujours enregistré ; email envoyé si le mailer fonctionne
        return response()->json([
            'message' => 'Message received.',
            'contact_email' => $to,
            'mailed' => $mailed,
            'id' => $record->id,
        ], 201);
    }

    public function info(): JsonResponse
    {
        return response()->json([
            'email' => config('mail.contact_to') ?: self::RECIPIENT,
        ]);
    }
}
