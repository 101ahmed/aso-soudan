<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SecretariatMessage */
class SecretariatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'sender_name' => $this->sender_name,
            'sender_email' => $this->sender_email,
            'sender_phone' => $this->sender_phone,
            'subject' => $this->subject,
            'body' => $this->body,
            'status' => $this->status,
            'admin_notes' => $this->admin_notes,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
