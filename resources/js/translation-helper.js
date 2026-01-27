/**
 * JavaScript Translation Helper
 * This file provides translation functionality for JavaScript files
 */

'use strict';

// Global translation object that will be populated from Laravel
window.translations = window.translations || {};

/**
 * Translation function for JavaScript
 * @param {string} key - Translation key
 * @param {object} replace - Object with replacement values
 * @returns {string} - Translated string
 */
window.__ = function(key, replace = {}) {
    let translation = window.translations[key] || key;
    
    // Handle replacements
    Object.keys(replace).forEach(placeholder => {
        const regex = new RegExp(':' + placeholder, 'g');
        translation = translation.replace(regex, replace[placeholder]);
    });
    
    return translation;
};

/**
 * Get translation for chart/dashboard strings
 */
window.getTranslations = function() {
    return {
        // Dashboard translations
        departmentAttendanceOverview: __('Department Attendance Overview'),
        presentEmployees: __('Present Employees'),
        absentEmployees: __('Absent Employees'),
        numberOfEmployees: __('Number of Employees'),
        departments: __('Departments'),
        present: __('Present'),
        absent: __('Absent'),
        onLeave: __('On Leave'),
        
        // Common UI translations
        loading: __('Loading...'),
        noDataAvailable: __('No Data Available'),
        failedToLoadData: __('Failed to load data.'),
        noRecentActivitiesFound: __('No recent activities found.'),
        unknownUser: __('Unknown User'),
        
        // Action translations
        viewAll: __('View All'),
        showMore: __('Show More'),
        showLess: __('Show Less'),
        loadMore: __('Load More'),
        
        // Status translations
        success: __('Success'),
        warning: __('Warning'),
        error: __('Error'),
        info: __('Info'),
        
        // Confirmation translations
        areYouSure: __('Are you sure?'),
        thisActionCannotBeUndone: __('This action cannot be undone'),
        yes: __('Yes'),
        no: __('No'),
        confirm: __('Confirm'),
        cancel: __('Cancel'),
        
        // Form translations
        requiredField: __('Required field'),
        invalidInput: __('Invalid input'),
        pleaseWait: __('Please Wait'),
        
        // Pagination translations
        showing: __('Showing'),
        to: __('to'),
        of: __('of'),
        results: __('results'),
        page: __('Page'),
        previousPage: __('Previous Page'),
        nextPage: __('Next Page'),
        
        // Filter translations
        filter: __('Filter'),
        clearFilter: __('Clear Filter'),
        applyFilter: __('Apply Filter'),
        sortBy: __('Sort by'),
        ascending: __('Ascending'),
        descending: __('Descending')
    };
};

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { __, getTranslations };
}