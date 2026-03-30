<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePppUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email.email' => 'Format :attribute tidak valid.',
            'ppp_password.required_if' => 'Password PPP wajib diisi jika metode login Username & Password.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ppp_profile_id' => 'Paket Langganan',
            'customer_id' => 'ID Pelanggan',
            'customer_name' => 'Nama Pelanggan',
            'nomor_hp' => 'Nomor HP',
            'ppp_password' => 'Password PPP',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status_registrasi' => ['required', 'string', 'in:aktif,on_process'],
            'tipe_pembayaran' => ['required', 'string', 'in:prepaid,postpaid'],
            'status_bayar' => ['required', 'string', 'in:sudah_bayar,belum_bayar'],
            'status_akun' => ['required', 'string', 'in:enable,disable,isolir'],
            'ppp_profile_id' => ['required', 'integer', 'exists:ppp_profiles,id'],
            'tipe_service' => ['required', 'string', 'in:pppoe,l2tp_pptp,openvpn_sstp'],
            'tagihkan_ppn' => ['sometimes', 'boolean'],
            'prorata_otomatis' => ['sometimes', 'boolean'],
            'promo_aktif' => ['sometimes', 'boolean'],
            'durasi_promo_bulan' => ['nullable', 'integer', 'min:0'],
            'biaya_instalasi' => ['nullable', 'numeric', 'min:0'],
            'jatuh_tempo' => ['nullable', 'date'],
            'aksi_jatuh_tempo' => ['required', 'string', 'in:isolir,tetap_terhubung'],
            'tipe_ip' => ['required', 'string', 'in:dhcp,static'],
            'profile_group_id' => ['nullable', 'integer', 'exists:profile_groups,id'],
            'odp_id' => ['nullable', 'integer', 'exists:odps,id'],
            'ip_static' => ['nullable', 'string', 'max:120'],
            'odp_pop' => ['nullable', 'string', 'max:120'],
            'customer_id' => ['nullable', 'string', 'max:120', Rule::unique('ppp_users', 'customer_id')],
            'customer_name' => ['required', 'string', 'max:150'],
            'nik' => ['nullable', 'string', 'max:191'],
            'nomor_hp' => ['required', 'string', 'max:30', Rule::unique('ppp_users', 'nomor_hp')],
            'email' => ['nullable', 'email', 'max:191'],
            'alamat' => ['nullable', 'string'],
            'latitude' => ['nullable', 'string', 'max:120'],
            'longitude' => ['nullable', 'string', 'max:120'],
            'location_accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'location_capture_method' => ['nullable', 'string', 'in:gps,map_picker,manual'],
            'location_captured_at' => ['nullable', 'date'],
            'metode_login' => ['required', 'string', 'in:username_password,username_equals_password'],
            'username' => ['required', 'string', 'max:120', Rule::unique('ppp_users', 'username')],
            'ppp_password' => ['nullable', 'string', 'max:120', 'required_if:metode_login,username_password'],
            'password_clientarea' => ['nullable', 'string', 'max:120'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
