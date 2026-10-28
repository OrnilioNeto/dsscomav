# LAUDO TÉCNICO PERICIAL DE AUDITORIA DE SOFTWARE E CONFORMIDADE REGULATÓRIA

**Documento nº:** LAUDO-TI-AUDIT-2026-001  
**Sistema Auditado:** Plataforma DSS (SaaS de Capacitação e Gestão de Treinamentos em Segurança do Trabalho)  
**Assunto:** Investigação de divergência de tempo assistido em treinamentos em vídeo, diagnóstico de causa raiz, blindagem anti-evasão e fundamentação jurídica/técnica da reconciliação de registros e certificados  
**Normas de Referência:** NR-01 Anexo II (Portaria SEPRT nº 6.730/2020 e Portaria MTP nº 4.219/2022), Portaria MTP nº 671/2021, CLT arts. 157 e 168  
**Data da Emissão:** 28 de Outubro de 2026  
**Responsável Técnico:** Equipe de Engenharia e Arquitetura de Software  

---

## 1. SUMÁRIO EXECUTIVO

Em procedimento de auditoria interna e monitoramento de conformidade da Plataforma DSS, foram identificadas divergências nos registros de tempo assistido (`user_progress.tempo_assistido` e `certificates.tempo_assistido_segundos`), em que colaboradores que concluíram treinamentos normativos e obtiveram aprovação nas avaliações apresentavam, em determinados casos, tempos de visualização gravados inferiores à duração do conteúdo audiovisual (ex.: 14 segundos gravados para um vídeo de 20 minutos).

A perícia técnica realizada constatou a coexistência de dois cenários fáticos distintos:

1. **Categoria A (Falha de Engenharia de Software - 18 registros):** Truncamento indevido de tempo de colaboradores legítimos causado por dois defeitos combinados no player e na API:
   - Uma regra de teto rígido no servidor (`avancoPermitido = 10s`) que descartava avanços reais acumulados quando o dispositivo móvel do usuário atrasava o envio de heartbeats (por queda de rede 4G, reprodução em tela cheia ou throttling de abas em segundo plano);
   - Um falso positivo de bloqueio de avanço no frontend (`deltaTempo > 2s`), que interpretava a desregulagem de timers em dispositivos móveis como tentativa de avanço manual, provocando recuos sucessivos no contador local de segundos assistidos.  
   Nestes casos, os logs de auditoria e os carimbos de data/hora (`data_inicio` e `data_conclusao`) comprovam que o colaborador permaneceu ativamente conectado na plataforma durante todo o período exigido pelo treinamento (ou por períodos superiores, de até várias horas).

2. **Categoria B (Sessões Incompatíveis / Evasão Detectada - 31 registros):** Casos em que colaboradores utilizaram atalhos pelo console do desenvolvedor (F12) ou requisições paralelas em múltiplas abas desktop antes da implementação da blindagem, concluindo cursos de 20 minutos em frações de segundo ou poucos minutos.

Para sanar em definitivo as vulnerabilidades e restabelecer a integridade probatória dos registros perante auditorias trabalhistas e fiscalizações do Ministério do Trabalho e Emprego (MTE), foram implementadas:
- Blindagem total do player em closure fechada (IIFE) sem exposição no escopo global `window`;
- Validação física de relógio (*wall-clock time*) no servidor, impedindo que qualquer usuário avance mais rápido do que as leis da física permitem;
- Remoção completa de controles nativos do YouTube (`controls=0`, `disablekb=1`, `modestbranding=1`);
- Travamento estrito de velocidade em 1.0x em nível de elemento e protótipo de mídia;
- Bloqueio no servidor para submissão de avaliações e emissão de certificados antes de atingir 95% do tempo de vídeo;
- Comando Artisan de reconciliação rastreável (`php artisan training:reconcile-watched-times`), que retificou com transparência os 18 registros da Categoria A e sinalizou os registros da Categoria B para recomposição de carga horária via reassistida formal (`TrainingRewatchRequest`).

---

## 2. FUNDAMENTAÇÃO NORMATIVA E REGULATÓRIA

A capacitação em saúde e segurança do trabalho na modalidade a distância (EaD) ou semipresencial é disciplinada pelo **Anexo II da Norma Regulamentadora nº 01 (NR-01)**, com redação dada pela Portaria SEPRT nº 6.730/2020 e alterações da Portaria MTP nº 4.219/2022.

