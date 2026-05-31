<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\GdDriver;

class ProfilePhotoController extends Controller
{
    /**
     * Exibe a página de edição de perfil
     */
    public function edit()
    {
        $user = auth()->user();
        return view('usuarios.edit-profile', compact('user'));
    }

    /**
     * Faz upload e salva a foto de perfil
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'foto' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
            ]);

            $user = auth()->user();

            // Deletar foto anterior se existir
            if ($user->foto_perfil) {
                $oldPath = public_path("uploads/perfil/{$user->foto_perfil}");
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Garantir que diretório existe
            $uploadDir = public_path('uploads/perfil');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            // Obter arquivo
            $foto = $request->file('foto');
            
            // Gerar nome do arquivo
            $filename = 'perfil_' . $user->id . '_' . time() . '.jpg';
            
            // Processar a imagem com GD
            $tmpPath = $foto->getRealPath();
            $info = getimagesize($tmpPath);
            
            if (!$info) {
                throw new \Exception('Arquivo de imagem inválido');
            }
            
            // Criar imagem original
            $image = null;
            $mime = $info['mime'];
            
            if ($mime === 'image/jpeg') {
                $image = imagecreatefromjpeg($tmpPath);
            } elseif ($mime === 'image/png') {
                $image = imagecreatefrompng($tmpPath);
            } elseif ($mime === 'image/gif') {
                $image = imagecreatefromgif($tmpPath);
            } elseif ($mime === 'image/webp') {
                $image = imagecreatefromwebp($tmpPath);
            } else {
                throw new \Exception('Tipo de imagem não suportado: ' . $mime);
            }
            
            if (!$image) {
                throw new \Exception('Não foi possível processar a imagem');
            }
            
            // Redimensionar para 300x300 (mantém proporção e faz crop no centro)
            $width = imagesx($image);
            $height = imagesy($image);
            $size = min($width, $height);
            
            $x = intval(($width - $size) / 2);
            $y = intval(($height - $size) / 2);
            
            $resized = imagecreatetruecolor(300, 300);
            imagecopyresampled($resized, $image, 0, 0, $x, $y, 300, 300, $size, $size);
            
            // Salvar como JPEG com qualidade 80%
            $filePath = $uploadDir . '/' . $filename;
            imagejpeg($resized, $filePath, 80);
            
            // Liberar memória
            imagedestroy($image);
            imagedestroy($resized);
            
            // Atualizar usuário
            $user->update(['foto_perfil' => $filename]);

            return response()->json([
                'success' => true,
                'message' => 'Foto de perfil atualizada com sucesso!',
                'fotoUrl' => $user->getFotoPerfilUrl() . '?t=' . time(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao fazer upload de foto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar foto: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a foto de perfil
     */
    public function delete()
    {
        try {
            $user = auth()->user();

            if ($user->foto_perfil && file_exists(public_path("uploads/perfil/{$user->foto_perfil}"))) {
                @unlink(public_path("uploads/perfil/{$user->foto_perfil}"));
            }

            $user->update(['foto_perfil' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Foto de perfil removida!',
                'fotoUrl' => $user->getFotoPerfilUrl(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao remover foto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover foto: ' . $e->getMessage(),
            ], 500);
        }
    }
}
