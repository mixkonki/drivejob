<footer>
    <div class="footer-left">
        <p>© 2024 DriveJob. Όλα τα δικαιώματα κατοχυρωμένα.</p>
    </div>
    <div class="footer-center">
        <ul class="footer-links">
            <li><a href="<?php echo BASE_URL; ?>terms">Όροι Χρήσης</a></li>
            <li><a href="<?php echo BASE_URL; ?>privacy">Πολιτική Απορρήτου</a></li>
            <li><a href="<?php echo BASE_URL; ?>faq">FAQ</a></li>
        </ul>
    </div>
    <div class="footer-right">
        <a href="https://facebook.com/drivejob" target="_blank">
            <img src="<?php echo BASE_URL; ?>img/facebook-icon.png" alt="Facebook">
        </a>
        <a href="https://linkedin.com/company/drivejob" target="_blank">
            <img src="<?php echo BASE_URL; ?>img/linkedin-icon.png" alt="LinkedIn">
        </a>
    </div>
</footer>

<!-- Cookie banner (Πακέτο 7 — GDPR). Μόνο τεχνικά απαραίτητα cookies σήμερα· -->
<!-- το banner ενημερώνει και αποθηκεύει την επιλογή σε first-party cookie.   -->
<div id="dj-cookie-banner" style="display:none; position:fixed; bottom:0; left:0; right:0; z-index:9999; background:#2c3e50; color:#fff; padding:16px 20px; box-shadow:0 -2px 12px rgba(0,0,0,.25); font-size:14px; line-height:1.5;">
    <div style="max-width:1100px; margin:0 auto; display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
        <span style="flex:1 1 480px;">
            Το DriveJob χρησιμοποιεί μόνο <strong>τεχνικά απαραίτητα cookies</strong> για τη σύνδεσή σας —
            κανένα cookie διαφήμισης ή παρακολούθησης.
            <a href="<?php echo BASE_URL; ?>privacy" style="color:#9ecbff; text-decoration:underline;">Πολιτική Απορρήτου</a>
        </span>
        <button type="button" id="dj-cookie-accept" style="background:#c62828; color:#fff; border:0; border-radius:6px; padding:10px 22px; cursor:pointer; font-size:14px;">Το κατάλαβα</button>
    </div>
</div>
<script>
(function () {
    function getCookie(name) {
        var m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
        return m ? m.pop() : null;
    }
    if (!getCookie('dj_cookie_consent')) {
        var b = document.getElementById('dj-cookie-banner');
        if (b) { b.style.display = 'block'; }
    }
    var btn = document.getElementById('dj-cookie-accept');
    if (btn) {
        btn.addEventListener('click', function () {
            var d = new Date();
            d.setFullYear(d.getFullYear() + 1);
            document.cookie = 'dj_cookie_consent=essential; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
            document.getElementById('dj-cookie-banner').style.display = 'none';
        });
    }
})();
</script>
