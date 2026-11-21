</main>
<footer class="site-footer">
    <div class="container">
        <div class="row gy-4 align-items-start">
            <div class="col-lg-5">
                <a class="navbar-brand brand-mark text-white" href="<?= e(url('index.php')) ?>"><span class="brand-icon"><i class="bi bi-layers-fill"></i></span>learn<span>hub</span></a>
                <p class="footer-copy mt-3">Make your next move a learning one. Build skills for the work and life you want.</p>
            </div>
            <div class="col-6 col-lg-2"><h6>Explore</h6><a href="<?= e(url('courses.php')) ?>">All courses</a><a href="<?= e(url('about.php')) ?>">About us</a></div>
            <div class="col-6 col-lg-2"><h6>Support</h6><a href="<?= e(url('contact.php')) ?>">Contact</a><a href="<?= e(url('login.php')) ?>">Your account</a></div>
            <div class="col-lg-3"><span class="footer-note"><i class="bi bi-stars me-2"></i>Small steps. Big possibilities.</span></div>
        </div>
        <div class="footer-bottom"><span>© <?= date('Y') ?> LearnHub Learning, Inc.</span><span>Made for curious minds everywhere.</span></div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
