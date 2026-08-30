<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\ClassSchedule;
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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

        $levels = $this->officialLevels($stage);

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
            foreach ($levels as $level) {
                $level->subjects()->syncWithoutDetaching([$subjectModels[$item['code']]->id]);
            }
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
            $user = User::query()->firstOrNew(['email' => $item['email']]);
            $user->fill([
                'first_name' => $item['first'],
                'last_name' => $item['last'],
                'name' => $item['first'].' '.$item['last'],
                'phone' => null,
                'locale' => 'ar',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            if (! $user->exists) {
                $user->password = app()->environment('production')
                    ? Str::password(20)
                    : 'Password123!';
            }
            $user->save();
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
            if (Schema::hasTable('teacher_level')) {
                $teacher->levels()->sync(collect($levels)->pluck('id')->all());
            }

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
                    'level_id' => $levels['L2']->id,
                    'status' => 'active',
                    'registered_at' => now(),
                ]
            );
        }

        foreach ($levels as $level) {
            foreach ($subjectModels as $code => $subject) {
                $class = ClassGroup::query()->updateOrCreate(
                    [
                        'academic_year_id' => $year->id,
                        'subject_id' => $subject->id,
                        'level_id' => $level->id,
                    ],
                    [
                        'name' => $subject->name_ar.' — '.$level->name_ar,
                        'teacher_id' => $teachersBySubject[$code]?->id,
                        'code' => $code.'-'.$level->code,
                        'capacity' => 20,
                        'status' => 'active',
                    ]
                );

                if ($level->code !== 'L2') {
                    continue;
                }

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

        $this->seedWeeklySchedules($subjectModels, $levels, $year);
    }

    /**
     * @return array<string, Level>
     */
    private function officialLevels(EducationStage $stage): array
    {
        $definitions = [
            ['code' => 'L1', 'name_ar' => 'الأول', 'name_fr' => '1re année', 'sort_order' => 10],
            ['code' => 'L2', 'name_ar' => 'الثاني', 'name_fr' => '2e année', 'sort_order' => 20],
            ['code' => 'L3', 'name_ar' => 'الثالث', 'name_fr' => '3e année', 'sort_order' => 30],
            ['code' => 'SPECIAL', 'name_ar' => 'حالة خاصة', 'name_fr' => 'Cas particulier', 'sort_order' => 40],
        ];

        $levels = [];
        foreach ($definitions as $item) {
            $levels[$item['code']] = Level::query()->updateOrCreate(
                ['education_stage_id' => $stage->id, 'code' => $item['code']],
                [
                    'name_ar' => $item['name_ar'],
                    'name_fr' => $item['name_fr'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        $legacy = Level::query()
            ->where('education_stage_id', $stage->id)
            ->where('code', 'CE2')
            ->first();
        if ($legacy) {
            Student::query()->where('level_id', $legacy->id)->update(['level_id' => $levels['L2']->id]);
            ClassGroup::query()->where('level_id', $legacy->id)->update(['level_id' => $levels['L2']->id]);
            $legacy->update(['is_active' => false]);
        }

        return $levels;
    }

    private function seedWeeklySchedules(array $subjectModels, array $levels, AcademicYear $year): void
    {
        if (! Schema::hasTable('class_schedules')) {
            return;
        }

        $templates = [
            'AR' => [
                ['weekday' => 6, 'starts_at' => '10:00:00', 'ends_at' => '11:00:00', 'room' => 'Salle A'],
                ['weekday' => 7, 'starts_at' => '10:00:00', 'ends_at' => '11:00:00', 'room' => 'Salle A'],
            ],
            'QURAN' => [
                ['weekday' => 6, 'starts_at' => '11:15:00', 'ends_at' => '12:15:00', 'room' => 'Salle A'],
            ],
            'MATH' => [
                ['weekday' => 7, 'starts_at' => '11:15:00', 'ends_at' => '12:15:00', 'room' => 'Salle B'],
            ],
        ];

        foreach ($levels as $level) {
            foreach ($templates as $code => $slots) {
                $subject = $subjectModels[$code] ?? null;
                if (! $subject) {
                    continue;
                }
                $class = ClassGroup::query()
                    ->where('academic_year_id', $year->id)
                    ->where('subject_id', $subject->id)
                    ->where('level_id', $level->id)
                    ->first();
                if (! $class) {
                    continue;
                }
                foreach ($slots as $slot) {
                    ClassSchedule::query()->updateOrCreate(
                        [
                            'class_group_id' => $class->id,
                            'weekday' => $slot['weekday'],
                            'starts_at' => $slot['starts_at'],
                        ],
                        [
                            'ends_at' => $slot['ends_at'],
                            'room' => $slot['room'],
                        ]
                    );
                }
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
