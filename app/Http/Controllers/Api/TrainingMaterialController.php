<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingMaterial;
use Illuminate\Support\Facades\Storage;

class TrainingMaterialController extends Controller
{
    public function download($materialId)
    {
        $material = TrainingMaterial::findOrFail($materialId);
        $training = $material->training;
        $user = request()->user();

        if (!$user || !$user->canAccessTraining($training)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Você não tem acesso a este material.',
            ], 403);
        }

        if (!Storage::disk('public')->exists($material->arquivo)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Arquivo não encontrado.',
            ], 404);
        }

        $fullPath = Storage::disk('public')->path($material->arquivo);
        $downloadName = basename($material->arquivo);

        return response()->download($fullPath, $downloadName, [
            'Content-Type' => Storage::disk('public')->mimeType($material->arquivo) ?: 'application/octet-stream',
        ]);
    }
}
