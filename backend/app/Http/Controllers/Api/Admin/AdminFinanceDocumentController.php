<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinanceDocumentResource;
use App\Models\FinanceDocument;
use App\Support\StoredFileStore;
use App\Support\UploadRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminFinanceDocumentController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.view');

        return FinanceDocumentResource::collection(FinanceDocument::ensureSlots());
    }

    public function update(Request $request, string $code, string $kind): FinanceDocumentResource
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.update');

        $document = $this->document($kind);
        $data = $this->validated($request);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $stored = StoredFileStore::replace($document->file_path, $file, 'finance_documents');
            $data['file_path'] = $stored['path'];
            $data['original_name'] = $file->getClientOriginalName() ?: $stored['original_name'];
            $data['mime'] = $stored['mime'];
            $data['size'] = $stored['size'];
        }
        unset($data['file']);

        $data['updated_by'] = $request->user()?->id;
        $document->update($data);

        return new FinanceDocumentResource($document->fresh());
    }

    public function publish(Request $request, string $code, string $kind): FinanceDocumentResource|JsonResponse
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.update');

        $document = $this->document($kind);
        if (! $document->hasPublicContent()) {
            return response()->json([
                'message' => 'Add a file or text before publishing.',
            ], 422);
        }

        $document->update([
            'is_published' => true,
            'published_at' => $document->published_at ?: now(),
            'updated_by' => $request->user()?->id,
        ]);

        return new FinanceDocumentResource($document->fresh());
    }

    public function unpublish(Request $request, string $code, string $kind): FinanceDocumentResource
    {
        $this->assertFinance($code);
        $this->authorizePermission($request, 'finance.update');

        $document = $this->document($kind);
        $document->update([
            'is_published' => false,
            'updated_by' => $request->user()?->id,
        ]);

        return new FinanceDocumentResource($document->fresh());
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title_ar' => ['sometimes', 'required', 'string', 'max:190'],
            'title_fr' => ['nullable', 'string', 'max:190'],
            'body_ar' => ['nullable', 'string', 'max:20000'],
            'body_fr' => ['nullable', 'string', 'max:20000'],
            'file' => UploadRules::document(12288, false),
        ]);
    }

    private function document(string $kind): FinanceDocument
    {
        abort_unless(in_array($kind, FinanceDocument::KINDS, true), 404);
        FinanceDocument::ensureSlots();

        return FinanceDocument::query()->where('kind', $kind)->firstOrFail();
    }

    private function assertFinance(string $code): void
    {
        abort_unless($code === 'finance', 404);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
