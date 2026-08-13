<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\EducationStage;
use App\Models\Level;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\StudentAttendance;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->removeFrenchLanguageSubject();
        $this->forgetSeededDemoAttendance();

        $year = AcademicYear::query()->updateOrCreate(
            ['name' => '2026/2027'],
            [
                'starts_on' => '2026-09-01',
                'ends_on' => '2027-06-30',
                'status' => 'active',
                'is_current' => true,
            ]
        );

        AcademicYear::query()->where('id', '!=', $year->id)->update(['is_current' => false]);

        $stage = EducationStage::query()->updateOrCreate(
            ['code' => 'primary'],
            [
                'name_ar' => 'المرحلة الابتدائية',
                'name_fr' => 'Primaire',
                'sort_order' => 10,
                'is_active' => true,
            ]
        );

        $level = Level::query()->updateOrCreate(
            ['education_stage_id' => $stage->id, 'code' => 'CE2'],
            [
                'name_ar' => 'المستوى CE2',
                'name_fr' => 'Niveau CE2',
                'sort_order' => 20,
                'is_active' => true,
            ]
        );

        $subjects = [
            ['code' => 'AR', 'name_ar' => 'اللغة العربية', 'name_fr' => 'Langue arabe'],
            ['code' => 'QURAN', 'name_ar' => 'القرآن الكريم', 'name_fr' => 'Coran'],
            ['code' => 'MATH', 'name_ar' => 'الرياضيات', 'name_fr' => 'Mathématiques'],
        ];

        $subjectModels = [];
        foreach ($subjects as $item) {
            $subjectModels[$item['code']] = Subject::query()->updateOrCreate(
                ['code' => $item['code']],
                [
                    'name_ar' => $item['name_ar'],
                    'name_fr' => $item['name_fr'],
                    'is_active' => true,
                ]
            );
            $level->subjects()->syncWithoutDetaching([$subjectModels[$item['code']]->id]);
        }

        $studentsData = [
            ['أحمد', 'محمد'],
            ['سارة', 'علي'],
            ['يوسف', 'إبراهيم'],
            ['مريم', 'حسن'],
            ['عمر', 'خالد'],
            ['نور', 'عثمان'],
        ];

        $teacherRole = Role::query()->where('code', 'TEACHER')->first();
        $teachersBySubject = [];
        $teachersSeed = [
            ['first' => 'إبراهيم', 'last' => 'سليمان', 'email' => 'ibrahim.teacher@acs-rennes.fr', 'subjects' => ['AR']],
            ['first' => 'فاطمة', 'last' => 'أحمد', 'email' => 'fatima.teacher@acs-rennes.fr', 'subjects' => ['QURAN']],
            ['first' => 'عمر', 'last' => 'حسن', 'email' => 'omar.teacher@acs-rennes.fr', 'subjects' => ['MATH']],
        ];

        foreach ($teachersSeed as $item) {
            $user = User::query()->updateOrCreate(
                ['email' => $item['email']],
                [
                    'first_name' => $item['first'],
                    'last_name' => $item['last'],
                    'name' => $item['first'].' '.$item['last'],
                    'phone' => null,
                    'locale' => 'ar',
                    'status' => 'active',
                    'password' => 'Password123!',
                    'email_verified_at' => now(),
                ]
            );
            if ($teacherRole) {
                $user->roles()->syncWithoutDetaching([$teacherRole->id]);
            }

            $teacher = Teacher::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $item['first'],
                    'last_name' => $item['last'],
                    'status' => 'active',
                    'hired_on' => '2026-09-01',
                ]
            );
            $subjectIds = collect($item['subjects'])
                ->map(fn ($code) => $subjectModels[$code]->id ?? null)
                ->filter()
                ->all();
            $teacher->subjects()->sync($subjectIds);

            foreach ($item['subjects'] as $code) {
                $teachersBySubject[$code] = $teacher;
            }
        }

        $students = [];
        foreach ($studentsData as [$first, $last]) {
            $students[] = Student::query()->updateOrCreate(
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'academic_year_id' => $year->id,
                ],
                [
                    'education_stage_id' => $stage->id,
                    'level_id' => $level->id,
                    'status' => 'active',
                    'registered_at' => now(),
                ]
            );
        }

        foreach ($subjectModels as $code => $subject) {
            $class = ClassGroup::query()->updateOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'subject_id' => $subject->id,
                    'name' => $subject->name_fr.' — CE2',
                ],
                [
                    'level_id' => $level->id,
                    'teacher_id' => $teachersBySubject[$code]?->id,
                    'code' => $code.'-CE2',
                    'capacity' => 20,
                    'status' => 'active',
                ]
            );

            foreach ($students as $student) {
                DB::table('class_students')->updateOrInsert(
                    [
                        'class_group_id' => $class->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'status' => 'active',
                        'enrolled_on' => now()->toDateString(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    private function removeFrenchLanguageSubject(): void
    {
        $subjects = Subject::query()
            ->withTrashed()
            ->where(function ($query) {
                $query->where('code', 'FR')
                    ->orWhere('name_ar', 'اللغة الفرنسية');
            })
            ->get();

        foreach ($subjects as $subject) {
            $classIds = ClassGroup::query()
                ->withTrashed()
                ->where('subject_id', $subject->id)
                ->pluck('id');

            $sessionIds = AcademicSession::query()
                ->withTrashed()
                ->whereIn('class_group_id', $classIds)
                ->pluck('id');

            StudentAttendance::query()->whereIn('academic_session_id', $sessionIds)->delete();
            DB::table('teacher_attendances')->whereIn('academic_session_id', $sessionIds)->delete();
            AcademicSession::query()->withTrashed()->whereIn('id', $sessionIds)->forceDelete();
            DB::table('class_students')->whereIn('class_group_id', $classIds)->delete();
            ClassGroup::query()->withTrashed()->whereIn('id', $classIds)->forceDelete();
            DB::table('teacher_subject')->where('subject_id', $subject->id)->delete();
            DB::table('student_subject')->where('subject_id', $subject->id)->delete();
            DB::table('level_subject')->where('subject_id', $subject->id)->delete();
            $subject->forceDelete();
        }
    }

    private function forgetSeededDemoAttendance(): void
    {
        $sessionIds = AcademicSession::query()
            ->where('room', 'Salle A')
            ->where('starts_at', '10:00:00')
            ->pluck('id');

        if ($sessionIds->isEmpty()) {
            return;
        }

        StudentAttendance::query()->whereIn('academic_session_id', $sessionIds)->delete();
        DB::table('teacher_attendances')->whereIn('academic_session_id', $sessionIds)->delete();
        AcademicSession::query()->whereIn('id', $sessionIds)->forceDelete();
    }
}
