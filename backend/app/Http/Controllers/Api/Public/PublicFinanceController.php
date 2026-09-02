<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinanceDocumentResource;
use App\Models\FinanceDocument;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicFinanceController extends Controller
{
    public function documents(): AnonymousResourceCollection
    {
        FinanceDocument::ensureSlots();

        return FinanceDocumentResource::collection(
            FinanceDocument::query()
                ->published()
                ->orderByRaw("CASE kind WHEN '".FinanceDocument::KIND_GENERAL_REPORT."' THEN 1 ELSE 2 END")
                ->get()
        );
    }

    public function document(string $kind): FinanceDocumentResource
    {
        abort_unless(in_array($kind, FinanceDocument::KINDS, true), 404);

        $document = FinanceDocument::query()
            ->published()
            ->where('kind', $kind)
            ->first();

        abort_if($document === null, 404);

        return new FinanceDocumentResource($document);
    }
}
