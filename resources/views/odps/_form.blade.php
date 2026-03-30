@php($editing = isset($odp))

<div class="form-row">
    <div class="form-group col-md-3">
        <label for="odp_code">Kode ODP</label>
        <div class="input-group">
            <input type="text" id="odp_code" name="code" class="form-control" value="{{ old('code', $odp->code ?? '') }}">
            <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" data-generate-odp-code>Auto</button>
            </div>
        </div>
    </div>
    <div class="form-group col-md-4">
        <label for="odp_name">Nama ODP</label>
        <input type="text" id="odp_name" name="name" class="form-control" value="{{ old('name', $odp->name ?? '') }}">
    </div>
    <div class="form-group col-md-3">
        <label for="odp_area">Area</label>
        <input type="text" id="odp_area" name="area" class="form-control" value="{{ old('area', $odp->area ?? '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="odp_status">Status</label>
        <select id="odp_status" name="status" class="form-control">
            <option value="active" @selected(old('status', $odp->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $odp->status ?? '') === 'inactive')>Inactive</option>
            <option value="maintenance" @selected(old('status', $odp->status ?? '') === 'maintenance')>Maintenance</option>
        </select>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-3">
        <label for="odp_capacity_ports">Kapasitas Port</label>
        <input type="number" id="odp_capacity_ports" name="capacity_ports" class="form-control" value="{{ old('capacity_ports', $odp->capacity_ports ?? 8) }}">
    </div>
    <div class="form-group col-md-3">
        <label for="odp_latitude">Latitude</label>
        <input type="text" id="odp_latitude" name="latitude" class="form-control" value="{{ old('latitude', $odp->latitude ?? '') }}">
    </div>
    <div class="form-group col-md-3">
        <label for="odp_longitude">Longitude</label>
        <input type="text" id="odp_longitude" name="longitude" class="form-control" value="{{ old('longitude', $odp->longitude ?? '') }}">
    </div>
    @if($editing)
        <div class="form-group col-md-3">
            <label>Pelanggan Terkait</label>
            <input type="text" class="form-control" value="{{ $odp->ppp_users_count ?? 0 }} pelanggan" disabled>
        </div>
    @endif
</div>

<div class="form-group">
    <label for="odp_notes">Catatan</label>
    <textarea id="odp_notes" name="notes" class="form-control" rows="3">{{ old('notes', $odp->notes ?? '') }}</textarea>
</div>
