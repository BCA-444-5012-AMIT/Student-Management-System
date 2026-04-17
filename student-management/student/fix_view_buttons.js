// Fix for student courses view buttons
// Add this script to the bottom of courses.php before the closing </body> tag

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Student courses page loaded');
    
    // Fix view buttons with click handlers
    const viewButtons = document.querySelectorAll('a[href*="courses.php?course_id"]');
    
    viewButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            console.log('Navigating to:', url);
            window.location.href = url;
        });
    });
    
    // Fix dropdown navigation
    const courseSelect = document.querySelector('select[onchange*="window.location.href"]');
    if (courseSelect) {
        courseSelect.addEventListener('change', function() {
            const courseId = this.value;
            if (courseId) {
                console.log('Changing to course:', courseId);
                window.location.href = 'courses.php?course_id=' + courseId;
            }
        });
    }
    
    // Fix clear button
    const clearButton = document.querySelector('button[onclick*="window.location.href"]');
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            console.log('Clearing selection');
            window.location.href = 'courses.php';
        });
    }
});
</script>
