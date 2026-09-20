<?php

namespace Tests\Unit;

use App\Http\Requests\Refund\StoreRefundRequest;
use App\Http\Requests\Refund\UpdateRefundDecisionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefundPercentageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Business rule: a refund is between 50% and 100% of the payment.
     * Only the "percentage" field is checked here, so the other fields
     * do not need to exist in the database.
     */
    private function storeHasPercentageError(mixed $percentage): bool
    {
        $request = new StoreRefundRequest();

        $validator = Validator::make(
            [
                'payment_id' => Str::uuid()->toString(),
                'percentage' => $percentage,
            ],
            $request->rules()
        );

        return $validator->errors()->has('percentage');
    }

    private function decisionHasPercentageError(array $data): bool
    {
        $request = new UpdateRefundDecisionRequest();

        $validator = Validator::make($data, $request->rules());

        return $validator->errors()->has('percentage');
    }

    // --- POST /refunds ---

    public function test_store_accepts_50_percent(): void
    {
        $this->assertFalse($this->storeHasPercentageError(50));
    }

    public function test_store_accepts_100_percent(): void
    {
        $this->assertFalse($this->storeHasPercentageError(100));
    }

    public function test_store_accepts_a_value_between_50_and_100(): void
    {
        $this->assertFalse($this->storeHasPercentageError(75.5));
    }

    public function test_store_rejects_less_than_50_percent(): void
    {
        $this->assertTrue($this->storeHasPercentageError(49));
    }

    public function test_store_rejects_more_than_100_percent(): void
    {
        $this->assertTrue($this->storeHasPercentageError(101));
    }

    public function test_store_rejects_a_negative_percentage(): void
    {
        $this->assertTrue($this->storeHasPercentageError(-10));
    }

    public function test_store_rejects_a_non_numeric_percentage(): void
    {
        $this->assertTrue($this->storeHasPercentageError('eighty'));
    }

    public function test_store_allows_no_percentage(): void
    {
        // Without a percentage the controller applies the automatic rule
        $this->assertFalse($this->storeHasPercentageError(null));
    }

    // --- PATCH /refunds/{refund}/decision ---

    public function test_decision_requires_a_percentage(): void
    {
        $this->assertTrue($this->decisionHasPercentageError([]));
    }

    public function test_decision_accepts_50_and_100_percent(): void
    {
        $this->assertFalse($this->decisionHasPercentageError(['percentage' => 50]));
        $this->assertFalse($this->decisionHasPercentageError(['percentage' => 100]));
    }

    public function test_decision_rejects_out_of_range_percentages(): void
    {
        $this->assertTrue($this->decisionHasPercentageError(['percentage' => 49]));
        $this->assertTrue($this->decisionHasPercentageError(['percentage' => 150]));
    }
}
