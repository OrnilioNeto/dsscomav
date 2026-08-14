<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = validator($request->all(), [
            'telefone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'data_nascimento' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['telefone', 'email', 'data_nascimento']);
        $user->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json([
            'status' => 'success',
            'message' => 'Perfil atualizado com sucesso!',
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $user = $request->user();

        $validator = validator($request->all(), [
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Envie uma imagem válida (máx. 8 MB).',
            ], 422);
        }

        try {
            $uploadDir = public_path('uploads/perfil');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $photo = $request->file('foto');
            $filename = 'perfil_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
            $tmpPath = $photo->getRealPath();
            $saved = false;

            if (
                function_exists('getimagesize') &&
                function_exists('imagecreatetruecolor') &&
                function_exists('imagecopyresampled') &&
                function_exists('imagejpeg')
            ) {
                $info = @getimagesize($tmpPath);
                if ($info && isset($info['mime'])) {
                    $image = null;
                    $mime = $info['mime'];

                    if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
                        $image = @imagecreatefromjpeg($tmpPath);
                    } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
                        $image = @imagecreatefrompng($tmpPath);
                    } elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) {
                        $image = @imagecreatefromgif($tmpPath);
                    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
                        $image = @imagecreatefromwebp($tmpPath);
                    }

                    if ($image) {
                        $width = imagesx($image);
                        $height = imagesy($image);
                        $maxWidth = 600;

                        if ($width > $maxWidth) {
                            $newWidth = $maxWidth;
                            $newHeight = intval(($height / $width) * $maxWidth);
                            $resized = imagecreatetruecolor($newWidth, $newHeight);
                            $whiteBg = imagecolorallocate($resized, 255, 255, 255);
                            imagefill($resized, 0, 0, $whiteBg);
                            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                            $filePath = $uploadDir . '/' . $filename;
                            $saved = imagejpeg($resized, $filePath, 80) === true;

                            imagedestroy($image);
                            imagedestroy($resized);
                        }
                    }
                }
            }

            if (!$saved) {
                $photo->move($uploadDir, $filename);
            }

            // Remover foto antiga
            if ($user->foto_perfil && $user->foto_perfil !== $filename) {
                $oldPath = public_path("uploads/perfil/{$user->foto_perfil}");
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $user->update(['foto_perfil' => $filename]);

            return response()->json([
                'status' => 'success',
                'message' => 'Foto de perfil atualizada com sucesso!',
                'foto_perfil' => $filename,
                'avatar_url' => $user->getFotoPerfilUrl(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Erro ao fazer upload da foto de perfil: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Não foi possível atualizar a foto de perfil.',
            ], 500);
        }
    }

    public function deletePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->foto_perfil) {
            $oldPath = public_path("uploads/perfil/{$user->foto_perfil}");
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }

            $user->update(['foto_perfil' => null]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Foto de perfil removida.',
            'avatar_url' => $user->getFotoPerfilUrl(),
        ]);
    }

    public function fichaQr(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'ficha_url' => $user->ficha_url,
                'qr_code_url' => $user->ficha_qr_code_url,
            ],
        ]);
    }
}
