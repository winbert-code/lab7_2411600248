<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Brand name <span class="text-danger">*</span></label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $brand->name) }}" autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="slug" class="form-label">Slug <span class="text-muted small">(optional, auto-generated if blank)</span></label>
        <input id="slug" name="slug" type="text" class="form-control @error('slug') is-invalid @enderror"
               value="{{ old('slug', $brand->slug) }}" placeholder="e.g. amd">
        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" rows="3"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $brand->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save"></i> {{ $brand->exists ? 'Save Changes' : 'Add Brand' }}
    </button>
    <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
