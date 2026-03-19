<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::first();

        $patients = [
            ['first_name' => 'Alice',   'last_name' => 'Johnson',   'email' => 'alice.j@email.com',   'phone' => '555-201-0001', 'dob' => '1985-03-15', 'gender' => 'female', 'blood' => 'A+',  'city' => 'New York',    'insurance' => 'BlueCross',  'policy' => 'BC-001234'],
            ['first_name' => 'Bob',     'last_name' => 'Smith',     'email' => 'bob.s@email.com',     'phone' => '555-201-0002', 'dob' => '1978-07-22', 'gender' => 'male',   'blood' => 'O+',  'city' => 'Brooklyn',    'insurance' => 'Aetna',      'policy' => 'AT-005678'],
            ['first_name' => 'Carol',   'last_name' => 'Williams',  'email' => 'carol.w@email.com',   'phone' => '555-201-0003', 'dob' => '1992-11-08', 'gender' => 'female', 'blood' => 'B+',  'city' => 'Manhattan',   'insurance' => 'Cigna',      'policy' => 'CG-009012'],
            ['first_name' => 'David',   'last_name' => 'Brown',     'email' => 'david.b@email.com',   'phone' => '555-201-0004', 'dob' => '1965-04-30', 'gender' => 'male',   'blood' => 'AB+', 'city' => 'Queens',      'insurance' => 'United',     'policy' => 'UH-013456'],
            ['first_name' => 'Emma',    'last_name' => 'Davis',     'email' => 'emma.d@email.com',    'phone' => '555-201-0005', 'dob' => '1998-09-12', 'gender' => 'female', 'blood' => 'O-',  'city' => 'Bronx',       'insurance' => 'Humana',     'policy' => 'HM-017890'],
            ['first_name' => 'Frank',   'last_name' => 'Miller',    'email' => 'frank.m@email.com',   'phone' => '555-201-0006', 'dob' => '1971-12-25', 'gender' => 'male',   'blood' => 'A-',  'city' => 'Staten Island','insurance' => 'BlueCross',  'policy' => 'BC-022345'],
            ['first_name' => 'Grace',   'last_name' => 'Wilson',    'email' => 'grace.w@email.com',   'phone' => '555-201-0007', 'dob' => '1988-06-17', 'gender' => 'female', 'blood' => 'B-',  'city' => 'Hoboken',     'insurance' => null,         'policy' => null],
            ['first_name' => 'Henry',   'last_name' => 'Moore',     'email' => 'henry.m@email.com',   'phone' => '555-201-0008', 'dob' => '1955-02-03', 'gender' => 'male',   'blood' => 'O+',  'city' => 'Jersey City', 'insurance' => 'Medicare',   'policy' => 'MC-031234'],
            ['first_name' => 'Isabela', 'last_name' => 'Taylor',    'email' => 'isabela.t@email.com', 'phone' => '555-201-0009', 'dob' => '2001-08-19', 'gender' => 'female', 'blood' => 'A+',  'city' => 'New York',    'insurance' => 'Medicaid',   'policy' => 'MD-035678'],
            ['first_name' => 'Jack',    'last_name' => 'Anderson',  'email' => 'jack.a@email.com',    'phone' => '555-201-0010', 'dob' => '1983-05-28', 'gender' => 'male',   'blood' => 'AB-', 'city' => 'Brooklyn',    'insurance' => 'Aetna',      'policy' => 'AT-040012'],
            ['first_name' => 'Karen',   'last_name' => 'Thomas',    'email' => 'karen.t@email.com',   'phone' => '555-201-0011', 'dob' => '1976-10-14', 'gender' => 'female', 'blood' => 'O+',  'city' => 'Manhattan',   'insurance' => 'Cigna',      'policy' => 'CG-044456'],
            ['first_name' => 'Liam',    'last_name' => 'Jackson',   'email' => 'liam.j@email.com',    'phone' => '555-201-0012', 'dob' => '1994-01-07', 'gender' => 'male',   'blood' => 'B+',  'city' => 'Queens',      'insurance' => 'United',     'policy' => 'UH-048890'],
            ['first_name' => 'Mia',     'last_name' => 'White',     'email' => 'mia.w@email.com',     'phone' => '555-201-0013', 'dob' => '2005-07-31', 'gender' => 'female', 'blood' => 'A+',  'city' => 'New York',    'insurance' => 'BlueCross',  'policy' => 'BC-053234'],
            ['first_name' => 'Noah',    'last_name' => 'Harris',    'email' => 'noah.h@email.com',    'phone' => '555-201-0014', 'dob' => '1960-11-20', 'gender' => 'male',   'blood' => 'O-',  'city' => 'Bronx',       'insurance' => 'Medicare',   'policy' => 'MC-057678'],
            ['first_name' => 'Olivia',  'last_name' => 'Martin',    'email' => 'olivia.m@email.com',  'phone' => '555-201-0015', 'dob' => '1990-04-05', 'gender' => 'female', 'blood' => 'AB+', 'city' => 'Hoboken',     'insurance' => 'Humana',     'policy' => 'HM-062012'],
            ['first_name' => 'Peter',   'last_name' => 'Garcia',    'email' => 'peter.g@email.com',   'phone' => '555-201-0016', 'dob' => '1972-08-09', 'gender' => 'male',   'blood' => 'A-',  'city' => 'Jersey City', 'insurance' => null,         'policy' => null],
            ['first_name' => 'Quinn',   'last_name' => 'Martinez',  'email' => 'quinn.m@email.com',   'phone' => '555-201-0017', 'dob' => '2000-12-24', 'gender' => 'female', 'blood' => 'B-',  'city' => 'New York',    'insurance' => 'Aetna',      'policy' => 'AT-070890'],
            ['first_name' => 'Ryan',    'last_name' => 'Robinson',  'email' => 'ryan.r@email.com',    'phone' => '555-201-0018', 'dob' => '1987-03-16', 'gender' => 'male',   'blood' => 'O+',  'city' => 'Brooklyn',    'insurance' => 'Cigna',      'policy' => 'CG-075234'],
            ['first_name' => 'Sofia',   'last_name' => 'Clark',     'email' => 'sofia.c@email.com',   'phone' => '555-201-0019', 'dob' => '1995-09-02', 'gender' => 'female', 'blood' => 'A+',  'city' => 'Manhattan',   'insurance' => 'United',     'policy' => 'UH-079678'],
            ['first_name' => 'Tyler',   'last_name' => 'Lewis',     'email' => 'tyler.l@email.com',   'phone' => '555-201-0020', 'dob' => '1968-06-11', 'gender' => 'male',   'blood' => 'B+',  'city' => 'Queens',      'insurance' => 'BlueCross',  'policy' => 'BC-084012'],
        ];

        foreach ($patients as $p) {
            Patient::create([
                'clinic_id'              => $clinic->id,
                'first_name'             => $p['first_name'],
                'last_name'              => $p['last_name'],
                'email'                  => $p['email'],
                'phone'                  => $p['phone'],
                'date_of_birth'          => $p['dob'],
                'gender'                 => $p['gender'],
                'blood_type'             => $p['blood'],
                'city'                   => $p['city'],
                'address'                => rand(1, 999) . ' ' . collect(['Oak', 'Maple', 'Pine', 'Cedar', 'Elm'])->random() . ' St',
                'insurance_provider'     => $p['insurance'],
                'insurance_policy_number'=> $p['policy'],
                'emergency_contact_name' => 'Emergency Contact',
                'emergency_contact_phone'=> '555-999-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                'is_active'              => true,
            ]);
        }
    }
}
