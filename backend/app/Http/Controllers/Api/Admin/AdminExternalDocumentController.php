<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExternalDocumentResource;
use App\Models\ExternalDocument;
use App\Support\StoredFileStore;
use App\Support\UploadRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminExternalDocumentController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'partner.view');

        return ExternalDocumentResource::collection(
            ExternalDocument::query()
                ->with('partner')
                ->filtered($request->only(['search', 'category', 'partner_id']))
                ->latest('id')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'partner.create');

        $data = $this->validated($request, true);
        $file = $request->file('file');
        $stored = StoredFileStore::store($file, 'external_documents');
        $data['file_path'] = $stored['path'];
        $data['original_name'] = $file->getClientOriginalName() ?: $stored['original_name'];
        $data['mime'] = $stored['mime'];
        $data['size'] = $stored['size'];
        $data['uploaded_by'] = $request->user()?->id;
        unset($data['file']);

        $item = ExternalDocument::query()->create($data);

        return (new ExternalDocumentResource($item->load('partner')))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $code, ExternalDocument $externalDocument): ExternalDocumentResource
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'partner.update');

        $data = $this->validated($request, false);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $stored = StoredFileStore::replace($externalDocument->file_path, $file, 'external_documents');
            $data['file_path'] = $stored['path'];
            $data['original_name'] = $file->getClientOriginalName() ?: $stored['original_name'];
            $data['mime'] = $stored['mime'];
            $data['size'] = $stored['size'];
        }
        unset($data['file']);
        $externalDocument->update($data);

        return new ExternalDocumentResource($externalDocument->fresh()->load('partner'));
    }

    public function destroy(Request $request, string $code, ExternalDocument $externalDocument): JsonResponse
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'partner.delete');
        StoredFileStore::forget($externalDocument->file_path);
        $externalDocument->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'title_ar' => [$creating ? 'required' : 'sometimes', 'string', 'max:190'],
            'title_fr' => ['nullable', 'string', 'max:190'],
            'category' => ['nullable', Rule::in(ExternalDocument::CATEGORIES)],
            'partner_id' => ['nullable', 'integer', Rule::exists('external_partners', 'id')],
            'is_public' => ['nullable', 'boolean'],
            'file' => UploadRules::document(12288, $creating),
        ]);

        if (array_key_exists('is_public', $data)) {
            $data['is_public'] = filter_var($data['is_public'], FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }

    private function assertExternal(string $code): void
    {
        abort_unless($code === 'external-relations', 404);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
