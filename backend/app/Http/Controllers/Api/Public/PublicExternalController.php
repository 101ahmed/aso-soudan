<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExternalContactRequestResource;
use App\Http\Resources\ExternalDocumentResource;
use App\Http\Resources\ExternalPartnerResource;
use App\Models\ExternalContactRequest;
use App\Models\ExternalDocument;
use App\Models\ExternalPartner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class PublicExternalController extends Controller
{
    public function partners(): AnonymousResourceCollection
    {
        return ExternalPartnerResource::collection(
            ExternalPartner::query()
                ->public()
                ->orderBy('name_ar')
                ->get()
        );
    }

    public function documents(): AnonymousResourceCollection
    {
        return ExternalDocumentResource::collection(
            ExternalDocument::query()
                ->public()
                ->with('partner')
                ->latest('id')
                ->get()
        );
    }

    public function storeContact(Request $request): JsonResponse
    {
        $key = 'external-contact:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $data = $request->validate([
            'applicant_name' => ['required', 'string', 'max:190'],
            'applicant_phone' => ['nullable', 'string', 'max:50'],
            'applicant_email' => ['nullable', 'email', 'max:190'],
            'applicant_organization' => ['nullable', 'string', 'max:190'],
            'partner_id' => ['nullable', 'integer', Rule::exists('external_partners', 'id')],
            'partner_name' => ['nullable', 'string', 'max:190'],
            'reason' => ['required', 'string', 'max:4000'],
        ]);

        RateLimiter::hit($key, 3600);

        if (! empty($data['partner_id'])) {
            $partner = ExternalPartner::query()->find($data['partner_id']);
            if ($partner && blank($data['partner_name'] ?? null)) {
                $data['partner_name'] = $partner->name_ar ?: $partner->name_fr;
            }
        }

        $item = ExternalContactRequest::query()->create([
            ...$data,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Contact request received.',
            'reference' => $item->reference,
            'data' => (new ExternalContactRequestResource($item))->resolve(),
        ], 201);
    }
}
