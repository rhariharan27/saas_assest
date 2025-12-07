{{-- Offcanvas for Add/Edit Asset --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="assetOffcanvas" aria-labelledby="assetOffcanvasLabel" style="width: 650px;"> {{-- Wider offcanvas for more fields --}}
  <div class="offcanvas-header">
    <h5 id="assetOffcanvasLabel" class="offcanvas-title">Add Asset</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0">
    {{-- IMPORTANT: Add enctype for potential file uploads if adding asset image later --}}
    <form class="add-edit-asset-form pt-0" id="assetForm" onsubmit="return false;" >
      @csrf
      <input type="hidden" name="_method" id="assetMethod" value="POST">
      <input type="hidden" name="asset_id" id="asset_id" value="">

      <div class="row">
        {{-- Name --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="assetName">Asset Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="assetName" placeholder="e.g., HR Laptop 05" name="name" required />
          <div class="invalid-feedback"></div>
        </div>
        {{-- Asset Tag --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="assetTag">Asset Tag <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="assetTag" placeholder="Unique Internal ID" name="asset_tag" required />
          <div class="invalid-feedback"></div>
        </div>
      </div>

      <div class="row">
        {{-- Category --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="asset_category_id">Category</label>
          <select id="asset_category_id" name="asset_category_id" class="select2 form-select">
            <option value="">Select Category</option>
            @foreach ($categories as $category)
              <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
          </select>
          <div class="invalid-feedback"></div>
        </div>
        {{-- Serial Number --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="serialNumber">Serial Number</label>
          <input type="text" class="form-control" id="serialNumber" name="serial_number" placeholder="Manufacturer S/N"/>
          <div class="invalid-feedback"></div>
        </div>
      </div>

      <div class="row">
        {{-- Manufacturer --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="manufacturer">Manufacturer</label>
          <input type="text" class="form-control" id="manufacturer" name="manufacturer" placeholder="e.g., Dell, Apple"/>
          <div class="invalid-feedback"></div>
        </div>
        {{-- Model --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="model">Model</label>
          <input type="text" class="form-control" id="model" name="model" placeholder="e.g., Latitude 7400, MacBook Pro 14"/>
          <div class="invalid-feedback"></div>
        </div>
      </div>

      <div class="row">
        {{-- Status --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
          <select id="status" name="status" class="select2 form-select" required>
            <option value="">Select Status</option>
            @foreach ($statuses as $status)
              <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
          </select>
          <div class="invalid-feedback"></div>
        </div>
        {{-- Condition --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="condition">Condition</label>
          <select id="condition" name="condition" class="select2 form-select">
            <option value="">Select Condition</option>
            @foreach ($conditions as $condition)
              <option value="{{ $condition->value }}">{{ $condition->label() }}</option>
            @endforeach
          </select>
          <div class="invalid-feedback"></div>
        </div>
      </div>

      <div class="row">
        {{-- Purchase Date --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="purchase_date">Purchase Date</label>
          <input type="text" class="form-control flatpickr-input" id="purchase_date" name="purchase_date" placeholder="YYYY-MM-DD" readonly="readonly">
          <div class="invalid-feedback"></div>
        </div>
        {{-- Purchase Cost --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="purchase_cost">Purchase Cost</label>
          <input type="text" class="form-control numeral-mask" id="purchase_cost" name="purchase_cost" placeholder="e.g., 1200.50" /> {{-- Add class for cleavejs --}}
          <div class="invalid-feedback"></div>
        </div>
      </div>

      <div class="row">
        {{-- Supplier --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="supplier">Supplier</label>
          <input type="text" class="form-control" id="supplier" name="supplier" placeholder="Vendor name"/>
          <div class="invalid-feedback"></div>
        </div>
        {{-- Warranty Expiry --}}
        <div class="col-md-6 mb-3">
          <label class="form-label" for="warranty_expiry_date">Warranty Expiry</label>
          <input type="text" class="form-control flatpickr-input" id="warranty_expiry_date" name="warranty_expiry_date" placeholder="YYYY-MM-DD" readonly="readonly">
          <div class="invalid-feedback"></div>
        </div>
      </div>

      {{-- Location --}}
      <div class="mb-3">
        <label class="form-label" for="location">Current Location</label>
        <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Office A, Storage Room, Assigned"/>
        <div class="invalid-feedback"></div>
      </div>

      {{-- Notes --}}
      <div class="mb-3">
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any specific notes about this asset..."></textarea>
        <div class="invalid-feedback"></div>
      </div>

      {{-- General Error Message Area --}}
      <div class="mb-3">
        <small class="text-danger" id="general-error"></small>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit" id="submitAssetBtn">Submit</button>
        <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div> {{-- End Offcanvas --}}
