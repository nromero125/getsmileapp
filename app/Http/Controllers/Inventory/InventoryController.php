<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index()
    {
        $clinicId = auth()->user()->clinic_id;

        $categories = InventoryCategory::where('clinic_id', $clinicId)
            ->withCount('items')
            ->orderBy('name')
            ->get();

        $items = InventoryItem::where('clinic_id', $clinicId)
            ->with(['category', 'treatments'])
            ->orderBy('name')
            ->get();

        $treatments = \App\Models\Treatment::where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->get(['id', 'name']);

        return Inertia::render('Inventory/Index', [
            'categories' => $categories,
            'items'      => $items,
            'treatments' => $treatments,
            'stats'      => [
                'total'       => $items->count(),
                'low_stock'   => $items->filter->is_low_stock->count(),
                'total_value' => (float) $items->sum(fn($i) => $i->stock * $i->cost_per_unit),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $clinicId  = auth()->user()->clinic_id;
        $validated = $this->validateItem($request);

        $item = InventoryItem::create([...$validated, 'clinic_id' => $clinicId]);

        if ($request->stock > 0) {
            InventoryMovement::create([
                'clinic_id'           => $clinicId,
                'inventory_item_id'   => $item->id,
                'user_id'             => auth()->id(),
                'type'                => 'in',
                'quantity'            => $validated['stock'],
                'stock_after'         => $validated['stock'],
                'reason'              => 'Stock inicial',
            ]);
        }

        $this->syncTreatmentLinks($item, $request->treatment_links ?? []);

        return back()->with('success', 'Artículo creado.');
    }

    public function update(Request $request, InventoryItem $item)
    {
        $this->authorizeClinic($item);
        $validated = $this->validateItem($request);

        $item->update($validated);

        $this->syncTreatmentLinks($item, $request->treatment_links ?? []);

        return back()->with('success', 'Artículo actualizado.');
    }

    public function destroy(InventoryItem $item)
    {
        $this->authorizeClinic($item);
        $item->delete();
        return back()->with('success', 'Artículo eliminado.');
    }

    public function storeMovement(Request $request, InventoryItem $item)
    {
        $this->authorizeClinic($item);

        $validated = $request->validate([
            'type'     => 'required|in:in,out,adjustment',
            'quantity' => 'required|numeric|min:0',
            'reason'   => 'nullable|string|max:255',
        ]);

        $stock = (float) $item->stock;

        $newStock = match ($validated['type']) {
            'in'         => $stock + $validated['quantity'],
            'out'        => max(0, $stock - $validated['quantity']),
            'adjustment' => (float) $validated['quantity'],
        };

        InventoryMovement::create([
            'clinic_id'         => $item->clinic_id,
            'inventory_item_id' => $item->id,
            'user_id'           => auth()->id(),
            'type'              => $validated['type'],
            'quantity'          => $validated['quantity'],
            'stock_after'       => $newStock,
            'reason'            => $validated['reason'] ?? null,
        ]);

        $item->update(['stock' => $newStock]);

        return back()->with('success', 'Movimiento registrado.');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
        ]);

        InventoryCategory::create([...$validated, 'clinic_id' => auth()->user()->clinic_id]);

        return back()->with('success', 'Categoría creada.');
    }

    public function destroyCategory(InventoryCategory $category)
    {
        abort_if($category->clinic_id !== auth()->user()->clinic_id, 403);
        $category->delete();
        return back()->with('success', 'Categoría eliminada.');
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'name'                  => 'required|string|max:255',
            'inventory_category_id' => 'nullable|exists:inventory_categories,id',
            'description'           => 'nullable|string|max:500',
            'unit'                  => 'required|string|max:30',
            'stock'                 => 'required|numeric|min:0',
            'min_stock'             => 'required|numeric|min:0',
            'cost_per_unit'         => 'required|numeric|min:0',
            'supplier'              => 'nullable|string|max:255',
            'sku'                   => 'nullable|string|max:100',
            'is_active'             => 'boolean',
        ]);
    }

    private function syncTreatmentLinks(InventoryItem $item, array $links): void
    {
        $sync = [];
        foreach ($links as $link) {
            if (!empty($link['treatment_id'])) {
                $sync[$link['treatment_id']] = ['quantity_used' => $link['quantity_used'] ?? 1];
            }
        }
        $item->treatments()->sync($sync);
    }

    private function authorizeClinic(InventoryItem $item): void
    {
        abort_if($item->clinic_id !== auth()->user()->clinic_id, 403);
    }
}
