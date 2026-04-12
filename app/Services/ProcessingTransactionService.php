<?php

namespace App\Services;

use App\Models\ProcessingInput;
use App\Models\ProcessingOutput;
use App\Models\ProcessingTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\WasteCategory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a waste-processing transaction: inputs (waste taken from inventory)
 * transformed into outputs (products produced). Inputs decrement inventory;
 * outputs increment product stock.
 */
class ProcessingTransactionService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<int, array{waste_category_id: int, quantity: float|string}>  $inputs
     * @param  array<int, array{product_id: int, quantity: float|string}>  $outputs
     */
    public function create(
        array $inputs,
        array $outputs = [],
        ?string $notes = null,
        ?User $createdBy = null,
    ): ProcessingTransaction {
        if (empty($inputs)) {
            throw new InvalidArgumentException('Minimal satu input sampah.');
        }

        return DB::transaction(function () use ($inputs, $outputs, $notes, $createdBy) {
            $preparedInputs = [];
            $preparedOutputs = [];
            $totalInputWeight = 0.0;

            foreach ($inputs as $input) {
                $category = WasteCategory::findOrFail($input['waste_category_id']);
                $quantity = (float) $input['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Kuantitas input harus lebih dari 0.');
                }

                $preparedInputs[] = ['category' => $category, 'quantity' => $quantity];
                $totalInputWeight += $quantity;
            }

            foreach ($outputs as $output) {
                $product = Product::findOrFail($output['product_id']);
                $quantity = (float) $output['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Kuantitas output harus lebih dari 0.');
                }

                $preparedOutputs[] = ['product' => $product, 'quantity' => $quantity];
            }

            $transaction = ProcessingTransaction::create([
                'total_input_weight' => $totalInputWeight,
                'notes' => $notes,
                'created_by' => $createdBy?->id,
                'transacted_at' => now(),
            ]);

            foreach ($preparedInputs as $row) {
                ProcessingInput::create([
                    'processing_transaction_id' => $transaction->id,
                    'waste_category_id' => $row['category']->id,
                    'category_name_snapshot' => $row['category']->name,
                    'unit_snapshot' => $row['category']->unit,
                    'quantity' => $row['quantity'],
                ]);

                // Throws if stock insufficient - rolls back all
                $this->inventoryService->remove(
                    category: $row['category'],
                    quantity: $row['quantity'],
                    reason: 'process',
                    source: $transaction,
                    createdBy: $createdBy,
                );
            }

            foreach ($preparedOutputs as $row) {
                ProcessingOutput::create([
                    'processing_transaction_id' => $transaction->id,
                    'product_id' => $row['product']->id,
                    'product_name_snapshot' => $row['product']->name,
                    'unit_snapshot' => $row['product']->unit,
                    'quantity' => $row['quantity'],
                ]);

                $row['product']->increment('stock', $row['quantity']);
            }

            return $transaction->load('inputs', 'outputs');
        });
    }
}
