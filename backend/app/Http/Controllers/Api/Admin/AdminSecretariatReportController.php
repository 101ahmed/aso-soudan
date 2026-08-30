<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\SecretariatReportBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminSecretariatReportController extends Controller
{
    public function __construct(private SecretariatReportBuilder $builder) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'report.view');
        $year = $this->year($request);
        $departments = $this->visibleDepartments($request);

        return response()->json([
            'year' => $year,
            'generated_at' => now()->toIso8601String(),
            'secretariats' => $this->builder->summaries($departments, $year),
        ]);
    }

    public function show(Request $request, string $code): JsonResponse
    {
        $this->authorizePermission($request, 'report.view');
        $department = $this->department($request, $code);

        return response()->json($this->builder->build($department, $this->year($request)));
    }

    public function pdf(Request $request, string $code): Response
    {
        $this->authorizePermission($request, 'report.view');
        abort_unless(
            $request->user()?->hasPermission('report.export')
            || $request->user()?->hasRole('SUPER_ADMIN')
            || $request->user()?->hasRole('PRESIDENT'),
            403
        );

        $department = $this->department($request, $code);
        $year = $this->year($request);
        $locale = in_array($request->string('locale')->toString(), ['ar', 'fr', 'en'], true)
            ? $request->string('locale')->toString()
            : 'ar';
        $report = $this->builder->build($department, $year);
        $html = view('reports.secretariat', compact('report', 'locale'))->render();
        $filename = 'rapport-'.$department->code.'-'.$year.'.pdf';

        if (class_exists(\Dompdf\Dompdf::class)) {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$filename.'.html"',
        ]);
    }

    private function department(Request $request, string $code): Department
    {
        $department = $request->attributes->get('department')
            ?? Department::query()->where('code', $code)->firstOrFail();
        abort_unless($department->is_active, 404);

        return $department;
    }

    private function visibleDepartments(Request $request)
    {
        $user = $request->user();
        $codes = [
            'general', 'academic', 'social', 'finance', 'media',
            'women-children', 'statistics', 'external-relations', 'sports',
        ];
        $query = Department::query()->active()->whereIn('code', $codes)->orderBy('sort_order');

        if ($user->hasRole('SUPER_ADMIN') || $user->hasRole('PRESIDENT')) {
            return $query->get();
        }

        return $user->departments()
            ->where('departments.is_active', true)
            ->whereIn('departments.code', $codes)
            ->orderBy('departments.sort_order')
            ->get();
    }

    private function year(Request $request): int
    {
        $year = $request->integer('year', (int) now()->year);

        return max(2020, min(2100, $year));
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
