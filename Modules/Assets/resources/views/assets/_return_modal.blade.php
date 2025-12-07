
{{-- NEW: Return Asset Modal --}}
<div class="modal fade" id="returnAssetModal" tabindex="-1" aria-labelledby="returnAssetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="returnAssetModalLabel">Return Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="returnAssetForm" onsubmit="return false;">
        @csrf {{-- Method will be POST --}}
        <input type="hidden" name="asset_id_return" id="return_asset_id" value=""> {{-- Use different ID to avoid conflict --}}
        <input type="hidden" name="assignment_id" id="return_assignment_id" value=""> {{-- Store current assignment ID if needed --}}

        <div class="modal-body">
          <div class="mb-3">
            <label>Asset:</label>
            <p class="fw-bold" id="returnAssetNameTag">Asset Name [Tag]</p>
          </div>
          <div class="mb-3">
            <label>Currently Assigned To:</label>
            <p class="fw-bold" id="returnCurrentAssignee">Employee Name</p>
          </div>
          <hr>
          {{-- Return Date --}}
          <div class="mb-3">
            <label class="form-label" for="returnedAt">Return Date <span class="text-danger">*</span></label>
            <input type="text" class="form-control flatpickr-input" id="returnedAt" name="returned_at" placeholder="YYYY-MM-DD" readonly="readonly" required>
            <div class="invalid-feedback"></div>
          </div>

          {{-- Condition In --}}
          <div class="mb-3">
            <label class="form-label" for="conditionIn">Condition on Return <span class="text-danger">*</span></label>
            <select id="conditionIn" name="condition_in" class="select2-return-condition form-select" required>
              <option value="">Select Condition</option>
              @foreach ($conditions as $condition) {{-- Passed from controller --}}
              <option value="{{ $condition->value }}">{{ $condition->label() }}</option>
              @endforeach
            </select>
            <div class="invalid-feedback"></div>
          </div>

          {{-- Notes --}}
          <div class="mb-3">
            <label class="form-label" for="returnNotes">Return Notes</label>
            <textarea class="form-control" id="returnNotes" name="notes" rows="3" placeholder="Optional notes about return condition, damage, etc..."></textarea>
            <div class="invalid-feedback"></div>
          </div>

          {{-- General Error Message Area --}}
          <div class="mb-3">
            <small class="text-danger" id="return-general-error"></small>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="submitReturnBtn">Confirm Return</button>
        </div>
      </form>
    </div>
  </div>
</div>
{{-- End Return Asset Modal --}}
