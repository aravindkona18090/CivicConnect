// Counter animation (existing)
const counters = document.querySelectorAll('.stat h2');
counters.forEach(counter => {
    counter.innerText = '0';
    const updateCount = () => {
        const target = +counter.getAttribute('data-count');
        let current = +counter.innerText;
        const inc = Math.ceil(target / 200);
        if(current < target){
            counter.innerText = current + inc;
            setTimeout(updateCount, 10);
        } else {
            counter.innerText = target;
        }
    }
    updateCount();
});
document.getElementById("loginSelector").addEventListener("change", function() {
    let role = this.value;
    if (role) {
        window.location.href = role + "login/login.php"; // e.g., people_login.php, worker_login.php, admin_login.php
    }
});


// Language switching
document.getElementById('languageSelector').addEventListener('change', function() {
    const lang = this.value;
    fetch(`lang/${lang}.json`)
        .then(response => response.json())
        .then(data => {
            document.querySelector('.stat:nth-child(1) p').innerText = data.reported_issues;
            document.querySelector('.stat:nth-child(2) p').innerText = data.resolved_issues;
            document.querySelector('.stat:nth-child(3) p').innerText = data.in_progress_issues;
            document.querySelector('.report-btn').innerText = '⚠️ ' + data.report_issue;
        });
});
