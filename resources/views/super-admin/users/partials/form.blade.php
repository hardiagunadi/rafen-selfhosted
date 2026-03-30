<div class="form-row">
    <div class="form-group col-md-6">
        <label for="name">Nama</label>
        <input id="name" type="text" name="name" value="{{ old('name', $user?->name) }}" class="form-control" required>
    </div>
    <div class="form-group col-md-6">
        <label for="nickname">Nama Panggilan</label>
        <input id="nickname" type="text" name="nickname" value="{{ old('nickname', $user?->nickname) }}" class="form-control">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" class="form-control" required>
    </div>
    <div class="form-group col-md-6">
        <label for="phone">Nomor HP / WhatsApp</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $user?->phone) }}" class="form-control">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="role">Role</label>
        <select id="role" name="role" class="form-control" required>
            @foreach($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user?->role ?? 'administrator') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="password">Password {{ $user ? '(Opsional)' : '' }}</label>
        <input id="password" type="password" name="password" class="form-control" {{ $user ? '' : 'required' }}>
        <small class="form-text text-muted">{{ $user ? 'Kosongkan jika tidak ingin mengganti password.' : 'Minimal 8 karakter.' }}</small>
    </div>
</div>
