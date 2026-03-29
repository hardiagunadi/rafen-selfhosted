<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOltConnectionRequest extends FormRequest
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
        return [
            'vendor' => ['required', 'in:hsgq'],
            'name' => ['required', 'string', 'max:150'],
            'olt_model' => ['nullable', 'string', 'max:120'],
            'host' => ['required', 'string', 'max:191'],
            'snmp_port' => ['required', 'integer', 'between:1,65535'],
            'snmp_version' => ['required', 'in:2c'],
            'snmp_community' => ['required', 'string', 'max:191'],
            'snmp_write_community' => ['nullable', 'string', 'max:191'],
            'snmp_timeout' => ['required', 'integer', 'between:1,30'],
            'snmp_retries' => ['required', 'integer', 'between:0,5'],
            'is_active' => ['sometimes', 'boolean'],
            'oid_serial' => ['nullable', 'regex:/^[0-9]+(?:\\.[0-9]+)*$/'],
            'oid_onu_name' => ['nullable', 'regex:/^[0-9]+(?:\\.[0-9]+)*$/'],
            'oid_rx_onu' => ['nullable', 'regex:/^[0-9]+(?:\\.[0-9]+)*$/'],
            'oid_tx_onu' => ['nullable', 'regex:/^[0-9]+(?:\\.[0-9]+)*$/'],
            'oid_rx_olt' => ['nullable', 'regex:/^[0-9]+(?:\\.[0-9]+)*$/'],
            'oid_tx_olt' => ['nullable', 'regex:/^[0-9]+(?:\\.[0-9]+)*$/'],
            'oid_distance' => ['nullable', 'regex:/^[0-9]+(?:\\.[0-9]+)*$/'],
            'oid_status' => ['nullable', 'regex:/^[0-9]+(?:\\.[0-9]+)*$/'],
            'oid_reboot_onu' => ['nullable', 'regex:/^[0-9]+(?:\\.[0-9]+)*$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama koneksi OLT wajib diisi.',
            'host.required' => 'Host / IP OLT wajib diisi.',
            'snmp_community.required' => 'SNMP community wajib diisi.',
            'oid_serial.regex' => 'OID serial ONU harus berupa angka dengan pemisah titik.',
            'oid_onu_name.regex' => 'OID nama ONU harus berupa angka dengan pemisah titik.',
            'oid_rx_onu.regex' => 'OID Rx ONU harus berupa angka dengan pemisah titik.',
            'oid_tx_onu.regex' => 'OID Tx ONU harus berupa angka dengan pemisah titik.',
            'oid_rx_olt.regex' => 'OID Rx OLT harus berupa angka dengan pemisah titik.',
            'oid_tx_olt.regex' => 'OID Tx OLT harus berupa angka dengan pemisah titik.',
            'oid_distance.regex' => 'OID distance harus berupa angka dengan pemisah titik.',
            'oid_status.regex' => 'OID status ONU harus berupa angka dengan pemisah titik.',
            'oid_reboot_onu.regex' => 'OID reboot ONU harus berupa angka dengan pemisah titik.',
        ];
    }
}
