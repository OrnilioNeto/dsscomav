<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialPost;
use App\Models\SocialLike;
use App\Models\SocialComment;
use App\Models\SocialFollow;
use App\Models\User;
use App\Models\RankingMonthlyScore;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SocialController extends Controller
{
    private function serializePost(SocialPost $post, int $currentUserId): array
    {
        $post->loadMissing(['user', 'likes', 'comments.user', 'training']);

        return [
            'id' => $post->id,
            'caption' => $post->caption,
            'location' => $post->location,
            'photo_url' => $post->photo_path ? url("uploads/social/{$post->photo_path}") : null,
            'created_at' => $post->created_at?->toISOString(),
            'created_at_diff' => $post->created_at?->diffForHumans(),
            'user' => [
                'id' => $post->user->id,
                'nome' => $post->user->nome,
                'tipo_usuario' => $post->user->tipo_usuario,
                'avatar_url' => $post->user->getFotoPerfilUrl(),
            ],
            'likes_count' => $post->likes->count(),
            'liked_by_me' => $post->likes->contains('user_id', $currentUserId),
            'comments_count' => $post->comments->count(),
            'comments' => $post->comments->map(fn ($c) => [
                'id' => $c->id,
                'content' => $c->content,
                'created_at_diff' => $c->created_at?->diffForHumans(),
                'user' => [
                    'id' => $c->user->id,
                    'nome' => $c->user->nome,
                    'avatar_url' => $c->user->getFotoPerfilUrl(),
                ],
            ])->values(),
            'training' => $post->training ? [
                'id' => $post->training->id,
                'titulo' => $post->training->titulo,
            ] : null,
            'training_score' => $post->training_score,
            'ranking_position' => $post->ranking_position,
            'can_delete' => $post->user_id === $currentUserId,
        ];
    }

    public function feed(Request $request)
    {
        $user = $request->user();

        $followingIds = $user->following()->pluck('following_id');
        $tab = $request->input('tab', $followingIds->isEmpty() ? 'explorar' : 'meu_feed');

        if ($tab === 'meu_feed') {
            $userIds = $followingIds->push($user->id);
            $posts = SocialPost::with(['user', 'likes', 'comments.user', 'training'])
                ->whereIn('user_id', $userIds)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $posts = SocialPost::with(['user', 'likes', 'comments.user', 'training'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $suggestions = User::where('id', '!=', $user->id)
            ->where('status', 'ativo')
            ->whereNotIn('id', $followingIds)
            ->limit(5)
            ->get(['id', 'nome', 'tipo_usuario', 'foto_perfil'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'nome' => $u->nome,
                'tipo_usuario' => $u->tipo_usuario,
                'avatar_url' => $u->getFotoPerfilUrl(),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'tab' => $tab,
                'posts' => $posts->map(fn ($p) => $this->serializePost($p, $user->id))->values(),
                'suggestions' => $suggestions,
            ],
        ]);
    }

    public function storePost(Request $request)
    {
        $validator = validator($request->all(), [
            'caption' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:150',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
            'training_id' => 'nullable|exists:trainings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $photoPath = null;

            if ($request->hasFile('photo')) {
                $uploadDir = public_path('uploads/social');
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }

                $photo = $request->file('photo');
                $filename = 'post_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
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
                            $maxWidth = 800;

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

                $photoPath = $filename;
            }

            $score = null;
            $rank = null;
            if ($request->input('training_id')) {
                $trainingId = (int) $request->input('training_id');
                $progress = $user->progress()->where('training_id', $trainingId)->first();
                if (!$progress || !$progress->concluido) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Você só pode compartilhar conquistas de treinamentos concluídos.',
                    ], 422);
                }

                $latestScore = RankingMonthlyScore::where('user_id', $user->id)
                    ->orderByDesc('year_reference')
                    ->orderByDesc('month_reference')
                    ->first();
                $rank = $latestScore ? $latestScore->position : null;
                $score = $latestScore ? $latestScore->average_score : null;
            }

            $post = SocialPost::create([
                'user_id' => $user->id,
                'photo_path' => $photoPath,
                'caption' => $request->input('caption'),
                'location' => $request->input('location'),
                'training_id' => $request->input('training_id'),
                'training_score' => $score,
                'ranking_position' => $rank,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Post publicado com sucesso!',
                'data' => $this->serializePost($post, $user->id),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar postagem via API: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu um erro ao salvar a postagem.',
            ], 500);
        }
    }

    public function destroyPost($id)
    {
        $post = SocialPost::findOrFail($id);
        $user = request()->user();

        if ($post->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Acesso negado.'], 403);
        }

        try {
            if ($post->photo_path) {
                $filePath = public_path("uploads/social/{$post->photo_path}");
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $post->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Postagem removida do feed.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao deletar postagem: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao remover postagem.',
            ], 500);
        }
    }

    public function toggleLike($id)
    {
        try {
            $userId = request()->user()->id;
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
                'status' => 'success',
                'liked' => $liked,
                'likes_count' => $likesCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao curtir post: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Erro ao curtir post.'], 500);
        }
    }

    public function storeComment(Request $request, $id)
    {
        $validator = validator($request->all(), [
            'content' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Comentário é obrigatório (máx. 500 caracteres).'], 422);
        }

        try {
            $comment = SocialComment::create([
                'user_id' => request()->user()->id,
                'post_id' => $id,
                'content' => $request->input('content'),
            ]);

            $comment->load('user');

            return response()->json([
                'status' => 'success',
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at?->toISOString(),
                    'created_at_diff' => $comment->created_at?->diffForHumans(),
                    'user' => [
                        'id' => $comment->user->id,
                        'nome' => $comment->user->nome,
                        'avatar_url' => $comment->user->getFotoPerfilUrl(),
                    ],
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar comentário: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Erro ao salvar comentário.'], 500);
        }
    }

    public function toggleFollow($id)
    {
        $user = request()->user();

        if ((int) $id === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Você não pode seguir a si mesmo.',
            ], 400);
        }

        try {
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

            return response()->json([
                'status' => 'success',
                'following' => $following,
                'followers_count' => $targetUser->followersCount(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao seguir colaborador: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Erro ao seguir colaborador.'], 500);
        }
    }

    public function showProfile($id)
    {
        $user = request()->user();
        $target = User::findOrFail($id);

        $posts = SocialPost::where('user_id', $id)
            ->with(['user', 'likes', 'comments.user', 'training'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $target->id,
                    'nome' => $target->nome,
                    'tipo_usuario' => $target->tipo_usuario,
                    'cargo' => $target->cargo,
                    'empresa' => $target->empresa,
                    'avatar_url' => $target->getFotoPerfilUrl(),
                    'followers_count' => $target->followersCount(),
                    'following_count' => $target->followingCount(),
                    'posts_count' => $posts->count(),
                    'is_following' => $user->isFollowing($target->id),
                    'is_me' => $target->id === $user->id,
                ],
                'posts' => $posts->map(fn ($p) => $this->serializePost($p, $user->id))->values(),
            ],
        ]);
    }
}
