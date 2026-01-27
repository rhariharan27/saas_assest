<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

<script>
  // Load all translation JSON files - MUST be loaded BEFORE any other scripts use window.translations
  const translationsAR = @json(json_decode(file_get_contents(base_path('lang/ar.json')), true));
  const translationsEN = @json(json_decode(file_get_contents(base_path('lang/en.json')), true));
  
  window.allLanguageTranslations = {
    ar: translationsAR,
    en: translationsEN
  };
  
  console.log('[Translations] ====== INITIALIZATION START ======');
  console.log('[Translations] Loaded language files - AR keys: ' + Object.keys(translationsAR).length);
  console.log('[Translations] Loaded language files - EN keys: ' + Object.keys(translationsEN).length);
  console.log('[Translations] AR sample: "Are you sure?" =', translationsAR['Are you sure?']);
  console.log('[Translations] EN sample: "Are you sure?" =', translationsEN['Are you sure?']);
  
  // Helper function to get cookie value
  function getCookie(name) {
    const nameEQ = name + "=";
    const cookies = document.cookie.split(';');
    for(let i = 0; i < cookies.length; i++) {
      let cookie = cookies[i].trim();
      if (cookie.indexOf(nameEQ) === 0) {
        return cookie.substring(nameEQ.length);
      }
    }
    return null;
  }
  
  // Get current locale from HTML attribute or session
  function getCurrentLocale() {
    // Try multiple ways to get the locale
    const htmlLang = document.documentElement.getAttribute('lang');
    const cookieLocale = getCookie('appLocale');
    const localStorageLocale = localStorage.getItem('appLocale');
    const serverLocale = '{{ session()->get("locale") ?? "" }}';
    const fallbackLocale = '{{ app()->getLocale() }}';
    
    console.log('[Translations] Debug info:');
    console.log('  - HTML lang attribute:', htmlLang);
    console.log('  - Cookie appLocale:', cookieLocale);
    console.log('  - localStorage appLocale:', localStorageLocale);
    console.log('  - Server session locale:', serverLocale);
    console.log('  - Fallback app locale:', fallbackLocale);
    
    // Priority: HTML attribute > Cookie > server session > localStorage > fallback
    let locale = 'en';
    
    if (htmlLang && htmlLang.trim()) {
      locale = htmlLang.split('-')[0].toLowerCase();
      console.log('[Translations] Selected: HTML lang:', locale);
    } else if (cookieLocale && cookieLocale.trim()) {
      locale = cookieLocale.toLowerCase();
      console.log('[Translations] Selected: cookie:', locale);
    } else if (serverLocale && serverLocale.trim()) {
      locale = serverLocale.toLowerCase();
      console.log('[Translations] Selected: server session:', locale);
    } else if (localStorageLocale && localStorageLocale.trim()) {
      locale = localStorageLocale.toLowerCase();
      console.log('[Translations] Selected: localStorage:', locale);
    } else if (fallbackLocale && fallbackLocale.trim()) {
      locale = fallbackLocale.toLowerCase();
      console.log('[Translations] Selected: fallback:', locale);
    }
    
    console.log('[Translations] Final locale:', locale);
    return locale;
  }
  
  // Initialize window.translations immediately
  function initializeTranslations() {
    const currentLocale = getCurrentLocale();
    console.log('[Translations] Initializing for locale:', currentLocale);
    
    if (!window.translations) {
      window.translations = {};
    }
    
    // Get translations for current locale - make sure locale is valid
    let translationsForLocale = window.allLanguageTranslations[currentLocale];
    
    if (!translationsForLocale) {
      console.warn('[Translations] No translations found for locale "' + currentLocale + '", checking alternatives...');
      // Try to find any matching locale
      for (let availableLocale in window.allLanguageTranslations) {
        if (availableLocale.startsWith(currentLocale) || currentLocale.startsWith(availableLocale)) {
          translationsForLocale = window.allLanguageTranslations[availableLocale];
          console.log('[Translations] Found matching locale:', availableLocale);
          break;
        }
      }
    }
    
    if (!translationsForLocale) {
      console.warn('[Translations] No translations found for locale: ' + currentLocale + ', using English');
      translationsForLocale = window.allLanguageTranslations.en;
    }
    
    // Copy translations to window.translations
    window.translations = Object.assign({}, translationsForLocale);
    
    // Add camelCase aliases for common keys used in JavaScript
    window.translations.areYouSure = window.translations['Are you sure?'];
    window.translations.youWontBeAbleToRevert = window.translations['You won\'t be able to revert this!'];
    window.translations.yesDeleteIt = window.translations['Yes, delete it!'];
    window.translations.deleted = window.translations['Deleted!'];
    window.translations.oops = window.translations['Oops...'];
    window.translations.success = window.translations['Success'];
    window.translations.error = window.translations['Error'];
    window.translations.confirm = window.translations['Confirm'];
    window.translations.cancel = window.translations['Cancel'];
    window.translations.cannotDelete = window.translations['Cannot Delete'];
    window.translations.assetAssignedWarning = window.translations['This asset is currently assigned to an employee. Please return it first.'];
    window.translations.deleteAssetWarning = window.translations['Delete this asset? This action cannot be undone.'];
    window.translations.deleteCourseWarning = window.translations['Delete this course? Associated lessons may also be deleted!'];
    window.translations.operationFailed = window.translations['Operation failed.'];
    window.translations.failedToLoadTask = window.translations['Failed to load task data.'];
    window.translations.failedToUpdateStatus = window.translations['Failed to update status.'];
    window.translations.deleteWarning = window.translations['You won\'t be able to revert this!'];
    window.translations.categoryHas = window.translations['Category has'];
    window.translations.coursesReassignFirst = window.translations['course(s). Please reassign first.'];
    window.translations.courseHas = window.translations['This course has'];
    window.translations.enrollmentsManageFirst = window.translations['active enrollment(s). Please manage enrollments first.'];
    
    window.currentLocale = currentLocale;
    
    console.log('[Translations] ====== INITIALIZATION COMPLETE ======');
    console.log('[Translations] Locale:', currentLocale);
    console.log('[Translations] Total keys:', Object.keys(window.translations).length);
    console.log('[Translations] "Are you sure?":', window.translations['Are you sure?']);
    console.log('[Translations] "areYouSure" alias:', window.translations.areYouSure);
    console.log('[Translations] Sample test - checking if translations are loaded correctly');
    if (currentLocale === 'ar') {
      console.log('[Translations] ✓ ARABIC MODE: Success message should be:', window.translations['Success']);
      console.log('[Translations] ✓ ARABIC MODE: Are you sure should be:', window.translations['Are you sure?']);
    }
  }
  
  // Initialize immediately (before other scripts run)
  initializeTranslations();
  
  // Dynamic language switcher
  window.switchLanguageDynamic = function(locale) {
    console.log('[Translations] Switching to locale:', locale);
    
    if (!window.allLanguageTranslations[locale]) {
      console.warn('[Translations] Locale not available:', locale);
      return;
    }
    
    localStorage.setItem('appLocale', locale);
    
    // Navigate to language swap endpoint
    window.location.href = '/lang/' + locale;
  };

  // Helper function to translate a message - tries exact key first, then falls back to camelCase, then original message
  window.trans = function(key, fallback) {
    if (!window.translations) {
      console.warn('[Translations] window.translations not initialized, using fallback:', fallback);
      return fallback || key;
    }
    
    // Try exact key first
    if (window.translations[key]) {
      return window.translations[key];
    }
    
    // Try camelCase version
    const camelKey = key.replace(/\s+(.)/g, (m, c) => c.toUpperCase()).replace(/^\w/, c => c.toLowerCase());
    if (window.translations[camelKey]) {
      return window.translations[camelKey];
    }
    
    // Return fallback
    return fallback || key;
  };

  // Convenient aliases for common translations
  window.getSuccessTitle = function() { return window.trans('Success', 'Success'); };
  window.getErrorTitle = function() { return window.trans('Error', 'Error'); };
  window.getConfirmTitle = function() { return window.trans('Are you sure?', 'Are you sure?'); };
  window.getDeleteConfirmText = function() { return window.trans("You won't be able to revert this!", "You won't be able to revert this!"); };
  window.getYesDeleteButton = function() { return window.trans('Yes, delete it!', 'Yes, delete it!'); };
  window.getCancelButton = function() { return window.trans('Cancel', 'Cancel'); };

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
