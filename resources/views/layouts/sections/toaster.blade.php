<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

<script>
  // Global SweetAlert translations
  if (!window.translations) {
    window.translations = {};
  }
  
  // Merge default SweetAlert translations
  window.translations = {
    ...window.translations,
    // SweetAlert messages
    areYouSure: '{{ __("Are you sure?") }}',
    youWontBeAbleToRevert: '{{ __("You won\'t be able to revert this!") }}',
    yesDeleteIt: '{{ __("Yes, delete it!") }}',
    deleted: '{{ __("Deleted!") }}',
    oops: '{{ __("Oops...") }}',
    success: '{{ __("Success") }}',
    error: '{{ __("Error") }}',
    confirm: '{{ __("Confirm") }}',
    cancel: '{{ __("Cancel") }}',
    yes: '{{ __("Yes") }}',
    no: '{{ __("No") }}',
    delete: '{{ __("Delete") }}',
    confirmDelete: '{{ __("Confirm Delete") }}',
    deleteWarning: '{{ __("You won\'t be able to revert this!") }}',
    confirmDeleteThis: '{{ __("Are you sure you want to delete this?") }}',
    failed: '{{ __("Failed") }}',
    warning: '{{ __("Warning") }}',
    areYouSureYouWantToPerform: '{{ __("Are you sure you want to perform this action?") }}',
    thisActionCannotBeUndone: '{{ __("This action cannot be undone.") }}',
    pleaseConfirmYourAction: '{{ __("Please confirm your action.") }}',
    submittedSuccessfully: '{{ __("Submitted successfully") }}',
    failedToSubmit: '{{ __("Failed to submit") }}',
    savedSuccessfully: '{{ __("Saved successfully") }}',
    failedToSave: '{{ __("Failed to save") }}',
    updatedSuccessfully: '{{ __("Updated successfully") }}',
    failedToUpdate: '{{ __("Failed to update") }}',
    deletedSuccessfully: '{{ __("Deleted successfully") }}',
    failedToDelete: '{{ __("Failed to delete") }}',
    loading: '{{ __("Loading...") }}',
    pleaseWait: '{{ __("Please wait") }}',
    processing: '{{ __("Processing") }}',
    roleDeleted: '{{ __("The role has been deleted!") }}',
    // Additional error messages for JS alerts
    cannotDelete: '{{ __("Cannot Delete") }}',
    assetAssignedWarning: '{{ __("This asset is currently assigned to an employee. Please return it first.") }}',
    categoryHas: '{{ __("Category has") }}',
    coursesReassignFirst: '{{ __("course(s). Please reassign first.") }}',
    courseHas: '{{ __("This course has") }}',
    enrollmentsManageFirst: '{{ __("active enrollment(s). Please manage enrollments first.") }}',
    failedToLoadTask: '{{ __("Failed to load task data.") }}',
    errorFetchingData: '{{ __("An error occurred while fetching data.") }}',
    operationFailed: '{{ __("Operation failed.") }}',
    errorOccurred: '{{ __("An error occurred.") }}',
    failedToUpdateStatus: '{{ __("Failed to update status.") }}',
    couldNotLoadNotes: '{{ __("Could not load notes. Please try again.") }}',
    failedToLoadNoteData: '{{ __("Failed to load note data for editing.") }}',
    couldNotLoadNoteData: '{{ __("Could not load note data.") }}',
    failedToLoadJobOpening: '{{ __("Failed to load job opening data.") }}',
    deleteAssetWarning: '{{ __("Delete this asset? This action cannot be undone.") }}',
    deleteCourseWarning: '{{ __("Delete this course? Associated lessons may also be deleted!") }}',
  };

  var notyf = new Notyf();
  // success message popup notification
  @if(session()->has('success'))
  notyf.success("{{ session()->get('success') }}");
  @endif

  // info message popup notification
  @if(session()->has('info'))
  notyf.info("{{ session()->get('info') }}");
  @endif

  // warning message popup notification
  @if(session()->has('warning'))
  notyf.warning("{{ session()->get('warning') }}");
  @endif

  // error message popup notification
  @if(session()->has('error'))
  notyf.error("{{ session()->get('error') }}");
  @endif

  @if ($errors->any())
  let errorMessages = `{!! implode('<br>', $errors->all()) !!}`;
  showErrorSwalHtml(errorMessages);
  @endif


  function showSuccessToast(message) {
    notyf.success(message);
  }

  function showErrorToast(message) {
    notyf.error(message);
  }

  function showInfoToast(message) {
    notyf.info(message);
  }

  function showWarningToast(message) {
    notyf.warning(message);
  }

  function showSuccessSwal(message) {
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: message,
      customClass: {
        confirmButton: 'btn btn-success'
      }
    });
  }

  function showInfoSwal(message) {
    Swal.fire({
      icon: 'info',
      title: 'Info',
      text: message,
      customClass: {
        confirmButton: 'btn btn-info'
      }
    });
  }

  function showWarningSwal(message) {
    Swal.fire({
      icon: 'warning',
      title: 'Warning',
      text: message,
      customClass: {
        confirmButton: 'btn btn-warning'
      }
    });
  }

  function showErrorSwal(message) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: message,
      customClass: {
        confirmButton: 'btn btn-danger'
      }
    });
  }

  function showErrorSwalHtml(message) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      html: message,
      customClass: {
        confirmButton: 'btn btn-danger'
      }
    });
  }

</script>
