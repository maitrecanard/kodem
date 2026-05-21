<?php

namespace Tests\Unit;

use App\Models\Audit;
use Tests\TestCase;

class AuditPaymentStateTest extends TestCase
{
    public function test_an_audit_is_not_paid_without_a_paid_at_timestamp(): void
    {
        $audit = new Audit(['url' => 'https://exemple.fr', 'price_cents' => 2900]);

        $this->assertFalse($audit->isPaid());
    }

    public function test_an_audit_is_paid_once_paid_at_is_set(): void
    {
        $audit = new Audit(['url' => 'https://exemple.fr', 'price_cents' => 2900]);
        $audit->paid_at = now();

        $this->assertTrue($audit->isPaid());
    }

    public function test_pdf_and_cwv_add_ons_have_independent_paid_states(): void
    {
        $audit = new Audit(['url' => 'https://exemple.fr']);

        $this->assertFalse($audit->isPdfPaid());
        $this->assertFalse($audit->isCwvPaid());

        $audit->pdf_paid_at = now();
        $this->assertTrue($audit->isPdfPaid());
        $this->assertFalse($audit->isCwvPaid());
    }
}
