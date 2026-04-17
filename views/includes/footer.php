<footer class="kse-footer">

  <div class="footer-container">

    <!-- LEFT : LOGO + ADDRESS -->
    <div class="footer-col">
      <div class="footer-logo">
        <img src="/public/images/keystone_logo.jpeg" alt="KSE Logo">
        <h3>Keystone School of Engineering</h3>
        <p>Approved by AICTE, Govt. of Maharashtra</p>
      </div>

      <p>
        Keystone Campus, Near Handewadi Chowk,<br>
        Pune – 412308
      </p>

      <p>
        📞 9922887755 / 9922550060 <br>
        ✉ foundation@shalaka.org <br>
        🌐 www.keystoneschoolofengineering.com
      </p>
    </div>

    <!-- MIDDLE : QUICK LINKS -->
    <div class="footer-col">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="#">Complaints & Grievance</a></li>
        <li><a href="#">Online Grievance</a></li>
        <li><a href="#">AICTE</a></li>
        <li><a href="#">NAAC</a></li>
        <li><a href="#">SPPU</a></li>
      </ul>
    </div>

    <!-- RIGHT : PORTAL INFO -->
    <div class="footer-col">
      <h4>Event Management Portal</h4>
      <p>Smart Event Documentation System</p>

      <ul>
        <li><a href="#">Downloads</a></li>
        <li><a href="#">MoUs</a></li>
        <li><a href="#">Press Release</a></li>
      </ul>
    </div>

  </div>

  <!-- BOTTOM BAR -->
  <div class="footer-bottom">
    © <?= date("Y"); ?> Keystone School of Engineering | Event Management Portal
  </div>

</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="/public/js/clientSideValidation.js"></script>

<!-- CKEditor -->
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll('.ckeditor-field').forEach(function (element) {
        ClassicEditor
            .create(element, {
                toolbar: [
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'underline',
                    '|',
                    'bulletedList',
                    'numberedList',
                    '|',
                    'alignment',
                    '|',
                    'indent',
                    'outdent',
                    '|',
                    'blockQuote',
                    '|',
                    'undo',
                    'redo'
                ]
            })
            .catch(error => {
                console.error(error);
            });
    });

});
</script>

</body>
</html>
