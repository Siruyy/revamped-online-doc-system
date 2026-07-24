<?php

namespace Database\Seeders;

use App\Models\AcademicDepartment;
use App\Models\AcademicProgram;
use Illuminate\Database\Seeder;

class AcademicProgramSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $departmentCode => $department) {
            $departmentModel = AcademicDepartment::query()->updateOrCreate(
                ['code' => $departmentCode],
                ['name' => $department['name'], 'is_active' => true],
            );

            foreach ($department['programs'] as [$code, $name, $major, $level]) {
                AcademicProgram::query()->updateOrCreate(
                    ['code' => $code, 'major' => $major],
                    [
                        'academic_department_id' => $departmentModel->id,
                        'name' => $name,
                        'level' => $level,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, array{name: string, programs: list<array{string, string, ?string, string}>}>
     */
    private function catalog(): array
    {
        return [
            'GSD' => [
                'name' => 'Graduate School Department',
                'programs' => [
                    ['EMD', 'Doctor of Educational Management', null, 'postgraduate'],
                    ['MAED', 'Master of Arts in Education', 'Educational Management', 'graduate'],
                    ['MAED', 'Master of Arts in Education', 'English', 'graduate'],
                    ['MAED', 'Master of Arts in Education', 'Mathematics', 'graduate'],
                    ['MAED', 'Master of Arts in Education', 'Filipino', 'graduate'],
                    ['MAED', 'Master of Arts in Education', 'Physical Education', 'graduate'],
                    ['MAED', 'Master of Arts in Education', 'Science', 'graduate'],
                    ['MAED', 'Master of Arts in Education', 'Social Studies', 'graduate'],
                    ['MBA', 'Master in Business Administration', null, 'graduate'],
                    ['MBA', 'Master in Business Administration', 'With Thesis', 'graduate'],
                    ['MPM', 'Master in Public Management', null, 'graduate'],
                    ['MPM', 'Master in Public Management', 'With Thesis', 'graduate'],
                ],
            ],
            'ASTED' => [
                'name' => 'Arts, Sciences, and Teacher Education Department',
                'programs' => [
                    ['BA Philo', 'Bachelor of Arts in Philosophy', null, 'college'],
                    ['BA PoS', 'Bachelor of Arts in Political Science', null, 'college'],
                    ['BEEd', 'Bachelor of Elementary Education', null, 'college'],
                    ['BSNEd', 'Bachelor of Special Needs Education', 'Elementary School Teaching', 'college'],
                    ['BPEd', 'Bachelor of Physical Education', null, 'college'],
                    ['BSEd', 'Bachelor of Secondary Education', 'English', 'college'],
                    ['BSEd', 'Bachelor of Secondary Education', 'Mathematics', 'college'],
                    ['BSEd', 'Bachelor of Secondary Education', 'Filipino', 'college'],
                    ['BSSW', 'Bachelor of Science in Social Work', null, 'college'],
                ],
            ],
            'AED' => [
                'name' => 'Accounting Education Department',
                'programs' => [
                    ['BSA', 'Bachelor of Science in Accountancy', null, 'college'],
                    ['BSAIS', 'Bachelor of Science in Accounting Information System', null, 'college'],
                ],
            ],
            'BMED' => [
                'name' => 'Business and Management Education Department',
                'programs' => [
                    ['BSBA', 'Bachelor of Science in Business Administration', 'Financial Management', 'college'],
                    ['BSBA', 'Bachelor of Science in Business Administration', 'Human Resource Management', 'college'],
                    ['BSBA', 'Bachelor of Science in Business Administration', 'Marketing Management', 'college'],
                    ['BSOA', 'Bachelor of Science in Office Administration', null, 'college'],
                ],
            ],
            'CSD' => [
                'name' => 'Computer Studies Department',
                'programs' => [
                    ['BSIT', 'Bachelor of Science in Information Technology', null, 'college'],
                    ['BSCS', 'Bachelor of Science in Computer Science', null, 'college'],
                ],
            ],
            'CESD' => [
                'name' => 'Computer Engineering and Studies Department',
                'programs' => [
                    ['BSCpE', 'Bachelor of Science in Computer Engineering', null, 'college'],
                ],
            ],
            'IHMD' => [
                'name' => 'International Hospitality Management Department',
                'programs' => [
                    ['BSHM', 'Bachelor of Science in Hospitality Management', null, 'college'],
                    ['BSTM', 'Bachelor of Science in Tourism Management', null, 'college'],
                ],
            ],
            'CCJ' => [
                'name' => 'College of Criminal Justice',
                'programs' => [
                    ['BSCrim', 'Bachelor of Science in Criminology', null, 'college'],
                ],
            ],
        ];
    }
}
