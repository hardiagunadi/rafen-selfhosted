<?php

namespace App\Services;

use App\Models\OltOnuOptic;
use App\Models\OltOnuOpticHistory;

class OltOnuAlarmEvaluator
{
    /**
     * @return array{
     *     severity: 'none'|'warning'|'critical',
     *     label: string,
     *     summary: string|null,
     *     reasons: list<string>,
     *     current_rx_onu_dbm: float|null,
     *     previous_rx_onu_dbm: float|null,
     *     rx_delta_db: float|null
     * }
     */
    public function evaluate(OltOnuOptic $onu, ?OltOnuOpticHistory $previousHistory = null): array
    {
        $warningThreshold = (float) config('olt.synthetic_alarm.rx_warning_dbm', -27);
        $criticalThreshold = (float) config('olt.synthetic_alarm.rx_critical_dbm', -30);
        $deltaWarningThreshold = (float) config('olt.synthetic_alarm.delta_warning_db', 2.5);
        $deltaCriticalThreshold = (float) config('olt.synthetic_alarm.delta_critical_db', 4.0);

        $severity = 'none';
        $reasons = [];
        $currentRx = $onu->rx_onu_dbm !== null ? (float) $onu->rx_onu_dbm : null;
        $previousRx = $previousHistory?->rx_onu_dbm !== null ? (float) $previousHistory->rx_onu_dbm : null;
        $rxDelta = null;

        if ($onu->status !== null && strcasecmp($onu->status, 'online') !== 0) {
            $severity = 'critical';
            $reasons[] = 'ONU offline.';
        }

        if ($currentRx !== null) {
            if ($currentRx <= $criticalThreshold) {
                $severity = 'critical';
                $reasons[] = 'Rx ONU melewati ambang kritis '.number_format($criticalThreshold, 2).' dBm.';
            } elseif ($currentRx <= $warningThreshold && $severity !== 'critical') {
                $severity = 'warning';
                $reasons[] = 'Rx ONU melewati ambang warning '.number_format($warningThreshold, 2).' dBm.';
            }
        } elseif ($onu->status !== null && strcasecmp($onu->status, 'online') === 0 && $severity !== 'critical') {
            $severity = 'warning';
            $reasons[] = 'Rx ONU belum terbaca pada polling terbaru.';
        }

        if ($currentRx !== null && $previousRx !== null) {
            $rxDelta = round(abs($currentRx - $previousRx), 2);

            if ($rxDelta >= $deltaCriticalThreshold) {
                $severity = 'critical';
                $reasons[] = 'Perubahan redaman mencapai '.number_format($rxDelta, 2).' dB.';
            } elseif ($rxDelta >= $deltaWarningThreshold && $severity !== 'critical') {
                $severity = 'warning';
                $reasons[] = 'Perubahan redaman mencapai '.number_format($rxDelta, 2).' dB.';
            }
        }

        return [
            'severity' => $severity,
            'label' => match ($severity) {
                'critical' => 'Kritis',
                'warning' => 'Warning',
                default => 'Normal',
            },
            'summary' => $reasons[0] ?? null,
            'reasons' => $reasons,
            'current_rx_onu_dbm' => $currentRx,
            'previous_rx_onu_dbm' => $previousRx,
            'rx_delta_db' => $rxDelta,
        ];
    }
}
