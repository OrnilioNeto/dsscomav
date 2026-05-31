# 📸 Funcionalidade de Foto de Perfil - Guia de Implementação

## ✅ O que foi implementado

### 1. **Banco de Dados**
- ✅ Migração criada: `2026_05_31_000000_add_foto_perfil_to_users`
- ✅ Campo `foto_perfil` adicionado à tabela `users`
- ✅ Tipo: string (armazena nome do arquivo)

### 2. **Modelo (User)**
Métodos adicionados:
- `getFotoPerfilUrl()` - Retorna URL da foto ou avatar genérico colorido
- `getInitials()` - Gera iniciais do nome para o avatar
- `getAvatarColor()` - Cor consistente baseada no ID

### 3. **Controller**
`ProfilePhotoController.php` - Controla:
- Upload de foto (câmera ou galeria)
- Redimensionamento automático para 300x300px
- Conversão para WebP (compressão otimizada)
- Remoção de foto anterior
- Deleção de foto de perfil

### 4. **Rotas**
```php
Route::get('/perfil/editar', [ProfilePhotoController::class, 'edit'])->name('profile.edit');
Route::post('/perfil/foto/upload', [ProfilePhotoController::class, 'upload'])->name('profile.photo.upload');
Route::delete('/perfil/foto/delete', [ProfilePhotoController::class, 'delete'])->name('profile.photo.delete');
```

### 5. **Views**
- **`usuarios/edit-profile.blade.php`** - Página completa de edição de perfil com:
  - Foto de perfil com overlay hover
  - Botão de câmera e galeria
  - Captura de câmera em tempo real
  - Barra de progresso de upload
  - Informações pessoais e profissionais

- **`components/user-avatar.blade.php`** - Componente reutilizável

### 6. **Layout**
Atualidades:
- Menu desktop com foto de perfil
- Menu mobile com link para editar perfil
- Dropdown com informações do usuário

### 7. **Dependências**
- ✅ `intervention/image` (v3.11.8) - Processamento de imagens

## 🎯 Fluxo de Uso

### Acesso
1. Usuário clica na foto de perfil no header
2. Seleciona "Editar Perfil"
3. Abre `/perfil/editar`

### Upload de Foto
**Opção 1 - Câmera:**
- Clica em "Câmera"
- Permite acesso à câmera do dispositivo
- Clica em "Capturar"
- Foto é processada e enviada

**Opção 2 - Galeria:**
- Clica em "Galeria"
- Seleciona foto do dispositivo
- Foto é processada e enviada automaticamente

### Validações
- ✅ Apenas imagens (jpeg, png, jpg, gif, webp)
- ✅ Máximo 5MB
- ✅ Redimensiona automaticamente para 300x300px
- ✅ Converte para WebP (melhor compressão)

## 📁 Estrutura de Diretórios

```
public/
  uploads/
    perfil/
      perfil_1_1234567890.webp
      perfil_2_1234567891.webp
      ...
```

## 🎨 Avatar Padrão

Se o usuário não tiver foto:
- Exibe avatar colorido com iniciais do nome
- Cor baseada no ID (consistente)
- Gerado pelo serviço **ui-avatars.com**

Cores disponíveis:
- Vermelho (#FF6B6B)
- Teal (#4ECDC4)
- Azul (#45B7D1)
- Coral (#FFA07A)
- Verde menta (#98D8C8)
- Amarelo (#F7DC6F)
- Roxo (#BB8FCE)
- Azul claro (#85C1E2)

## 🔒 Segurança

- ✅ Validação de tipo de arquivo
- ✅ Limite de tamanho (5MB)
- ✅ CSRF token obrigatório
- ✅ Autenticação obrigatória
- ✅ Conversão para WebP reduz arquivo
- ✅ Nomes de arquivo randômicos

## 📱 Compatibilidade

- ✅ Desktop (câmera + galeria)
- ✅ Mobile (câmera + galeria nativa)
- ✅ Tablet
- ✅ Dispositivos antigos (fallback para avatar)

## 🚀 Como Usar

### 1. Editar Perfil
```
GET /perfil/editar
```

### 2. Upload de Foto
```
POST /perfil/foto/upload
Content-Type: multipart/form-data

foto: [arquivo de imagem]
```

Resposta:
```json
{
  "success": true,
  "message": "Foto de perfil atualizada com sucesso!",
  "fotoUrl": "https://..."
}
```

### 3. Deletar Foto
```
DELETE /perfil/foto/delete
```

Resposta:
```json
{
  "success": true,
  "message": "Foto de perfil removida!",
  "fotoUrl": "https://ui-avatars.com/api?..."
}
```

## 💡 Dicas Adicionais

- Foto é salva em formato WebP para melhor compressão
- Sempre mantém proporção de 1:1 (quadrado)
- Tamanho final: 300x300px
- Qualidade: 80% (balanço entre qualidade e tamanho)

---

**Implementação concluída! ✅**