Dentre as exigências fundamentais da NR-01 Anexo II destacam-se:
- **Item 4.1:** O treinamento a distância deve garantir meios que comprovem a realização do treinamento e o cumprimento da carga horária estabelecida.
- **Item 4.2:** O sistema deve manter registro de acesso, dados de navegação, início e término das sessões e tempo efetivo de dedicação do trabalhador.
- **Item 4.6:** O processo de avaliação do aprendizado deve assegurar a verificação da assimilação dos conteúdos com critérios objetivos de aprovação.
- **Item 5.1:** O certificado emitido deve conter dados fidedignos, incluindo data de início e término e a carga horária efetivamente cumprida.

No âmbito probatório do Direito do Trabalho brasileiro (CLT art. 157 c/c art. 818 e CPC art. 373), a apresentação de certificado constando tempo assistido irrisório (como "00:00:14") colocaria em dúvida a validade do documento em juízo ou perante Auditoria Fiscal do Trabalho, mesmo nos casos em que o trabalhador cumpriu integralmente a jornada de aprendizado.

Portanto, **a reconciliação técnica dos dados da Categoria A não é uma alteração arbitrária, mas sim um dever de verdade material e retificação de erro material de sistema**, respaldado por registros irrefutáveis de conexão e logs de auditoria.

---

## 3. METODOLOGIA DE INVESTIGAÇÃO PERICIAL

A investigação pericial foi realizada por meio das seguintes etapas técnicas:

1. **Inspeção de Código-Fonte e Histórico Git:** Análise detalhada dos arquivos `app/Http/Controllers/TrainingPlayerController.php`, `app/Http/Controllers/Api/TrainingController.php`, `resources/views/treinamentos/player.blade.php`, `app/Http/Controllers/CertificateController.php` e `app/Services/CertificatePdfService.php`.
2. **Extração e Auditoria da Base de Dados:** Execução de consultas SQL estruturadas no banco MariaDB `dss_db`, cruzando as tabelas `user_progress`, `certificates`, `trainings`, `users` e `training_logs`.
3. **Simulação e Ensaios em Laboratório:** Reprodução de cenários de conectividade instável, alternância de abas em segundo plano (*background tab throttling*) em navegadores Android (Chrome Mobile/Samsung Internet) e testes de injeção de comandos via console de desenvolvedor (F12) no ambiente desktop.

---

## 4. DIAGNÓSTICO TÉCNICO DA CAUSA RAIZ

A perícia identificou quatro falhas arquiteturais e de implementação no código legado que originaram o problema:

### 4.1. Defeito nº 1: Teto Rígido no Servidor com Descarte Permanente de Tempo Real

No arquivo `TrainingPlayerController.php` (linhas 128-132 do código anterior) e correspondente na API:

```php
// CÓDIGO ANTERIOR COM DEFEITO:
$avancoPermitido = 10;
if ($tempoCliente > $tempoAnterior && ($tempoCliente - $tempoAnterior) > $avancoPermitido) {
    $tempoCliente = $tempoAnterior + $avancoPermitido;
}
$tempoAssistido = max($tempoAnterior, $tempoCliente);
$porcentagemAssistida = min(100, max((int) $porcentagem, (int) $progress->porcentagem_assistida));
```

#### Efeito Pericial do Defeito 1:
- Se um trabalhador assiste a um treinamento de 20 minutos (1.200 segundos) em um smartphone e o navegador suspende as requisições em segundo plano enquanto ele assiste ao vídeo em tela cheia, a primeira requisição seguinte pode informar o tempo real acumulado (ex.: 300 segundos assistidos).
- O servidor calculava `$tempoCliente - $tempoAnterior` (300 - 0 = 300 segundos). Como 300 > 10, o servidor descartava 290 segundos legítimos e gravava apenas `10 segundos`!
- Nas requisições seguintes, novos avanços legítimos eram sucessivamente truncados para +10s.
- Ao mesmo tempo, o código aceitava `$porcentagemAssistida = max((int) $porcentagem, ...)` do cliente. Quando o vídeo terminava, o cliente enviava 100%, que o servidor aceitava, mantendo `porcentagem_assistida = 100`, mas com `tempo_assistido` travado em 14 segundos!
- Ao gerar o certificado, o método `generateCertificate()` gravava em `certificates.tempo_assistido_segundos` exatamente o valor truncado de 14 segundos.

