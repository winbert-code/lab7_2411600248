<div class="row g-3">
    <div class="col-md-4">
        <label for="code" class="form-label">Supplier code</label>
        <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $supplier->code) }}">
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-8">
        <label for="company_name" class="form-label">Company name <span class="text-danger">*</span></label>
        <input type="text" name="company_name" id="company_name" class="form-control @error('company_name') is-invalid @enderror"
               value="{{ old('company_name', $supplier->company_name) }}" required>
        @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="contact_person" class="form-label">Contact person <span class="text-danger">*</span></label>
        <input type="text" name="contact_person" id="contact_person" class="form-control @error('contact_person') is-invalid @enderror"
               value="{{ old('contact_person', $supplier->contact_person) }}" required>
        @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $supplier->email) }}" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
        <input type="tel" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $supplier->phone) }}" placeholder="09XX XXX XXXX" required>
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-5">
        <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
        <input type="text" name="address" id="address" class="form-control @error('address') is-invalid @enderror"
               value="{{ old('address', $supplier->address) }}" required>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label for="city" class="form-label">City</label>
        <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $supplier->city) }}">
    </div>
    <div class="col-md-3">
        <label for="payment_terms" class="form-label">Payment terms</label>
        <select name="payment_terms" id="payment_terms" class="form-select @error('payment_terms') is-invalid @enderror">
            @foreach (['COD', 'Net 15', 'Net 30', 'Net 45', 'Net 60'] as $term)
                <option value="{{ $term }}" @selected(old('payment_terms', $supplier->payment_terms) === $term)>{{ $term }}</option>
            @endforeach
        </select>
        @error('payment_terms') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label for="lead_time_days" class="form-label">Lead time (days) <span class="text-danger">*</span></label>
        <input type="number" name="lead_time_days" id="lead_time_days" min="1" max="180"
               class="form-control @error('lead_time_days') is-invalid @enderror"
               value="{{ old('lead_time_days', $supplier->lead_time_days) }}" required>
        @error('lead_time_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label for="minimum_order_amount" class="form-label">Minimum order (₱)</label>
        <input type="number" step="0.01" min="0" name="minimum_order_amount" id="minimum_order_amount"
               class="form-control @error('minimum_order_amount') is-invalid @enderror"
               value="{{ old('minimum_order_amount', $supplier->minimum_order_amount) }}">
        @error('minimum_order_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label for="status" class="form-label">Status</label>
        <select name="status" id="status" class="form-select">
            <option value="active" @selected(old('status', $supplier->status) === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $supplier->status) === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="col-12">
        <label for="notes" class="form-label">Notes</label>
        <textarea name="notes" id="notes" rows="3" class="form-control">{{ old('notes', $supplier->notes) }}</textarea>
    </div>
</div>
