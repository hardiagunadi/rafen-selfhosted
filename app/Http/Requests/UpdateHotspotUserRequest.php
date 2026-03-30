<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHotspotUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hotspotUser = $this->route('hotspotUser');

        return [
            'status_registrasi' => ['sometimes', 'required', 'string', 'in:aktif,on_process'],
            'tipe_pembayaran' => ['sometimes', 'required', 'string', 'in:prepaid,postpaid'],
            'status_bayar' => ['sometimes', 'required', 'string', 'in:sudah_bayar,belum_bayar'],
            'status_akun' => ['sometimes', 'required', 'string', 'in:enable,disable,isolir'],
            'hotspot_profile_id' => ['sometimes', 'required', 'integer', 'exists:hotspot_profiles,id'],
            'profile_group_id' => ['nullable', 'integer', 'exists:profile_groups,id'],
            'tagihkan_ppn' => ['sometimes', 'boolean'],
            'biaya_instalasi' => ['nullable', 'numeric', 'min:0'],
            'jatuh_tempo' => ['nullable', 'date'],
            'aksi_jatuh_tempo' => ['sometimes', 'required', 'string', 'in:isolir,tetap_terhubung'],
            'customer_id' => ['nullable', 'string', 'max:120', Rule::unique('hotspot_users', 'customer_id')->ignore($hotspotUser?->id)],
            'customer_name' => ['sometimes', 'required', 'string', 'max:150'],
            'nik' => ['nullable', 'string', 'max:50'],
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'alamat' => ['nullable', 'string'],
            'metode_login' => ['sometimes', 'required', 'string', 'in:username_password,username_equals_password'],
            'username' => ['sometimes', 'required', 'string', 'max:120', Rule::unique('hotspot_users', 'username')->ignore($hotspotUser?->id)],
            'hotspot_password' => ['nullable', 'string', 'max:120', 'required_if:metode_login,username_password'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
