<?php

namespace Tests\Feature;

use App\Http\Middleware\LogSystemRequests;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\RedefinirSenhaNotification;
use App\Support\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(string $cpf = '22222222222', string $email = 'joao@teste.com'): User
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);

        return User::create([
            'nome' => 'João Motorista',
            'cpf' => $cpf,
            'email' => $email,
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }

    public function test_pagina_esqueci_senha_disponivel_para_visitante(): void
    {
        $this->get('/esqueci-senha')->assertOk()->assertSee('Recuperar senha');
    }

    public function test_envia_email_de_redefinicao_para_email_cadastrado(): void
    {
        Notification::fake();
        $user = $this->criarUsuario();

        $response = $this->post('/esqueci-senha', ['email' => 'JOAO@TESTE.COM']);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('success');

        Notification::assertSentTo($user, RedefinirSenhaNotification::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'joao@teste.com']);
    }

    public function test_resposta_e_generica_para_email_nao_cadastrado(): void
    {
        Notification::fake();
        $this->criarUsuario();

        $response = $this->post('/esqueci-senha', ['email' => 'ninguem@teste.com']);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('success');

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'ninguem@teste.com']);
    }

    public function test_redefine_senha_com_token_valido(): void
    {
        $user = $this->criarUsuario();
        $token = Password::broker()->createToken($user);

        $response = $this->post('/redefinir-senha', [
            'token' => $token,
            'email' => 'joao@teste.com',
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('novaSenha123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'joao@teste.com']);

        // E2E: a nova senha funciona no login
        $this->post('/login', ['cpf' => '22222222222', 'password' => 'novaSenha123'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_rejeita_token_invalido(): void
    {
        $user = $this->criarUsuario();
        Password::broker()->createToken($user);

        $response = $this->post('/redefinir-senha', [
            'token' => 'token-invalido',
            'email' => 'joao@teste.com',
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue(Hash::check('senha123', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_rejeita_senha_menor_que_oito_caracteres(): void
    {
        $user = $this->criarUsuario();
        $token = Password::broker()->createToken($user);

        $response = $this->post('/redefinir-senha', [
            'token' => $token,
            'email' => 'joao@teste.com',
            'password' => 'curta',
            'password_confirmation' => 'curta',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('senha123', $user->fresh()->password));
    }

    public function test_rotas_de_recuperacao_redirecionam_usuario_logado(): void
    {
        $user = $this->criarUsuario();

        $this->actingAs($user)->get('/esqueci-senha')->assertRedirect('/dashboard');
        $this->actingAs($user)->get('/redefinir-senha/qualquer-token')->assertRedirect('/dashboard');
    }

    public function test_email_view_renderiza_com_link(): void
    {
        $user = $this->criarUsuario();
        $token = 'token-de-teste';

        $mail = (new RedefinirSenhaNotification($token))->toMail($user);

        $this->assertSame('Redefinição de senha - '.plataforma_nome(), $mail->subject);
        $this->assertSame('emails.redefinir-senha', $mail->view);
        $this->assertStringContainsString($token, $mail->viewData['url']);
        $this->assertStringContainsString(urlencode('joao@teste.com'), $mail->viewData['url']);

        // Garante que o Blade do e-mail renderiza sem erro.
        $html = view($mail->view, $mail->viewData)->render();
        $this->assertStringContainsString('Criar nova senha', $html);
        $this->assertStringContainsString($mail->viewData['url'], $html);
    }

    public function test_remetente_padrao_dos_emails_e_suporte_previaseg(): void
    {
        $this->assertSame('suporte@previaseg.com', config('mail.from.address'));
    }

    public function test_email_usa_remetente_do_tenant_quando_configurado(): void
    {
        $tenant = Tenant::create([
            'nome' => 'Cliente Teste',
            'slug' => 'cliente-teste',
            'email_remetente' => 'contato@cliente.test',
        ]);

        app(TenantManager::class)->set($tenant);

        try {
            $mail = (new RedefinirSenhaNotification('token-de-teste'))
                ->toMail($this->criarUsuario());

            $this->assertSame('contato@cliente.test', $mail->from[0] ?? null);
            $this->assertSame('Cliente Teste', $mail->from[1] ?? null);
        } finally {
            app(TenantManager::class)->clear();
        }
    }

    public function test_log_de_sistema_mascara_token_e_email_da_redefinicao(): void
    {
        $middleware = new LogSystemRequests;
        $request = Request::create('http://localhost/redefinir-senha/token-secreto?email=joao@teste.com');

        $safePath = (new \ReflectionMethod($middleware, 'safePath'))->invoke($middleware, $request);
        $safeUrl = (new \ReflectionMethod($middleware, 'safeUrl'))->invoke($middleware, $request);

        $this->assertSame('redefinir-senha/***', $safePath);
        $this->assertStringNotContainsString('token-secreto', $safeUrl);
        $this->assertStringNotContainsString('joao@teste.com', $safeUrl);
    }
}