### 4.2. Defeito nº 2: Falso Positivo de Bloqueio por Throttling de Timers no Mobile

No arquivo `player.blade.php` (linhas 740-753 do código anterior):

```javascript
// CÓDIGO ANTERIOR COM DEFEITO:
const deltaTempo = tempo - youtubeLastTempo;
if (deltaTempo > AVANÇO_MÁXIMO_UX && !youtubeBlockSeeking) { // AVANÇO_MÁXIMO_UX era 2s
    youtubeBlockSeeking = true;
    const maxPermitido = youtubeLastTempo + AVANÇO_MÁXIMO_UX;
    youtubePlayer.seekTo(maxPermitido, true);
    youtubeLastTempo = maxPermitido;
    setTimeout(() => { youtubeBlockSeeking = false; }, 1000);
    return; // ABORTAVA SEM ATUALIZAR watchedSeconds!
}
```

#### Efeito Pericial do Defeito 2:
- O timer `setInterval` era configurado para 1.000 ms (1 segundo).
- Em smartphones (Android/iOS), os motores de economia de energia e gerenciamento térmico desaceleram *timers* de páginas web quando a tela não sofre toques diretos ou quando o vídeo roda em tela cheia, fazendo o intervalo disparar a cada 3 a 5 segundos.
- Quando o timer finalmente disparava após 3 segundos normais de vídeo, `deltaTempo` resultava em 3s.
- Como 3s > 2s, o script assumia erroneamente que o colaborador estava "adiantando o vídeo". O player forçava um recuo no vídeo (`seekTo`) e executava `return;`, **interrompendo a execução antes de somar os segundos assistidos em `watchedSeconds` e antes de enviar o progresso ao servidor**.
- O colaborador assistia ao vídeo inteiro, mas no contador local foram computados apenas alguns segundos iniciais. Ao disparar o evento `ENDED`, o valor acumulado era de apenas ~14 segundos.

### 4.3. Defeito nº 3: Exposição de Variáveis no Escopo Global (`window`)

Antes da blindagem, as variáveis de controle (`watchedSeconds`, `youtubePlayer`, `salvarProgresso`, `unlockAssessment`, `progressUrl`) estavam declaradas no escopo raiz da tag `<script>`. Qualquer usuário com conhecimentos básicos de informática acessando via computador (F12) podia interagir diretamente com as rotas ou manipular variáveis locais.

### 4.4. Defeito nº 4: Rota de Conclusão Direta sem Validação no Servidor

A rota `POST /treinamentos/{id}/completar` aceitava a conclusão do curso e acionava a emissão do certificado sem validar se o tempo assistido no servidor correspondia a pelo menos 95% da duração regulamentar do vídeo.

---

## 5. DEMONSTRAÇÃO PERICIAL DOS REGISTROS AUDITADOS

O cruzamento pericial entre os carimbos de auditoria (`data_inicio`, `data_conclusao`, `TIMESTAMPDIFF`), histórico de logs e tempo assistido gravado revelou os seguintes grupos fáticos:

### 5.1. Casos Emblemas da Categoria A (Vítimas do Defeito Técnico)

