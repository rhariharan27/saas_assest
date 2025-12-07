{{-- NEW: Add Maintenance Log Modal --}}
<div class="modal fade" id="maintenanceLogModal" tabindex="-1" aria-labelledby="maintenanceLogModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="maintenanceLogModalLabel">Add Maintenance Log for {{ $asset->asset_tag }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="maintenanceLogForm" onsubmit="return false;">
        @csrf {{-- Method is POST --}}
        {{-- Pass asset ID if needed, although it's in the URL --}}
        {{-- <input type="hidden" name="asset_id" value="{{ $asset->id }}"> --}}
        <div class="modal-body">
          <div class="row g-3">
            {{-- Maintenance Type --}}
            <div class="col-12">
              <label for="maintenance_type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
              <select id="maintenance_type" name="maintenance_type" class="select2 form-select" required>
                <option value="">Select Type</option>
                @foreach ($maintenanceTypes as $type) {{-- Passed from controller --}}
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
              </select>
              <div class="invalid-feedback"></div>
            </div>
            {{-- Performed Date --}}
            <div class="col-md-6">
              <label class="form-label" for="performed_at">Performed Date <span class="text-danger">*</span></label>
              <input type="text" class="form-control flatpickr-input" id="performed_at" name="performed_at" placeholder="YYYY-MM-DD" readonly="readonly" required>
              <div class="invalid-feedback"></div>
            </div>
            {{-- Cost --}}
            <div class="col-md-6">
              <label class="form-label" for="cost">Cost (Optional)</label>
              <input type="text" class="form-control numeral-mask" id="cost" name="cost" placeholder="e.g., 150.00" />
              <div class="invalid-feedback"></div>
            </div>
            {{-- Provider --}}
            <div class="col-12">
              <label class="form-label" for="provider">Provider / Technician</label>
              <input type="text" class="form-control" id="provider" name="provider" placeholder="e.g., Internal IT Dept, Vendor Name"/>
              <div class="invalid-feedback"></div>
            </div>
            {{-- Details --}}
            <div class="col-12">
              <label class="form-label" for="details">Details / Work Performed <span class="text-danger">*</span></label>
              <textarea class="form-control" id="details" name="details" rows="3" required placeholder="Describe the maintenance performed..."></textarea>
              <div class="invalid-feedback"></div>
            </div>
            {{-- Next Due Date --}}
            <div class="col-12">
              <label class="form-label" for="next_due_date">Next Maintenance Due (Optional)</label>
              <input type="text" class="form-control flatpickr-input" id="next_due_date" name="next_due_date" placeholder="YYYY-MM-DD" readonly="readonly">
              <div class="invalid-feedback"></div>
            </div>
            {{-- Optional: Update Asset Status after maintenance --}}
            <div class="col-12">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" value="1" id="update_asset_status" name="update_asset_status">
                <label class="form-check-label" for="update_asset_status"> Update Asset Status After Maintenance?</label>
              </div>
              <div id="new_status_area" style="display:none;"> {{-- Show only if checkbox checked --}}
                <label class="form-label" for="new_asset_status">New Asset Status <span class="text-danger">*</span></label>
                <select id="new_asset_status" name="new_asset_status" class="select2 form-select">
                  <option value="">Select New Status</option>
                  @foreach ($statuses as $status) {{-- Use statuses passed from controller --}}
                  <option value="{{ $status->value }}">{{ $status->label() }}</option>
                  @endforeach
                </select>
                <div class="invalid-feedback"></div>
              </div>
            </div>

          </div> {{-- end row --}}
          <div class="mt-3 general-error-message text-danger small"></div> {{-- General error display --}}
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="submitMaintenanceBtn">Save Log</button>
        </div>
      </form>
    </div>
  </div>
</div>
{{-- End Maintenance Log Modal --}}
