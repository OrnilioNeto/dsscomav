<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Training;

class TrainingSeeder extends Seeder
{
    public function run(): void
    {
        // DSS para Motoristas
        Training::create([
            'titulo' => 'DSS - Segurança na Condução',
            'descricao' => 'Diálogo Semanal de Segurança sobre práticas seguras de condução',
            'tipo' => 'dss',
            'tipo_usuario_permitido' => ['motorista'],
            'url_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'tipo_video' => 'youtube',
            'carga_horaria' => 15,
            'data_publicacao' => now(),
            'status' => 'ativo',
            'obrigatorio' => true,
            'avaliacao_pergunta' => 'Qual a postura correta ao dirigir em condições adversas?',
            'avaliacao_opcoes' => ['Reduzir a velocidade e manter distância segura', 'Acelerar para sair logo da situação', 'Desligar o cinto para ter mais conforto', 'Usar o celular para pedir ajuda'],
            'avaliacao_resposta_correta' => 0,
        ]);

        // Treinamento para Funcionários
        Training::create([
            'titulo' => 'Treinamento - Normas de Segurança',
            'descricao' => 'Conhecimento obrigatório sobre normas de segurança da empresa',
            'tipo' => 'treinamento',
            'tipo_usuario_permitido' => ['funcionario'],
            'url_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'tipo_video' => 'youtube',
            'carga_horaria' => 30,
            'data_publicacao' => now(),
            'status' => 'ativo',
            'obrigatorio' => true,
            'avaliacao_pergunta' => 'Qual atitude representa segurança no ambiente de trabalho?',
            'avaliacao_opcoes' => ['Ignorar os EPIs', 'Seguir os procedimentos e usar EPIs', 'Correr no setor', 'Trabalhar sem comunicação com a equipe'],
            'avaliacao_resposta_correta' => 1,
        ]);

        // DSS para Terceirizados
        Training::create([
            'titulo' => 'DSS - Segurança no Trabalho',
            'descricao' => 'Orientações de segurança para colaboradores terceirizados',
            'tipo' => 'dss',
            'tipo_usuario_permitido' => ['terceirizado'],
            'url_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'tipo_video' => 'youtube',
            'carga_horaria' => 20,
            'data_publicacao' => now(),
            'status' => 'ativo',
            'obrigatorio' => true,
            'avaliacao_pergunta' => 'Qual procedimento é essencial antes de iniciar uma atividade?',
            'avaliacao_opcoes' => ['Verificar os riscos e orientações do local', 'Começar rapidamente sem olhar o ambiente', 'Dispensar o responsável', 'Ignorar normas internas'],
            'avaliacao_resposta_correta' => 0,
        ]);

        // Treinamento para Todos
        Training::create([
            'titulo' => 'Treinamento - Combate ao Incêndio',
            'descricao' => 'Treinamento geral de combate ao incêndio para todos os colaboradores',
            'tipo' => 'treinamento',
            'tipo_usuario_permitido' => ['motorista', 'funcionario', 'terceirizado'],
            'url_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'tipo_video' => 'youtube',
            'carga_horaria' => 25,
            'data_publicacao' => now(),
            'status' => 'ativo',
            'obrigatorio' => true,
            'avaliacao_pergunta' => 'Qual é a resposta correta ao identificar princípio de incêndio?',
            'avaliacao_opcoes' => ['Avisar a equipe e usar o extintor adequado se treinado', 'Esconder o problema', 'Abrir todas as portas sem orientação', 'Voltar para pegar objetos pessoais'],
            'avaliacao_resposta_correta' => 0,
        ]);

        // Outro exemplo DSS
        Training::create([
            'titulo' => 'DSS - Primeiros Socorros',
            'descricao' => 'Diálogo semanal sobre primeiros socorros básicos',
            'tipo' => 'dss',
            'tipo_usuario_permitido' => ['motorista', 'funcionario'],
            'url_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'tipo_video' => 'youtube',
            'carga_horaria' => 20,
            'data_publicacao' => now()->subDays(7),
            'status' => 'ativo',
            'obrigatorio' => false,
            'avaliacao_pergunta' => 'O que fazer em uma situação de primeiros socorros?',
            'avaliacao_opcoes' => ['Chamar ajuda e seguir o procedimento interno', 'Improvisar sem orientação', 'Ignorar a ocorrência', 'Deixar a pessoa sozinha'],
            'avaliacao_resposta_correta' => 0,
        ]);
    }
}