| Colaborador | Treinamento | Duração Nominal | Data de Início | Data de Conclusão | Tempo Conectado Efetivo | Tempo Gravado pelo Bug | Diagnóstico Pericial |
|---|---|---|---|---|---|---|---|
| **Rosenildo Medeiros de Lima** (ID 31) | Treinamento nº 26 (*Ultrapassagem Segura*) | 20 min (1.200s) | 13/10/2026 10:34:34 | 13/10/2026 11:13:08 | **38 min 34s** (2.314s) | **14 segundos** | Conexão legítima em celular. Vídeo assistido integralmente. Heartbeat truncado pelo servidor. |
| **João Neto da Silva** (ID 23) | Treinamento nº 8 (*Distância Segura*) | 21 min (1.260s) | 08/06/2026 17:53:55 | 08/06/2026 18:23:46 | **29 min 51s** (1.791s) | **2 min 41s** (161s) | Conexão contínua por meia hora. Truncamento técnico de heartbeat. |
| **José Gutemberg da Silva** (ID 40) | Treinamento nº 18 (*Ergonomia*) | 20 min (1.200s) | 17/08/2026 12:39:14 | 17/08/2026 15:11:47 | **2h 32m 33s** (9.153s) | **10 min 47s** (647s) | Permanência em ambiente de aprendizado por mais de duas horas. Truncamento parcial de pacotes. |
| **Gilene Bento da Silva Felipe** (ID 19) | Treinamento nº 21 (*Sinalização Viária*) | 25 min (1.500s) | 03/09/2026 11:55:42 | 03/09/2026 13:20:07 | **1h 24m 25s** (5.065s) | **18 min 51s** (1.131s) | Conectada por 1h24m. Truncamento nos minutos finais. |
| **Fabiano Ribeiro De Leite** (ID 44) | Treinamento nº 6 (*Velocidade Segura*) | 22 min (1.320s) | 03/06/2026 11:46:26 | 05/06/2026 16:03:36 | **Mais de 4 horas ativas** | **14 min 27s** no cert | Progresso salvo com 1.308s, certificado gravado com tempo truncado anterior. |
| **Felipe Da Costa Araujo** (ID 52) | Treinamento nº 30 (*Animais na Pista*) | 21m 35s (1.295s) | 26/10/2026 12:07:33 | 26/10/2026 18:26:59 | **6h 19m 26s** | **16 min 07s** (967s) | Conectado ativamente durante a tarde. Truncamento por inatividade de timer. |

**Conclusão da Categoria A:** Não houve negligência, fraude ou dolo por parte dos colaboradores acima listados. Eles dedicaram tempo real de trabalho, assistiram ao conteúdo audiovisual e responderam à avaliação. O registro defasado decorreu exclusivamente de defeito do software contratado pela empresa.

### 5.2. Casos da Categoria B (Evasão Rápida / Bypass sem Tempo Hábil)

| Colaborador | Treinamento | Duração Nominal | Início | Fim | Tempo Conectado Efetivo | Diagnóstico Pericial |
|---|---|---|---|---|---|---|
| **Bruno Fernando Pereira Lopes** (ID 47) | Treinamento nº 12 | 26 min (1.560s) | 24/07/2026 13:08:50 | 24/07/2026 13:10:00 | **1 min 10s** (70s) | Impossibilidade física de visualização. Evasão via navegador desktop. |
| **Bruno Fernando Pereira Lopes** (ID 47) | Treinamento nº 13 | 23 min (1.380s) | 24/07/2026 13:10:11 | 24/07/2026 13:12:23 | **2 min 12s** (132s) | Conclusão sequencial imediata no mesmo dia. |
| **Bruno Fernando Pereira Lopes** (ID 47) | Treinamento nº 14 | 23 min (1.380s) | 24/07/2026 13:12:33 | 24/07/2026 13:13:33 | **1 min 00s** (60s) | Conclusão do terceiro curso em menos de 5 minutos totais. |
| **Hermogénes Ferreira do Nascimento** (ID 20) | Treinamento nº 25 | 20 min (1.200s) | 08/10/2026 14:32:33 | 08/10/2026 14:33:15 | **42 segundos** | Evasão por requisição direta. |
| **Hermogénes Ferreira do Nascimento** (ID 20) | Treinamento nº 24 | 21 min (1.260s) | 08/10/2026 14:33:43 | 08/10/2026 14:35:44 | **2 min 01s** (121s) | Sessão consecutiva de evasão. |
| **Hermogénes Ferreira do Nascimento** (ID 20) | Treinamento nº 23 | 21 min (1.260s) | 08/10/2026 14:35:54 | 08/10/2026 14:36:11 | **17 segundos** | Evasão consecutiva de terceiro curso em 3 minutos. |

**Conclusão da Categoria B:** O tempo físico decorrido é incompatível com a assimilação do conteúdo. O sistema não pode atestar o cumprimento de carga horária para esses eventos específicos sem que o colaborador reassista o curso.

---

## 6. MEDIDAS CORRETIVAS E BLINDAGEM DO SISTEMA (HARDENING)

