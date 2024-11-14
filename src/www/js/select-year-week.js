// Function to get the first Tuesday of a month
function getFirstTuesday(year, month) {
    const date = new Date(year, month, 1);
    while (date.getDay() !== 2) {
        date.setDate(date.getDate() + 1);
    }
    return date;
}

// Calculate available years
function calculateYearRange() {
    const startYear = 2020;
    const today = new Date();
    const currentYear = today.getFullYear();
    const isAfterJune1 = today.getMonth() >= 5;
    const endYear = isAfterJune1 ? currentYear : currentYear - 1;
    
    const years = [];
    for (let year = startYear; year <= endYear; year++) {
        years.push(year);
    }
    return years;
}

// Get all possible weeks
function getAllWeeks() {
    const weeks = [];
    // Add weeks 1-15
    for (let week = 1; week <= 15; week++) {
        weeks.push({ value: week.toString(), label: `Week ${week}` });
    }
    // Add bowls
    weeks.push({ value: "17", label: "Bowls" });
    return weeks;
}

// Calculate available weeks for current year
function getCurrentYearWeeks() {
    const today = new Date();
    const currentYear = today.getFullYear();
    
    const firstTuesdaySept = getFirstTuesday(currentYear, 8);
    const secondTuesdayDec = getFirstTuesday(currentYear, 11);
    secondTuesdayDec.setDate(secondTuesdayDec.getDate() + 7);

    const weeks = [];
    
    // Always add Week 1
    weeks.push({ value: "1", label: "Week 1" });

    // Add weeks 2-15 based on date
    for (let week = 2; week <= 15; week++) {
        const weekAvailableDate = new Date(firstTuesdaySept);
        weekAvailableDate.setDate(weekAvailableDate.getDate() + (7 * (week - 2)));
        
        if (today >= weekAvailableDate) {
            weeks.push({ value: week.toString(), label: `Week ${week}` });
        }
    }

    // Add bowls option if after second Tuesday in December
    if (today >= secondTuesdayDec) {
        weeks.push({ value: "17", label: "Bowls" });
    }

    return weeks;
}

// Get the latest available year and week
function getLatestAvailableYearAndWeek() {
    const years = calculateYearRange();
    const latestYear = years[years.length - 1];
    const weeks = latestYear === new Date().getFullYear() 
        ? getCurrentYearWeeks() 
        : getAllWeeks();
    const latestWeek = weeks[weeks.length - 1].value;
    return { year: latestYear, week: latestWeek };
}

// Function to get base URL from current location
function getBaseURL() {
    return `${window.location.protocol}//${window.location.host}${window.location.pathname}`;
}

// Function to redirect with parameters
function redirectWithParams(params) {
    const baseURL = getBaseURL();
    const queryString = new URLSearchParams(params).toString();
    window.location.href = `${baseURL}?${queryString}`;
}

// Initialize selectors
function initializeSelectors() {
    const yearSelect = document.getElementById('yearSelect');
    const weekSelect = document.getElementById('weekSelect');
    
    const urlParams = new URLSearchParams(window.location.search);
    const years = calculateYearRange();
    
    // Check if URL has parameters
    if (!urlParams.has('year') || !urlParams.has('week')) {
        // Redirect with latest available year and week
        const latest = getLatestAvailableYearAndWeek();
        redirectWithParams({ year: latest.year, week: latest.week });
        return;
    }
    
    const currentYear = urlParams.get('year');
    const currentWeek = urlParams.get('week');
    
    // Initialize years
    years.forEach(year => {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        if (year.toString() === currentYear) {
            option.selected = true;
        }
        yearSelect.appendChild(option);
    });

    // Initialize weeks based on selected year
    const weeks = currentYear === new Date().getFullYear().toString()
        ? getCurrentYearWeeks()
        : getAllWeeks();
        
    weeks.forEach(week => {
        const option = document.createElement('option');
        option.value = week.value;
        option.textContent = week.label;
        if (week.value === currentWeek) {
            option.selected = true;
        }
        weekSelect.appendChild(option);
    });

    // Add change handlers
    function handleYearChange() {
        const selectedYear = yearSelect.value;
        // Clear and repopulate week select based on selected year
        weekSelect.innerHTML = '';
        const weeks = selectedYear === new Date().getFullYear().toString()
            ? getCurrentYearWeeks()
            : getAllWeeks();
            
        weeks.forEach(week => {
            const option = document.createElement('option');
            option.value = week.value;
            option.textContent = week.label;
            weekSelect.appendChild(option);
        });
        // Select first available week
        weekSelect.value = weeks[0].value;
        handleURLUpdate();
    }

    function handleURLUpdate() {
        const params = {
            year: yearSelect.value,
            week: weekSelect.value
        };
        redirectWithParams(params);
    }

    yearSelect.addEventListener('change', handleYearChange);
    weekSelect.addEventListener('change', handleURLUpdate);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the selectors
    initializeSelectors();
    
    // Move the stats into position
    const statsSource = document.getElementById('stats-source');
    const statsContainer = document.getElementById('stats-container');
    if (statsSource && statsContainer) {
        statsContainer.appendChild(statsSource);
    }
});