<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSale;
use App\Models\ProductSaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a product sale to a buyer (walk-in, nasabah, reseller, etc.).
 *
 * Snapshots each item's price so historical sales stay intact even after
 * prices change. Stock decrements via ProductInventoryService to keep the
 * audit trail in product_movements.
 */
class ProductSalesService
{
    public function __construct(
        private ProductInventoryService $productInventoryService,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: float|string, price_per_unit?: float|string}>  $items
     */
    public function create(
        string $buyerName,
        string $buyerPhone,
        array $items,
        ?User $buyerUser = null,
        string $paymentMethod = 'cash',
        string $paymentStatus = 'paid',
        ?string $notes = null,
        ?User $createdBy = null,
    ): ProductSale {
        if (trim($buyerName) === '') {
            throw new InvalidArgumentException('Nama pembeli wajib diisi.');
        }

        if (trim($buyerPhone) === '') {
            throw new InvalidArgumentException('Nomor HP pembeli wajib diisi.');
        }

        if (empty($items)) {
            throw new InvalidArgumentException('Minimal satu item produk.');
        }

        if (! in_array($paymentMethod, ProductSale::PAYMENT_METHODS, true)) {
            throw new InvalidArgumentException("Metode pembayaran tidak valid: '{$paymentMethod}'.");
        }

        if (! in_array($paymentStatus, ProductSale::PAYMENT_STATUSES, true)) {
            throw new InvalidArgumentException("Status pembayaran tidak valid: '{$paymentStatus}'.");
        }

        return DB::transaction(function () use (
            $buyerName,
            $buyerPhone,
            $items,
            $buyerUser,
            $paymentMethod,
            $paymentStatus,
            $notes,
            $createdBy,
        ) {
            $prepared = [];
            $totalQuantity = 0.0;
            $totalValue = 0.0;

            foreach ($items as $item) {
                $product = Product::with('currentPrice')->findOrFail($item['product_id']);
                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
                }

                $priceRecord = $product->currentPrice;
                $pricePerUnit = isset($item['price_per_unit']) && $item['price_per_unit'] !== ''
                    ? (float) $item['price_per_unit']
                    : (float) ($priceRecord?->price_per_unit ?? $product->price);

                if ($pricePerUnit < 0) {
                    throw new InvalidArgumentException('Harga tidak valid.');
                }

                $subtotal = round($quantity * $pricePerUnit, 2);

                $prepared[] = [
                    'product' => $product,
                    'price_record_id' => $priceRecord?->id,
                    'price_per_unit' => $pricePerUnit,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];

                $totalQuantity += $quantity;
                $totalValue += $subtotal;
            }

            $sale = ProductSale::create([
                'buyer_user_id' => $buyerUser?->id,
                'buyer_name' => trim($buyerName),
                'buyer_phone' => trim($buyerPhone),
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'notes' => $notes,
                'created_by' => $createdBy?->id,
                'transacted_at' => now(),
            ]);

            foreach ($prepared as $row) {
                $product = $row['product'];

                ProductSaleItem::create([
                    'product_sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_price_id' => $row['price_record_id'],
                    'product_name_snapshot' => $product->name,
                    'unit_snapshot' => $product->unit,
                    'price_per_unit_snapshot' => $row['price_per_unit'],
                    'quantity' => $row['quantity'],
                    'subtotal' => $row['subtotal'],
                ]);

                // Throws if stock insufficient — rolls back entire transaction.
                $this->productInventoryService->remove(
                    product: $product,
                    quantity: $row['quantity'],
                    reason: 'sale',
                    sourceRef: $sale,
                    createdBy: $createdBy,
                );
            }

            return $sale->load('items', 'buyer');
        });
    }

    public function markPaid(ProductSale $sale): ProductSale
    {
        if ($sale->payment_status === 'paid') {
            return $sale;
        }

        $sale->payment_status = 'paid';
        $sale->save();

        return $sale;
    }
}
