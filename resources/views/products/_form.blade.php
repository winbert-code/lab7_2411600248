<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label">Product name <span class="text-danger">*</span></label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $product->name) }}" autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
        <input id="sku" name="sku" type="text" class="form-control @error('sku') is-invalid @enderror"
               value="{{ old('sku', $product->sku) }}" placeholder="CPU-AMD-7800X3D">
        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="brand_id" class="form-label">Brand <span class="text-danger">*</span></label>
        <select id="brand_id" name="brand_id" class="form-select @error('brand_id') is-invalid @enderror">
            <option value="">Select a brand</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}"
                    {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                    {{ $brand->name }}
                </option>
            @endforeach
        </select>
        @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
        <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
            <option value="">Select a category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}"
                    {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="supplier" class="form-label">Supplier</label>
        <input id="supplier" name="supplier" type="text" class="form-control @error('supplier') is-invalid @enderror"
               value="{{ old('supplier', $product->supplier) }}">
        @error('supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" rows="3"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="quantity" class="form-label">Quantity in stock <span class="text-danger">*</span></label>
        <input id="quantity" name="quantity" type="number" min="0" step="1"
               class="form-control @error('quantity') is-invalid @enderror"
               value="{{ old('quantity', $product->quantity) }}">
        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="reorder_level" class="form-label">Reorder level <span class="text-danger">*</span></label>
        <input id="reorder_level" name="reorder_level" type="number" min="0" step="1"
               class="form-control @error('reorder_level') is-invalid @enderror"
               value="{{ old('reorder_level', $product->reorder_level) }}">
        @error('reorder_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="unit_price" class="form-label">Unit price <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">&#8369;</span>
            <input id="unit_price" name="unit_price" type="number" min="0" step="0.01"
                   class="form-control @error('unit_price') is-invalid @enderror"
                   value="{{ old('unit_price', $product->unit_price) }}">
            @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save"></i> {{ $product->exists ? 'Save Changes' : 'Add Product' }}
    </button>
    <a href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}"
       class="btn btn-outline-secondary">Cancel</a>
</div>
