<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Lead;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class FulfillPurchase
{
    public function handle(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            if ($purchase->paid_at) {
                return;
            }
            $lead = Lead::whereKey($purchase->lead_id)->lockForUpdate()->firstOrFail();
            $purchase->paid_at = now();
            $purchase->save();
            if (! $lead->paid_at) {
                $lead->paid_at = now();
                $lead->save();
            }
            AnalyticsEvent::record($lead->assignment_id, 'paid');
        });
    }
}
