<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProcessingInput;
use App\Models\ProcessingOutput;
use App\Models\ProcessingTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\WasteItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a waste-processing transaction: inputs drawn from the `sedekah`
 * inventory pool transformed into product outputs. Sampah nabung cannot be
 * processed — it is reserved for sale to mitra.
 */
class ProcessingTransactionService
{
    public function __construct(
        private InventoryService $inventoryService,
        private ProductInventoryService $productInventoryService,
    ) {}

    /**
     * @param  array<int, array{waste_item_id: int, quantity: float|string}>  $inputs
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
                $wasteItem = WasteItem::with('category')->findOrFail($input['waste_item_id']);
                $quantity = (float) $input['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Kuantitas input harus lebih dari 0.');
                }

                $preparedInputs[] = ['item' => $wasteItem, 'quantity' => $quantity];
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
                $wasteItem = $row['item'];

                ProcessingInput::create([
                    'processing_transaction_id' => $transaction->id,
                    'waste_item_id' => $wasteItem->id,
                    'item_code_snapshot' => $wasteItem->code,
                    'item_name_snapshot' => $wasteItem->name,
                    'category_name_snapshot' => $wasteItem->category->name,
                    'unit_snapshot' => $wasteItem->unit,
                    'quantity' => $row['quantity'],
                ]);

                // Throws if sedekah stock insufficient — rolls back entire transaction.
                $this->inventoryService->remove(
                    item: $wasteItem,
                    source: InventoryService::SOURCE_SEDEKAH,
                    quantity: $row['quantity'],
                    reason: 'process',
                    sourceRef: $transaction,
                    createdBy: $createdBy,
                );
            }

            foreach ($preparedOutputs as $row) {
                $output = ProcessingOutput::create([
                    'processing_transaction_id' => $transaction->id,
                    'product_id' => $row['product']->id,
                    'product_name_snapshot' => $row['product']->name,
                    'unit_snapshot' => $row['product']->unit,
                    'quantity' => $row['quantity'],
                ]);

                $this->productInventoryService->add(
                    product: $row['product'],
                    quantity: $row['quantity'],
                    reason: 'process',
                    sourceRef: $output,
                    createdBy: $createdBy,
                );
            }

            return $transaction->load('inputs', 'outputs');
        });
    }
}
