<?php

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Enums\ExpenseType;
use App\Models\Expense;
use App\Models\Period;
use App\Repositories\ExpenseRepository;
use App\Repositories\ExpenseTemplateRepository;
use App\Support\Money;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
        private readonly ExpenseTemplateRepository $templates,
    ) {}

    /**
     * @return Collection<int, Expense>
     */
    public function forPeriod(Period $period): Collection
    {
        return $this->expenses->forPeriod($period);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerVariable(Period $period, array $data): Expense
    {
        $this->assertOpen($period);

        $amount = Money::normalize($data['amount']);

        if (! Money::isPositive($amount)) {
            throw new DomainException('El importe del gasto debe ser mayor que cero.');
        }

        return $this->expenses->create([
            'period_id' => $period->id,
            'expense_template_id' => null,
            'description' => $data['description'],
            'type' => ExpenseType::Variable,
            'planned_amount' => $amount,
            'actual_amount' => $amount,
            'expense_date' => $data['expense_date'] ?? now()->toDateString(),
            'payment_number' => $data['payment_number'] ?? 1,
            'status' => $data['status'] ?? ExpenseStatus::Paid,
        ]);
    }

    public function markPaid(Expense $expense): Expense
    {
        $this->assertOpen($expense->period);

        if ($expense->status === ExpenseStatus::Paid) {
            throw new DomainException('El gasto ya fue marcado como pagado.');
        }

        return $this->expenses->update($expense, [
            'actual_amount' => $expense->actual_amount ?? $expense->planned_amount,
            'expense_date' => $expense->expense_date ?? now()->toDateString(),
            'status' => ExpenseStatus::Paid,
        ]);
    }

    /**
     * Genera los gastos fijos desde las plantillas activas. Es idempotente.
     */
    public function generateFixed(Period $period): int
    {
        if ($period->isClosed()) {
            return 0;
        }

        return DB::transaction(function () use ($period) {
            $created = 0;

            foreach ($this->templates->active() as $template) {
                $payments = $template->frequency === 'biweekly' ? [1, 2] : [$template->payment_number];

                foreach ($payments as $paymentNumber) {
                    if ($this->expenses->existsForTemplate($period, $template, $paymentNumber)) {
                        continue;
                    }

                    $this->expenses->create([
                        'period_id' => $period->id,
                        'expense_template_id' => $template->id,
                        'description' => $template->description,
                        'type' => ExpenseType::Fixed,
                        'planned_amount' => $template->amount,
                        'payment_number' => $paymentNumber,
                        'status' => ExpenseStatus::Pending,
                    ]);

                    $created++;
                }
            }

            return $created;
        });
    }

    public function delete(Expense $expense): void
    {
        $this->assertOpen($expense->period);

        $expense->delete();
    }

    private function assertOpen(Period $period): void
    {
        if ($period->isClosed()) {
            throw new DomainException('El período está cerrado y no admite modificaciones.');
        }
    }
}
