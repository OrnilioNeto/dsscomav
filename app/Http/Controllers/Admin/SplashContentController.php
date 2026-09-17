<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SplashContent;
use App\Support\SafeUpload;
use Illuminate\Http\Request;

class SplashContentController extends Controller
{
    private const MATERIAL_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    public function __construct()
    {
        $this->middleware('permission:splash,view')->only(['index']);
        $this->middleware('permission:splash,edit')->except(['index']);
    }

    public function index()
    {
        $contents = SplashContent::orderBy('ordem')->get();

        return view('admin.splash.index', compact('contents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|max:255',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'material' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB
        ]);

        $data = $request->only(['titulo', 'texto_conteudo', 'data_inicio', 'data_fim']);
        $data['status'] = 'ativo';
        $data['ordem'] = SplashContent::max('ordem') + 1;

        if ($request->hasFile('material')) {
            $file = $request->file('material');
            $extensao = SafeUpload::extensionFromUploadedFile($file, self::MATERIAL_EXTENSIONS);

            if ($extensao === null) {
                return redirect()->back()->withErrors(['material' => 'Tipo de arquivo não permitido. Envie JPG, PNG ou PDF.']);
            }

            $filename = time().'_'.uniqid().'.'.$extensao;
            $file->move(public_path(tenant_upload_dir('splash')), $filename);
            $data['material_path'] = tenant_upload_dir('splash').'/'.$filename;
            $data['material_tipo'] = $extensao === 'pdf' ? 'pdf' : 'imagem';
        }

        SplashContent::create($data);

        return redirect()->back()->with('success', 'Conteúdo de splash criado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $content = SplashContent::findOrFail($id);

        $request->validate([
            'titulo' => 'required|max:255',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'material' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB
        ]);

        $data = $request->only(['titulo', 'texto_conteudo', 'data_inicio', 'data_fim', 'status']);

        if ($request->hasFile('material')) {
            // Deletar antigo
            if ($content->material_path && file_exists(public_path($content->material_path))) {
                @unlink(public_path($content->material_path));
            }

            $file = $request->file('material');
            $extensao = SafeUpload::extensionFromUploadedFile($file, self::MATERIAL_EXTENSIONS);

            if ($extensao === null) {
                return redirect()->back()->withErrors(['material' => 'Tipo de arquivo não permitido. Envie JPG, PNG ou PDF.']);
            }

            $filename = time().'_'.uniqid().'.'.$extensao;
            $file->move(public_path(tenant_upload_dir('splash')), $filename);
            $data['material_path'] = tenant_upload_dir('splash').'/'.$filename;
            $data['material_tipo'] = $extensao === 'pdf' ? 'pdf' : 'imagem';
        }

        $content->update($data);

        return redirect()->back()->with('success', 'Conteúdo atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $content = SplashContent::findOrFail($id);

        if ($content->material_path && file_exists(public_path($content->material_path))) {
            @unlink(public_path($content->material_path));
        }

        $content->delete();

        return redirect()->back()->with('success', 'Conteúdo excluído permanentemente.');
    }

    public function toggleStatus($id)
    {
        $content = SplashContent::findOrFail($id);
        $content->status = $content->status === 'ativo' ? 'inativo' : 'ativo';
        $content->save();

        return redirect()->back()->with('success', 'Status alterado com sucesso!');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:splash_contents,id',
        ]);

        foreach ($request->ids as $index => $id) {
            SplashContent::where('id', $id)->update(['ordem' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