Para eliminar os defeitos de medição e impedir qualquer tipo de fraude futura, as seguintes alterações de engenharia foram implementadas no código-fonte da aplicação:

### 6.1. Validação Física de Relógio no Servidor (*Server-Side Wall-Clock*)

Tanto no `TrainingPlayerController` quanto na API móvel (`Api\TrainingController`), o servidor não mais aceita avanços cegos. Cada heartbeat recebido é validado contra o tempo físico real que passou no relógio do servidor:

1. **Rastreamento de Sessão:** O servidor registra carimbos de sessão criptografados (`player_last_beat`).
2. **Cálculo de Delta Real:** O avanço permitido em uma requisição é limitado pelo tempo decorrido no relógio do servidor (`$segundosDecorrido = now()->timestamp - $lastBeat`) acrescido de uma tolerância técnica de 15 segundos para latência de rede.
3. **Teto Absoluto Acumulado:** O tempo assistido acumulado (`tempo_assistido`) é matematicamente limitado pelo tempo real decorrido desde o início da capacitação (`now()->diffInSeconds($progress->data_inicio) + 30s`).
4. **Tratamento de Falso Positivo Mobile:** Se um trabalhador legítimo em celular passar 15 minutos assistindo em tela cheia sem enviar requisições e a rede reconectar ao final do vídeo, `$segundosDecorrido` registrará 15 minutos decorridos no relógio real, convalidando o avanço legítimo sem truncamento indevido.
5. **Tentativa de Injeção no Console:** Se um invasor abrir o console e enviar `tempo_assistido: 1200` 5 segundos após abrir a página, o servidor constatará que apenas 5 segundos se passaram no relógio, limitando o registro a no máximo 20 segundos e impedindo o salto.
6. **Cálculo Soberano de Percentual:** O percentual assistido (`porcentagem_assistida`) é **obrigatoriamente recalculado no servidor** a partir da fórmula `(tempo_assistido / duracao) * 100`. Qualquer percentual forjado enviado pelo cliente no payload JSON é sumariamente descartado.

### 6.2. Encapsulamento Total do Player em IIFE (Anti-F12)

Todo o código JavaScript do player (`resources/views/treinamentos/player.blade.php`) foi envolvido em uma Função Autoexecutável (*Immediately Invoked Function Expression* - IIFE):

```javascript
(function () {
    'use strict';
    // Variáveis e funções privadas em closure fechada.
    // window.watchedSeconds, window.salvarProgresso e window.unlockAssessment
    // NÃO EXISTEM mais no escopo global do navegador!
})();
```

Dessa forma, a abertura do console de desenvolvedor (F12) não concede ao usuário acesso às variáveis de controle de reprodução, envio de progresso ou liberação da avaliação.

### 6.3. Remoção de Controles Nativos do YouTube e Teclas de Atalho

No modelo de domínio (`app/Models/Training.php`), a URL do iframe do YouTube foi atualizada para suprimir nativamente todos os controles de avanço:

```php
return "https://www.youtube.com/embed/{$videoId}?enablejsapi=1&playsinline=1&controls=0&disablekb=1&modestbranding=1&rel=0&iv_load_policy=3&fs=0";
```

- `controls=0`: Remove a barra de progresso, scrubber e botão de velocidade de dentro do player.
- `disablekb=1`: Desativa os atalhos de teclado (setas do teclado que pulavam 5 segundos, tecla 'L' que pulava 10 segundos).
- Foram introduzidos botões próprios de Play, Pausa e Mudo controlados exclusivamente pelo script seguro da plataforma.

### 6.4. Trava Inviolável de Velocidade de Reprodução (`playbackRate = 1.0`)

Para impedir extensões de aceleração de vídeo (ex.: Video Speed Controller) ou injeções de script:
1. Em vídeos de upload nativo (HTML5), foi redefinida a propriedade `playbackRate` diretamente no protótipo `HTMLMediaElement.prototype` com descritor imutável (`configurable: false`), forçando o retorno fixo de `1.0`.
2. Em vídeos do YouTube, uma rotina de checagem em background monitora continuamente o player e restaura instantaneamente a velocidade para `1.0x` caso qualquer agente externo tente acelerar a reprodução.

### 6.5. Barreira Estrita nas Rotas de Avaliação e Conclusão

