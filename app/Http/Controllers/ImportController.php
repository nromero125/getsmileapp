<?php

namespace App\Http\Controllers;

use App\Exports\ImportTemplateExport;
use App\Imports\AppointmentsImport;
use App\Imports\InvoicesImport;
use App\Imports\PatientsImport;
use App\Imports\TreatmentsImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    private array $types = [
        'patients'     => ['label' => 'Pacientes',     'sheet' => 'Pacientes'],
        'treatments'   => ['label' => 'Tratamientos',  'sheet' => 'Tratamientos'],
        'appointments' => ['label' => 'Citas',         'sheet' => 'Citas'],
        'invoices'     => ['label' => 'Facturas',      'sheet' => 'Facturas'],
    ];

    public function index()
    {
        return Inertia::render('Import/Index', [
            'types' => $this->types,
        ]);
    }

    /**
     * Preview first 10 rows of the uploaded file without importing.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'type' => 'required|in:patients,treatments,appointments,invoices',
        ]);

        $rows = Excel::toArray(
            new class implements \Maatwebsite\Excel\Concerns\ToArray, \Maatwebsite\Excel\Concerns\WithHeadingRow {
                public function array(array $array): void {}
            },
            $request->file('file')
        );

        // Try to find matching sheet, fall back to first sheet
        $type      = $request->type;
        $sheetName = $this->types[$type]['sheet'];
        $data      = $rows[0] ?? [];

        foreach ($rows as $name => $sheetData) {
            if (is_string($name) && str_contains(strtolower($name), strtolower($sheetName))) {
                $data = $sheetData;
                break;
            }
        }

        // Store temporarily for the actual import
        $path = $request->file('file')->store('imports/pending', 'local');

        return response()->json([
            'path'    => $path,
            'preview' => array_slice($data, 0, 10),
            'total'   => count($data),
            'columns' => array_keys($data[0] ?? []),
        ]);
    }

    /**
     * Run the actual import from a previously uploaded file.
     */
    public function run(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'type' => 'required|in:patients,treatments,appointments,invoices',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $fullPath = storage_path('app/' . $request->path);

        if (! file_exists($fullPath)) {
            return back()->with('error', 'Archivo no encontrado. Por favor sube el archivo de nuevo.');
        }

        $importer = match ($request->type) {
            'patients'     => new PatientsImport($clinicId),
            'treatments'   => new TreatmentsImport($clinicId),
            'appointments' => new AppointmentsImport($clinicId),
            'invoices'     => new InvoicesImport($clinicId),
        };

        try {
            Excel::import($importer, $fullPath);
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        } finally {
            @unlink($fullPath);
        }

        $label = $this->types[$request->type]['label'];

        return back()->with('import_result', [
            'type'     => $label,
            'imported' => $importer->imported,
            'skipped'  => $importer->skipped,
            'errors'   => array_slice($importer->errors, 0, 20),
        ]);
    }

    /**
     * Download template (blank) or sample (with data) Excel file.
     */
    public function downloadTemplate(Request $request)
    {
        $withData = $request->boolean('sample');
        $filename = $withData ? 'dentaris_datos_de_ejemplo.xlsx' : 'dentaris_plantilla_importacion.xlsx';

        return Excel::download(new ImportTemplateExport($withData), $filename);
    }
}
