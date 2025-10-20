# 🔄 Guia de Migração para Spinner Genérico

Este guia mostra como migrar formulários existentes para usar o utilitário genérico de spinner.

## 📋 Passo a Passo

### 1️⃣ Atualizar o Botão de Submit

**Antes:**
```blade
<button type="submit" class="btn btn-success">Cadastrar</button>
```

**Depois:**
```blade
<button type="submit" class="btn btn-success">
    <span class="btn-text">Cadastrar</span>
    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
</button>
```

### 2️⃣ Adicionar data-spinner ao Formulário

**Antes:**
```blade
<form method="POST" action="{{ route('exemplo.store') }}">
    @csrf
    <!-- campos -->
    <button type="submit" class="btn btn-success">Cadastrar</button>
</form>
```

**Depois:**
```blade
<form data-spinner method="POST" action="{{ route('exemplo.store') }}">
    @csrf
    <!-- campos -->
    <button type="submit" class="btn btn-success">
        <span class="btn-text">Cadastrar</span>
        <span class="spinner-border spinner-border-sm d-none"></span>
    </button>
</form>

@vite(['resources/ts/form-spinner.ts'])
```

### 3️⃣ Remover Scripts Específicos (Opcional)

Se você tinha um script TypeScript específico para esse formulário, pode removê-lo e usar apenas o genérico:

**Antes:**
```typescript
// meu-formulario-submit.ts
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const btn = document.getElementById('btn-submit');
    // ... código manual de spinner
});
```

**Depois:**
```blade
<!-- Na view, apenas: -->
@vite(['resources/ts/form-spinner.ts'])
```

## 📦 Exemplos de Migração Real

### Exemplo 1: Formulário de Locatários

**`resources/views/locatarios/create.blade.php` - Antes:**
```blade
<form method="POST" action="{{ route('locatarios.store') }}">
    @csrf
    <input type="text" name="nome" required>
    <!-- mais campos -->
    
    <button type="submit" class="btn btn-success w-100">Cadastrar Locatário</button>
</form>
```

**Depois:**
```blade
<form data-spinner method="POST" action="{{ route('locatarios.store') }}">
    @csrf
    <input type="text" name="nome" required>
    <!-- mais campos -->
    
    <button type="submit" class="btn btn-success w-100">
        <span class="btn-text">Cadastrar Locatário</span>
        <span class="spinner-border spinner-border-sm d-none"></span>
    </button>
</form>

@vite(['resources/ts/form-spinner.ts'])
```

### Exemplo 2: Formulário de Obras

**`resources/views/obras/create.blade.php` - Antes:**
```blade
<form method="POST" action="{{ route('obras.store') }}">
    @csrf
    <input type="text" name="descricao" required>
    <input type="date" name="data_inicio" required>
    
    <button type="submit" class="btn btn-success">Salvar</button>
</form>
```

**Depois:**
```blade
<form data-spinner method="POST" action="{{ route('obras.store') }}">
    @csrf
    <input type="text" name="descricao" required>
    <input type="date" name="data_inicio" required>
    
    <button type="submit" class="btn btn-success">
        <span class="btn-text">Salvar</span>
        <span class="spinner-border spinner-border-sm d-none"></span>
    </button>
</form>

@vite(['resources/ts/form-spinner.ts'])
```

### Exemplo 3: Formulário de Transações

**`resources/views/transacoes/create.blade.php` - Antes:**
```blade
<form method="POST" action="{{ route('transacoes.store') }}">
    @csrf
    <select name="imovel_id" required>
        <!-- opções -->
    </select>
    <input type="number" name="valor_venda" step="0.01" required>
    
    <button type="submit" class="btn btn-success w-100">Registrar Venda</button>
</form>
```

**Depois:**
```blade
<form data-spinner method="POST" action="{{ route('transacoes.store') }}">
    @csrf
    <select name="imovel_id" required>
        <!-- opções -->
    </select>
    <input type="number" name="valor_venda" step="0.01" required>
    
    <button type="submit" class="btn btn-success w-100">
        <span class="btn-text">Registrar Venda</span>
        <span class="spinner-border spinner-border-sm d-none"></span>
    </button>
</form>

@vite(['resources/ts/form-spinner.ts'])
```

## ⚠️ Casos Especiais

### Formulário com Botão Personalizado (não é type="submit")

```blade
<form data-spinner data-spinner-button="custom-action-btn" method="POST">
    @csrf
    <!-- campos -->
    
    <div class="d-flex gap-2">
        <a href="{{ route('home') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" id="custom-action-btn" class="btn btn-primary">
            <span class="btn-text">Processar</span>
            <span class="spinner-border spinner-border-sm d-none"></span>
        </button>
    </div>
</form>

@vite(['resources/ts/form-spinner.ts'])
```

### Múltiplos Formulários na Mesma Página

Todos podem usar `data-spinner`:

```blade
<!-- Formulário 1 -->
<form data-spinner method="POST" action="{{ route('form1.store') }}">
    @csrf
    <button type="submit">
        <span class="btn-text">Enviar 1</span>
        <span class="spinner-border spinner-border-sm d-none"></span>
    </button>
</form>

<!-- Formulário 2 -->
<form data-spinner method="POST" action="{{ route('form2.store') }}">
    @csrf
    <button type="submit">
        <span class="btn-text">Enviar 2</span>
        <span class="spinner-border spinner-border-sm d-none"></span>
    </button>
</form>

<!-- Importar uma vez no final -->
@vite(['resources/ts/form-spinner.ts'])
```

### Formulário com Timeout de Segurança

Para formulários que podem falhar silenciosamente ou em SPAs:

```blade
<form data-spinner data-spinner-timeout="10000" method="POST">
    @csrf
    <!-- O botão será reativado após 10 segundos automaticamente -->
    <button type="submit">
        <span class="btn-text">Processar</span>
        <span class="spinner-border spinner-border-sm d-none"></span>
    </button>
</form>

@vite(['resources/ts/form-spinner.ts'])
```

## ✅ Checklist de Migração

- [ ] Adicionar `data-spinner` ao `<form>`
- [ ] Adicionar `<span class="btn-text">` ao redor do texto do botão
- [ ] Adicionar `<span class="spinner-border spinner-border-sm d-none"></span>` dentro do botão
- [ ] Importar `@vite(['resources/ts/form-spinner.ts'])` no final da view
- [ ] Remover scripts TypeScript específicos (se houver)
- [ ] Testar o formulário no navegador
- [ ] Verificar que o spinner aparece ao clicar em submit
- [ ] Confirmar que o botão fica desabilitado

## 🎯 Benefícios da Migração

| Antes | Depois |
|-------|--------|
| Script específico para cada form | Um script genérico para todos |
| ~20 linhas de código TS por form | 1 linha `@vite` |
| Difícil manter consistência | Padrão uniforme |
| Código duplicado | Código reutilizável |

## 📚 Formulários a Migrar no Projeto

Formulários identificados que podem se beneficiar do spinner genérico:

- [ ] `locatarios/create.blade.php`
- [ ] `locatarios/edit.blade.php`
- [ ] `obras/create.blade.php`
- [ ] `obras/edit.blade.php`
- [ ] `transacoes/create.blade.php`
- [ ] `transacoes/edit.blade.php`
- [ ] `alugueis/create.blade.php`
- [ ] `alugueis/edit.blade.php`
- [ ] `propriedades/edit.blade.php`
- [ ] `account/settings.blade.php` (formulário de senha)
- [x] `imoveis/create.blade.php` ✅ (já migrado)
- [x] `imoveis/edit.blade.php` ✅ (já migrado)