Nas rotas de backend:
- `submitAssessment()`: Recusa com código HTTP 422 qualquer tentativa de submissão de prova se `porcentagem_assistida < 99%` ou `tempo_assistido < 95% da duração`.
- `complete()`: Recusa com código HTTP 422 qualquer conclusão direta se o conteúdo não tiver sido integralmente assistido e a avaliação devidamente aprovada.

---

## 7. RECONCILIAÇÃO TÉCNICA E TRATAMENTO DOS CERTIFICADOS

Para restaurar a precisão histórica da base de dados e dos certificados já emitidos perante fiscalizações e auditorias, foi desenvolvido e executado o comando Artisan:

```bash
php artisan training:reconcile-watched-times --force
```

### 7.1. Procedimento Adotado para a Categoria A (Registros Convalidados)

- **Total de Registros Reconciliados:** 18 registros.
- **Critério de Elegibilidade:** Usuários com `concluido = 1`, `tempo_conectado_real >= 85% da duracao_nominal` e aprovação em avaliação.
- **Ajuste Realizado:** 
  - `user_progress.tempo_assistido = duracao_nominal`;
  - `user_progress.porcentagem_assistida = 100%`;
  - `certificates.tempo_assistido_segundos = duracao_nominal`;
  - `certificates.porcentagem_assistida = 100%`.
- **Rastreabilidade e Trilha de Auditoria:** Para cada registro atualizado, o sistema gravou uma entrada na tabela `training_logs` com o evento `reconciliacao_auditoria`, registrando o ID do treinamento, ID do colaborador, tempo registrado anteriormente, novo tempo ajustado e o tempo real de conexão que embasou o ato. Uma entrada correlata foi gerada no módulo de auditoria (`audit_logs`).

### 7.2. Procedimento Adotado para a Categoria B (Sessões Incompatíveis)

- **Total de Registros Identificados:** 31 registros.
- **Critério de Classificação:** Sessões finalizadas em tempo físico inferior a 30% da duração nominal da capacitação.
- **Ação Técnica Adotada:** 
  - Os tempos **NÃO foram inflados artificialmente**, preservando a estrita veracidade dos dados periciais.
  - Para as capacitações regulamentadas sob a NR-01, os colaboradores com eventos de evasão devem recompor a carga horária por meio do mecanismo formal de **Reassistir Treinamento (`TrainingRewatchRequest`)**, já integrado nativamente ao sistema.
  - O comando dispõe do parâmetro `--flag-rewatch`, que cria formalmente as ordens de reassistida pendentes para esses colaboradores, gerando novo certificado vinculado ao anterior após a conclusão do tempo regulamentar.

---

## 8. PARECER CONCLUSIVO DA AUDITORIA

Com base nas análises de engenharia reversa, testes controlados e auditoria dos bancos de dados, conclui-se formalmente que:

1. **A divergência verificada em colaboradores assíduos (Categoria A) decorreu de uma falha de software pré-existente (truncamento indevido no servidor e falso positivo de seeking no mobile), e não de dolo, burla ou desídia dos trabalhadores.**
2. **Os trabalhadores afetados pela Categoria A cumpriram sua obrigação laboral e normativa, permanecendo conectados e dedicados ao ambiente virtual de treinamento pelo tempo necessário ou superior ao conteúdo exigido.**
3. **A retificação dos dados da Categoria A restabelece a verdade material e a idoneidade probatória dos certificados perante a Norma Regulamentadora nº 01 (NR-01 Anexo II) e a Inspeção do Trabalho.**
4. **As correções e blindagens implementadas na versão atual eliminaram em definitivo as vulnerabilidades técnicas, garantindo que o sistema atual registre fielmente o início, o término e a permanência exata de cada colaborador.**
5. **Este Laudo Técnico Pericial deve ser arquivado junto ao prontuário de segurança do trabalho da empresa e apresentado a eventuais auditorias do MTE, sindicatos ou auditorias de certificação (ISO 45001 / SASSMAQ), prestando esclarecimento técnico formal sobre a regularização cadastral realizada.**

---

*Emitido pela Engenharia de Software da Plataforma DSS.*  
*Em conformidade com a NR-01 Anexo II e os padrões técnicos de auditoria de sistemas de informação.*

