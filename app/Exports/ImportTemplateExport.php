<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ImportTemplateExport implements WithMultipleSheets
{
    public function __construct(private bool $withSampleData = false) {}

    public function sheets(): array
    {
        return [
            new ImportSheet($this->patientsData(),    'Pacientes'),
            new ImportSheet($this->treatmentsData(),  'Tratamientos'),
            new ImportSheet($this->appointmentsData(),'Citas'),
            new ImportSheet($this->invoicesData(),    'Facturas'),
        ];
    }

    private function patientsData(): array
    {
        $headers = [['nombre', 'apellido', 'email', 'telefono', 'cedula_rnc', 'fecha_nacimiento', 'genero', 'direccion', 'notas']];

        if (! $this->withSampleData) return $headers;

        return array_merge($headers, [
            ['María',   'García',      'maria.garcia@email.com',   '8091234567', '001-1234567-8', '1985-03-15', 'F', 'Calle Principal 123, Santo Domingo', 'Alergia a la penicilina'],
            ['Carlos',  'Martínez',    'carlos.martinez@email.com','8097654321', '001-7654321-2', '1978-07-22', 'M', 'Av. Independencia 456, Santiago',    ''],
            ['Ana',     'Rodríguez',   'ana.rodriguez@email.com',  '8091112233', '402-1234567-1', '1992-11-05', 'F', 'Los Prados, Santo Domingo Norte',    'Paciente con hipertensión'],
            ['Luis',    'Pérez',       'luis.perez@email.com',     '8294445566', '',              '1965-01-30', 'M', 'La Romana',                          ''],
            ['Sofía',   'Hernández',   'sofia.hernandez@email.com','8096667788', '001-9876543-0', '2000-09-18', 'F', 'Punta Cana, La Altagracia',          'Ortodoncia previa'],
            ['Roberto', 'Díaz',        '',                         '8098889900', '',              '',           'M', 'Bonao, Monseñor Nouel',               ''],
            ['Carmen',  'López',       'carmen.lopez@email.com',   '8091231234', '001-5555555-5', '1988-04-12', 'F', 'Villa Mella, DN',                    ''],
            ['Jorge',   'Sánchez',     'jorge.sanchez@email.com',  '8094564567', '',              '1975-12-08', 'M', 'Higüey, La Altagracia',              'Diabético'],
        ]);
    }

    private function treatmentsData(): array
    {
        $headers = [['nombre', 'categoria', 'precio', 'duracion_minutos', 'descripcion']];

        if (! $this->withSampleData) return $headers;

        return array_merge($headers, [
            ['Limpieza Dental',            'Preventiva',   2500,  60,  'Profilaxis dental completa'],
            ['Examen Dental',              'Preventiva',   1500,  30,  'Revisión general y diagnóstico'],
            ['Radiografía Panorámica',     'Preventiva',   2500,  20,  'Ortopantomografía digital'],
            ['Resina Compuesta',           'Restaurativa', 3500,  45,  'Restauración estética de composite'],
            ['Corona Zirconia',            'Restaurativa', 25000, 90,  'Corona de zirconia CAD/CAM'],
            ['Extracción Simple',          'Cirugía Oral', 2000,  30,  'Extracción de pieza permanente'],
            ['Extracción Muela del Juicio','Cirugía Oral', 6000,  60,  'Extracción quirúrgica'],
            ['Tratamiento de Conducto',    'Endodoncia',   12000, 90,  'Endodoncia monoradicular'],
            ['Blanqueamiento Dental',      'Estética',     8000,  90,  'Blanqueamiento en consultorio'],
            ['Brackets Metálicos',         'Ortodoncia',   45000, 60,  'Ortodoncia con brackets convencionales'],
            ['Implante Dental',            'Cirugía Oral', 60000, 90,  'Implante unitario con corona'],
            ['Limpieza Profunda (RAR)',    'Periodoncia',  5000,  60,  'Raspado y alisado radicular'],
        ]);
    }

    private function appointmentsData(): array
    {
        $headers = [['email_paciente', 'fecha_cita', 'estado', 'email_dentista', 'costo_total', 'notas']];

        if (! $this->withSampleData) return $headers;

        return array_merge($headers, [
            ['maria.garcia@email.com',    '2026-01-10 09:00', 'completed', 'james@dentaris.app', 4000,  'Limpieza + examen'],
            ['carlos.martinez@email.com', '2026-01-15 10:30', 'completed', 'james@dentaris.app', 3500,  'Resina diente 14'],
            ['ana.rodriguez@email.com',   '2026-01-20 14:00', 'completed', 'james@dentaris.app', 12000, 'Endodoncia pieza 36'],
            ['luis.perez@email.com',      '2026-02-05 08:00', 'completed', 'james@dentaris.app', 2000,  'Extracción pieza 18'],
            ['sofia.hernandez@email.com', '2026-02-12 11:00', 'completed', 'james@dentaris.app', 2500,  'Limpieza y profilaxis'],
            ['roberto.diaz@email.com',    '2026-02-18 16:00', 'cancelled', 'james@dentaris.app', 0,     'Canceló por enfermedad'],
            ['carmen.lopez@email.com',    '2026-03-02 09:30', 'completed', 'james@dentaris.app', 25000, 'Corona zirconia pieza 21'],
            ['jorge.sanchez@email.com',   '2026-03-10 15:00', 'completed', 'james@dentaris.app', 3500,  'Control de glucemia previo'],
        ]);
    }

    private function invoicesData(): array
    {
        $headers = [['numero_factura', 'email_paciente', 'fecha_factura', 'fecha_vencimiento', 'descripcion_item', 'precio_item', 'cantidad', 'itbis_pct', 'monto_pagado', 'estado', 'notas']];

        if (! $this->withSampleData) return $headers;

        return array_merge($headers, [
            // INV-M001 — una sola línea, pagada
            ['INV-M001', 'maria.garcia@email.com',    '2026-01-10', '2026-01-25', 'Limpieza Dental',            2500,  1, 18, 2950,  'paid',    ''],
            ['INV-M001', 'maria.garcia@email.com',    '2026-01-10', '2026-01-25', 'Examen Dental',              1500,  1, 18, 0,     'paid',    ''],
            // INV-M002 — pagada
            ['INV-M002', 'carlos.martinez@email.com', '2026-01-15', '2026-01-30', 'Resina Compuesta',           3500,  1, 18, 4130,  'paid',    ''],
            // INV-M003 — parcial
            ['INV-M003', 'ana.rodriguez@email.com',   '2026-01-20', '2026-02-20', 'Tratamiento de Conducto',    12000, 1, 18, 6000,  'partial', 'Abono inicial'],
            // INV-M004 — pagada
            ['INV-M004', 'luis.perez@email.com',      '2026-02-05', '2026-02-20', 'Extracción Simple',          2000,  1, 18, 2360,  'paid',    ''],
            // INV-M005 — pagada
            ['INV-M005', 'sofia.hernandez@email.com', '2026-02-12', '2026-02-27', 'Limpieza Dental',            2500,  1, 18, 2950,  'paid',    ''],
            // INV-M006 — pagada, múltiples ítems
            ['INV-M006', 'carmen.lopez@email.com',    '2026-03-02', '2026-03-17', 'Corona Zirconia',            25000, 1, 18, 29500, 'paid',    ''],
            ['INV-M006', 'carmen.lopez@email.com',    '2026-03-02', '2026-03-17', 'Consulta y diagnóstico',     1500,  1, 18, 0,     'paid',    ''],
            // INV-M007 — pendiente
            ['INV-M007', 'jorge.sanchez@email.com',   '2026-03-10', '2026-03-25', 'Limpieza Profunda (RAR)',    5000,  1, 18, 0,     'sent',    'Pendiente de pago'],
        ]);
    }
}

