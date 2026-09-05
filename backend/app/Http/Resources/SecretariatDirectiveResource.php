<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SecretariatDirective */
class SecretariatDirectiveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'broadcast_id' => $this->broadcast_id,
            'is_broadcast' => (bool) $this->is_broadcast,
            'title' => $this->title,
            'body' => $this->body,
            'classification' => $this->classification,
            'status' => $this->status,
            'manager_notes' => $this->manager_notes,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'sender_department' => $this->whenLoaded('senderDepartment', fn () => $this->departmentPayload($this->senderDepartment)),
            'recipient_department' => $this->whenLoaded('recipientDepartment', fn () => $this->departmentPayload($this->recipientDepartment)),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'email' => $this->assignee->email,
            ] : null),
            'sender' => $this->whenLoaded('sender', fn () => $this->sender ? [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'email' => $this->sender->email,
            ] : null),
        ];
    }

    private function departmentPayload(?\App\Models\Department $department): ?array
    {
        if (! $department) {
            return null;
        }

        return [
            'id' => $department->id,
            'code' => $department->code,
            'name_ar' => $department->name_ar,
            'name_fr' => $department->name_fr,
            'officer' => [
                'name_ar' => $department->officer_name_ar,
                'name_fr' => $department->officer_name_fr,
                'email' => $department->officer_email,
            ],
        ];
    }
}
