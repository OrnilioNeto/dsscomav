<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Support\SafeUpload;
use Illuminate\Http\Request;

/**
 * Configurações globais da Plataforma (somente super_admin).
 * Hoje: fundo padrão do certificado da empresa raiz (sem tenant).
 */
class PlataformaSettingsController extends Controller
{
    public function edit()
    {
        $setting = PlatformSetting::firstOrCreate([]);

        return view('plataforma.configuracoes', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'fundo_certificado' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        $setting = PlatformSetting::firstOrCreate([]);

        if ($request->hasFile('fundo_certificado')) {
            $dir = 'uploads/certificado';
            $file = $request->file('fundo_certificado');
            $extensao = SafeUpload::extensionFromUploadedFile($file, ['png', 'jpg', 'jpeg', 'webp']) ?? 'png';
            $filename = 'fundo_padrao_'.time().'.'.$extensao;
            $file->move(public_path($dir), $filename);
            $setting->update(['fundo_certificado' => $dir.'/'.$filename]);
        }

        return redirect()->route('plataforma.settings.edit')->with('success', 'Fundo padrão do certificado atualizado!');
    }

    /**
     * Remove o fundo padrão e volta para a imagem padrão do sistema.
     */
    public function removeFundo()
    {
        $setting = PlatformSetting::firstOrCreate([]);

        if ($setting->fundo_certificado) {
            @unlink(public_path($setting->fundo_certificado));
            $setting->update(['fundo_certificado' => null]);
        }

        return redirect()->route('plataforma.settings.edit')
            ->with('success', 'Fundo padrão removido. O certificado volta a usar a imagem padrão do sistema.');
    }
}
