<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\EducationStage;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicAttendanceSeeder extends Seeder
{
    public function run(): void
    {
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
            ['code' => 'FR', 'name_ar' => 'اللغة الفرنسية', 'name_fr' => 'Français'],
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

            $session = AcademicSession::query()->updateOrCreate(
                [
                    'class_group_id' => $class->id,
                    'session_date' => now()->toDateString(),
                    'starts_at' => '10:00:00',
                ],
                [
                    'ends_at' => '11:00:00',
                    'status' => 'completed',
                    'room' => 'Salle A',
                ]
            );

            foreach ($students as $i => $student) {
                $status = match ($i % 4) {
                    0 => StudentAttendance::STATUS_PRESENT,
                    1 => StudentAttendance::STATUS_PRESENT,
                    2 => StudentAttendance::STATUS_ABSENT,
                    default => StudentAttendance::STATUS_LATE,
                };
                StudentAttendance::query()->updateOrCreate(
                    [
                        'academic_session_id' => $session->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'status' => $status,
                    ]
                );
            }
        }
    }
}
