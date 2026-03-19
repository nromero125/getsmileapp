<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PatientFileController extends Controller
{
    private array $allowedMimes = [
        'image/jpeg', 'image/png', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function store(Request $request, Patient $patient)
    {
        $this->authorizeClinic($patient);

        $request->validate([
            'file'           => ['required', 'file', 'max:20480'],
            'collection'     => ['required', 'in:xrays,photos,documents,other'],
            'custom_name'    => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:500'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
        ]);

        $file = $request->file('file');

        if (!in_array($file->getMimeType(), $this->allowedMimes)) {
            return back()->withErrors(['file' => 'File type not allowed.']);
        }

        $patient
            ->addMedia($file)
            ->usingName($request->custom_name ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            ->usingFileName($file->getClientOriginalName())
            ->withCustomProperties([
                'uploaded_by'    => auth()->id(),
                'uploader_name'  => auth()->user()->name,
                'notes'          => $request->notes,
                'appointment_id' => $request->appointment_id,
            ])
            ->toMediaCollection($request->collection);

        return back()->with('success', 'File uploaded successfully.');
    }

    public function destroy(Patient $patient, Media $media)
    {
        $this->authorizeClinic($patient);

        if ($media->model_id !== $patient->id || $media->model_type !== Patient::class) {
            abort(403);
        }

        $media->delete();

        return back()->with('success', 'File deleted.');
    }

    public function download(Patient $patient, Media $media)
    {
        $this->authorizeClinic($patient);

        if ($media->model_id !== $patient->id || $media->model_type !== Patient::class) {
            abort(403);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    private function authorizeClinic(Patient $patient): void
    {
        if ($patient->clinic_id !== auth()->user()->clinic_id) {
            abort(403);
        }
    }
}
