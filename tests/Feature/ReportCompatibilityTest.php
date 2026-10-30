<?php

namespace Tests\Feature;

use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class ReportCompatibilityTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_affiliate_report_works_with_the_configured_mysql_test_database(): void
    {
        $admin = $this->createUser('ADMIN');

        $this->actingAs($admin)
            ->get('/reports/afiliados')
            ->assertOk();
    }
}
