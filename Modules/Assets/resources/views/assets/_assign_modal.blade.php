
{{-- NEW: Assign Asset Modal --}}
<div class="modal fade" id="assignAssetModal" tabindex="-1" aria-labelledby="assignAssetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assignAssetModalLabel">Assign Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="assignAssetForm" onsubmit="return false;">
        @csrf {{-- Method will be POST --}}
        <input type="hidden" name="asset_id" id="assign_asset_id" value="">

        <div class="modal-body">
          <div class="mb-3">
            <label>Asset:</label>
            <p class="fw-bold" id="assignAssetNameTag">Asset Name [Tag]</p>
          </div>

          {{-- User/Employee Selection --}}
          <div class="mb-3">
            <label for="assignUserId" class="form-label">Assign To Employee <span class="text-danger">*</span></label>
            <select id="assignUserId" name="user_id" class="select2-assign-user form-select" required>
              <option value="">Select Employee</option>
              {{-- $users should be passed from AssetController@index --}}
              @foreach ($users as $user)
                <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
              @endforeach
            </select>
            <div class="invalid-feedback"></div>
          </div>

          {{-- Assignment Date --}}
          <div class="mb-3">
            <label class="form-label" for="assignedAt">Assignment Date <span class="text-danger">*</span></label>
            <input type="text" class="form-control flatpickr-input" id="assignedAt" name="assigned_at" placeholder="YYYY-MM-DD" readonly="readonly" required>
            <div class="invalid-feedback"></div>
          </div>

          {{-- Expected Return Date --}}
          <div class="mb-3">
            <label class="form-label" for="expectedReturnDate">Expected Return Date (Optional)</label>
            <input type="text" class="form-control flatpickr-input" id="expectedReturnDate" name="expected_return_date" placeholder="YYYY-MM-DD" readonly="readonly">
            <div class="invalid-feedback"></div>
          </div>

          {{-- Condition Out --}}
          <div class="mb-3">
            <label class="form-label" for="conditionOut">Condition When Assigned</label>
            <select id="conditionOut" name="condition_out" class="select2-basic form-select">
              <option value="">Select Condition (Optional)</option>
              @foreach ($conditions as $condition) {{-- Passed from controller --}}
              <option value="{{ $condition->value }}">{{ $condition->label() }}</option>
              @endforeach
            </select>
            <div class="invalid-feedback"></div>
          </div>

          {{-- Notes --}}
          <div class="mb-3">
            <label class="form-label" for="assignNotes">Assignment Notes</label>
            <textarea class="form-control" id="assignNotes" name="notes" rows="3" placeholder="Optional notes about this assignment..."></textarea>
            <div class="invalid-feedback"></div>
          </div>

          {{-- General Error Message Area --}}
          <div class="mb-3">
            <small class="text-danger" id="assign-general-error"></small>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="submitAssignBtn">Assign Asset</button>
        </div>
      </form>
    </div>
  </div>
</div>
{{-- End Assign Asset Modal --}}
