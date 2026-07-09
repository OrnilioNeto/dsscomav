<?php

namespace App\Http\Controllers;

use App\Models\SocialPost;
use App\Models\SocialLike;
use App\Models\SocialComment;
use App\Models\SocialFollow;
use App\Models\User;
use App\Models\Training;
use App\Models\RankingMonthlyScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SocialController extends Controller
{
    /**
     * Exibe o Feed Principal da Rede Social
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Rastrear quem o usuário segue
        $followingIds = $user->following()->pluck('following_id');

        // Definir a aba ativa. Se o usuário não segue ninguém, força 'explorar' para não ficar vazio.
        $tab = $request->input('tab', $followingIds->isEmpty() ? 'explorar' : 'meu_feed');

        // Obter as postagens correspondentes
        if ($tab === 'meu_feed') {
            $userIds = $followingIds->push($user->id);
            $posts = SocialPost::whereIn('user_id', $userIds)
                ->with(['user', 'likes', 'comments.user', 'training'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $posts = SocialPost::with(['user', 'likes', 'comments.user', 'training'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Sugestões de colaboradores para seguir (quem ainda não é seguido e não é o próprio usuário)
        $suggestions = User::where('id', '!=', $user->id)
            ->where('status', 'ativo')
            ->whereNotIn('id', $followingIds)
            ->limit(5)
            ->get();

        // Tratar compartilhamento de resultado de treinamento (se houver parâmetro na URL)
        $sharedTraining = null;
        $sharedRank = null;
        if ($request->has('share_training_id')) {
            $training = Training::find($request->input('share_training_id'));
            if ($training) {
                // Verificar se o usuário realmente concluiu esse treinamento
                $progress = $user->progress()->where('training_id', $training->id)->first();
                if ($progress && $progress->concluido) {
                    $sharedTraining = $training;
                    
                    // Buscar o ranking mais recente do usuário
                    $latestScore = RankingMonthlyScore::where('user_id', $user->id)
                        ->orderByDesc('year_reference')
                        ->orderByDesc('month_reference')
                        ->first();
                    $sharedRank = $latestScore ? $latestScore->position : 'Sem ranking';
                }
            }
        }

        return view('social.feed', compact('posts', 'suggestions', 'tab', 'sharedTraining', 'sharedRank'));
    }

    /**
     * Armazena uma nova postagem no Feed
     */
    public function storePost(Request $request)
    {
        $request->validate([
            'caption' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:150',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192', // até 8MB
            'training_id' => 'nullable|exists:trainings,id',
        ]);

        try {
            $user = auth()->user();
            $photoPath = null;

            // 1. Processar Upload de Foto (se houver)
            if ($request->hasFile('photo')) {
                $uploadDir = public_path('uploads/social');
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }

                $photo = $request->file('photo');
                $filename = 'post_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
                $tmpPath = $photo->getRealPath();
                $saved = false;

                // Processar imagem via GD (se habilitado) para limitar largura máxima a 800px e comprimir
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
                            $maxWidth = 800;

                            if ($width > $maxWidth) {
                                $newWidth = $maxWidth;
                                $newHeight = intval(($height / $width) * $maxWidth);

                                $resized = imagecreatetruecolor($newWidth, $newHeight);
                                // Preservar transparências de PNG/WebP (convertendo fundo para branco em JPEG)
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

                $photoPath = $filename;
            }

            // 2. Processar Dados de Ranking se for compartilhamento de conquista
            $score = null;
            $rank = null;
            if ($request->input('training_id')) {
                $latestScore = RankingMonthlyScore::where('user_id', $user->id)
                    ->orderByDesc('year_reference')
                    ->orderByDesc('month_reference')
                    ->first();
                $rank = $latestScore ? $latestScore->position : null;
                $score = $latestScore ? $latestScore->average_score : null;
            }

            // 3. Criar Post
            SocialPost::create([
                'user_id' => $user->id,
                'photo_path' => $photoPath,
                'caption' => $request->input('caption'),
                'location' => $request->input('location'),
                'training_id' => $request->input('training_id'),
                'training_score' => $score,
                'ranking_position' => $rank,
            ]);

            return redirect()->route('social.feed')->with('success', 'Post publicado com sucesso!');
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar postagem no feed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocorreu um erro ao salvar a postagem: ' . $e->getMessage());
        }
    }

    /**
     * Remove uma postagem
     */
    public function destroyPost($id)
    {
        $post = SocialPost::findOrFail($id);

        // Apenas o próprio dono ou um admin/super admin pode deletar
        if ($post->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Acesso negado.');
        }

        try {
            // Deletar arquivo físico
            if ($post->photo_path) {
                $filePath = public_path("uploads/social/{$post->photo_path}");
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $post->delete();

            return redirect()->back()->with('success', 'Postagem removida do feed.');
        } catch (\Throwable $e) {
            Log::error('Erro ao deletar postagem: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao remover postagem.');
        }
    }

    /**
     * Alterna curtida na postagem (AJAX)
     */
    public function toggleLike($id)
    {
        try {
            $userId = auth()->id();
            $like = SocialLike::where('user_id', $userId)->where('post_id', $id)->first();
            
            if ($like) {
                $like->delete();
                $liked = false;
            } else {
                SocialLike::create([
                    'user_id' => $userId,
                    'post_id' => $id,
                ]);
                $liked = true;
            }

            $likesCount = SocialLike::where('post_id', $id)->count();

            return response()->json([
                'success' => true,
                'liked' => $liked,
                'likes_count' => $likesCount
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao curtir post: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cria um comentário na postagem (AJAX)
     */
    public function storeComment(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:500',
        ]);

        try {
            $comment = SocialComment::create([
                'user_id' => auth()->id(),
                'post_id' => $id,
                'content' => $request->input('content'),
            ]);

            $comment->load('user');

            return response()->json([
                'success' => true,
                'comment' => [
                    'id' => $comment->id,
                    'user_name' => $comment->user->nome,
                    'user_avatar' => $comment->user->getFotoPerfilUrl(),
                    'user_profile_url' => route('social.user.profile', $comment->user->id),
                    'content' => e($comment->content),
                    'created_at' => $comment->created_at->diffForHumans(),
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar comentário: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Segue ou deixa de seguir um colaborador (AJAX)
     */
    public function toggleFollow($id)
    {
        if ((int)$id === auth()->id()) {
            return response()->json(['success' => false, 'error' => 'Você não pode seguir a si mesmo.'], 400);
        }

        try {
            $user = auth()->user();
            $follow = SocialFollow::where('follower_id', $user->id)->where('following_id', $id)->first();

            if ($follow) {
                $follow->delete();
                $following = false;
            } else {
                SocialFollow::create([
                    'follower_id' => $user->id,
                    'following_id' => $id,
                ]);
                $following = true;
            }

            $targetUser = User::findOrFail($id);
            $followersCount = $targetUser->followersCount();

            return response()->json([
                'success' => true,
                'following' => $following,
                'followers_count' => $followersCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao seguir colaborador: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exibe o Perfil de outro Colaborador
     */
    public function showProfile($id)
    {
        $user = User::findOrFail($id);
        $posts = SocialPost::where('user_id', $id)
            ->with(['user', 'likes', 'comments.user', 'training'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('social.profile', compact('user', 'posts'));
    }
}
